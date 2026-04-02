<?php

namespace Webkul\PushNotification\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PushNotification\Models\InPageNotification;

class InPageNotificationRepository extends Repository
{
    public function model(): string
    {
        return InPageNotification::class;
    }

    /**
     * Get unread notifications for a customer.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, InPageNotification>
     */
    public function getUnreadForCustomer(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->whereNull('read_at')
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * Mark all notifications as read for a customer.
     */
    public function markAllReadForCustomer(int $customerId): void
    {
        $this->model
            ->where('customer_id', $customerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(int $id): void
    {
        $this->model
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
