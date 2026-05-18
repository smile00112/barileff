<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Contracts\OrderStatusTransition;

class OrderStatusTransitionRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return OrderStatusTransition::class;
    }

    /**
     * Return active transitions for the given from-status, optionally
     * filtered by delivery_type, payment_type and channel.
     */
    public function getForContext(
        string $fromStatusCode,
        ?string $deliveryType = null,
        ?string $paymentType = null,
        ?string $channel = null
    ): Collection {
        return $this->model
            ->where('from_status_code', $fromStatusCode)
            ->where('is_active', true)
            ->where(function ($q) use ($deliveryType) {
                $q->whereNull('delivery_type')->orWhere('delivery_type', $deliveryType);
            })
            ->where(function ($q) use ($paymentType) {
                $q->whereNull('payment_type')->orWhere('payment_type', $paymentType);
            })
            ->where(function ($q) use ($channel) {
                $q->whereNull('channel')->orWhere('channel', $channel);
            })
            ->orderBy('priority')
            ->get();
    }
}
