<?php

namespace Webkul\Sales\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderStatus;
use Webkul\Sales\Models\OrderStatusHistory;
use Webkul\Sales\Models\OrderStatusTransition;

class OrderStatusTransitionService
{
    /**
     * Cache TTL for statuses and transitions (seconds).
     */
    private const CACHE_TTL = 3600;

    /**
     * Attempt to transition an order to a new status.
     *
     * Performs validation, writes history, and fires events — all within a DB transaction.
     * Returns immediately (idempotent) when the order is already in the requested status.
     */
    public function transition(Order $order, string $toStatus, TransitionContext $ctx): TransitionResult
    {
        // Idempotency: already in target status.
        if ($order->status === $toStatus) {
            return TransitionResult::success($order, 'Order already in the requested status.');
        }

        if (! $this->canTransition($order, $toStatus, $ctx)) {
            $message = "Transition from '{$order->status}' to '{$toStatus}' is not allowed.";

            Log::warning('OrderStatusTransitionService: invalid transition', [
                'order_id'    => $order->id,
                'from_status' => $order->status,
                'to_status'   => $toStatus,
                'source'      => $ctx->source,
                'actor_id'    => $ctx->actorId,
            ]);

            return TransitionResult::failure($order, $message, ['status' => [$message]]);
        }

        try {
            DB::transaction(function () use ($order, $toStatus, $ctx) {
                // Lock the order row to prevent concurrent updates.
                $order->lockForUpdate()->find($order->id);

                $oldStatus = $order->status;

                $order->status = $toStatus;
                $order->save();

                OrderStatusHistory::create([
                    'order_id'  => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $toStatus,
                    'user_type'  => $ctx->actorType,
                    'user_id'    => $ctx->actorId,
                    'user_name'  => $ctx->actorName,
                    'source'     => $ctx->source,
                ]);

                Event::dispatch('sales.order.update-status.after', $order);
            });
        } catch (\Throwable $e) {
            Log::error('OrderStatusTransitionService: transition failed', [
                'order_id'  => $order->id,
                'to_status' => $toStatus,
                'error'     => $e->getMessage(),
            ]);

            return TransitionResult::failure($order, 'Transition failed: '.$e->getMessage());
        }

        $order->refresh();

        return TransitionResult::success($order);
    }

    /**
     * Determine whether the given transition is currently allowed.
     */
    public function canTransition(Order $order, string $toStatus, TransitionContext $ctx): bool
    {
        // The target status must exist and be active.
        $target = $this->getStatusByCode($toStatus);

        if (! $target || ! $target->is_active) {
            return false;
        }

        $fromStatus = $order->status;

        // If the current status is not known in the DB (legacy data), allow transition
        // but log a warning so the data discrepancy is visible.
        $current = $this->getStatusByCode($fromStatus);

        if (! $current) {
            Log::warning('OrderStatusTransitionService: current order status not found in order_statuses', [
                'order_id'    => $order->id,
                'from_status' => $fromStatus,
                'to_status'   => $toStatus,
            ]);

            return true;
        }

        // Terminal statuses block all outgoing transitions unless an explicit rule exists.
        if ($current->is_terminal) {
            return $this->transitionRuleExists($fromStatus, $toStatus, $ctx);
        }

        return $this->transitionRuleExists($fromStatus, $toStatus, $ctx);
    }

    /**
     * Return all available target statuses for an order given the context.
     *
     * @return Collection<int, OrderStatus>
     */
    public function getAvailableTransitions(Order $order, TransitionContext $ctx): Collection
    {
        $fromStatus = $order->status;

        $transitions = $this->getTransitionsForContext(
            $fromStatus,
            $ctx->deliveryType,
            $ctx->paymentType,
            $ctx->channel
        );

        $toCodes = $transitions->pluck('to_status_code')->unique()->values();

        return $this->getAllStatuses()
            ->whereIn('code', $toCodes)
            ->where('is_active', true)
            ->values();
    }

    /**
     * Return all order statuses (cached).
     *
     * @return Collection<int, OrderStatus>
     */
    public function getAllStatuses(): Collection
    {
        return Cache::remember('order_statuses_all', self::CACHE_TTL, fn () => OrderStatus::orderBy('sort_order')->get());
    }

    /**
     * Flush the cached statuses and transitions.
     */
    public function flushCache(): void
    {
        Cache::forget('order_statuses_all');
        Cache::forget('order_status_transitions_all');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getStatusByCode(string $code): ?OrderStatus
    {
        return $this->getAllStatuses()->firstWhere('code', $code);
    }

    private function transitionRuleExists(
        string $fromCode,
        string $toCode,
        TransitionContext $ctx
    ): bool {
        return $this->getTransitionsForContext(
            $fromCode,
            $ctx->deliveryType,
            $ctx->paymentType,
            $ctx->channel
        )->contains('to_status_code', $toCode);
    }

    /**
     * Return active transitions from the cache, filtered by context.
     *
     * @return Collection<int, OrderStatusTransition>
     */
    private function getTransitionsForContext(
        string $fromCode,
        ?string $deliveryType,
        ?string $paymentType,
        ?string $channel
    ): Collection {
        $all = Cache::remember(
            'order_status_transitions_all',
            self::CACHE_TTL,
            fn () => OrderStatusTransition::where('is_active', true)->orderBy('priority')->get()
        );

        return $all->filter(function (OrderStatusTransition $t) use ($fromCode, $deliveryType, $paymentType, $channel) {
            if ($t->from_status_code !== $fromCode) {
                return false;
            }

            if ($t->delivery_type !== null && $t->delivery_type !== $deliveryType) {
                return false;
            }

            if ($t->payment_type !== null && $t->payment_type !== $paymentType) {
                return false;
            }

            if ($t->channel !== null && $t->channel !== $channel) {
                return false;
            }

            return true;
        })->values();
    }
}
