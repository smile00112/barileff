<?php

namespace Webkul\ManagerApp\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Webkul\Sales\Models\Order;
use Webkul\User\Contracts\Admin;

class ManagerOrderService
{
    /**
     * Statuses the manager is allowed to set.
     */
    public const ALLOWED_STATUSES = [
        Order::STATUS_PROCESSING,
        Order::STATUS_COMPLETED,
        Order::STATUS_CANCELED,
        Order::STATUS_AWAITING_CONFIRMATION,
    ];

    /**
     * Return paginated orders visible to this manager (filtered by inventory source).
     */
    public function getOrdersForManager(Admin $admin, array $filters = []): LengthAwarePaginator
    {
        $sourceIds = $admin->getRestrictedInventorySourceIds();

        $query = Order::with(['billing_address', 'shipping_address', 'payment', 'items'])
            ->whereIn('inventory_source_id', $sourceIds)
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('increment_id', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%")
                    ->orWhere('customer_first_name', 'like', "%{$term}%")
                    ->orWhere('customer_last_name', 'like', "%{$term}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Find a single order belonging to manager's inventory sources.
     */
    public function findOrderForManager(Admin $admin, int $orderId): ?Order
    {
        $sourceIds = $admin->getRestrictedInventorySourceIds();

        return Order::with(['billing_address', 'shipping_address', 'payment', 'items', 'comments'])
            ->whereIn('inventory_source_id', $sourceIds)
            ->find($orderId);
    }

    /**
     * Update order status (must be within allowed statuses).
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        return $order->refresh();
    }

    /**
     * Return allowed statuses with their display labels.
     *
     * @return array<string, string>
     */
    public function getStatusLabels(): array
    {
        return [
            Order::STATUS_PROCESSING              => 'Processing',
            Order::STATUS_COMPLETED               => 'Completed',
            Order::STATUS_CANCELED                => 'Canceled',
            Order::STATUS_AWAITING_CONFIRMATION   => 'Awaiting Confirmation',
        ];
    }
}
