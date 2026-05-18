<?php

namespace Webkul\Sales\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Contracts\OrderStatus;

class OrderStatusRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return OrderStatus::class;
    }

    /**
     * Return all active statuses, ordered.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Find a status by its code.
     */
    public function findByCode(string $code): ?\Webkul\Sales\Contracts\OrderStatus
    {
        return $this->model->where('code', $code)->first();
    }
}
