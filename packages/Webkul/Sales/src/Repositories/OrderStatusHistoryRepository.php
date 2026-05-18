<?php

namespace Webkul\Sales\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Contracts\OrderStatusHistory;

class OrderStatusHistoryRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return OrderStatusHistory::class;
    }

    /**
     * Return history for a given order, newest first.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forOrder(int $orderId)
    {
        return $this->model->where('order_id', $orderId)->latest('created_at')->get();
    }
}
