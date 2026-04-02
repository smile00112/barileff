<?php

namespace Webkul\PushNotification\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PushNotification\Models\PushSubscription;

class PushSubscriptionRepository extends Repository
{
    public function model(): string
    {
        return PushSubscription::class;
    }

    /**
     * Find an existing subscription by endpoint.
     */
    public function findByEndpoint(string $endpoint): ?PushSubscription
    {
        return $this->model->where('endpoint', $endpoint)->first();
    }

    /**
     * Upsert a subscription for a subscribable entity.
     */
    public function upsertForSubscribable(
        string $subscribableType,
        int $subscribableId,
        string $endpoint,
        string $publicKey,
        string $authToken
    ): PushSubscription {
        return $this->model->updateOrCreate(
            [
                'subscribable_type' => $subscribableType,
                'subscribable_id' => $subscribableId,
                'endpoint' => $endpoint,
            ],
            [
                'public_key' => $publicKey,
                'auth_token' => $authToken,
            ]
        );
    }

    /**
     * Delete subscription by endpoint.
     */
    public function deleteByEndpoint(string $endpoint): bool
    {
        return (bool) $this->model->where('endpoint', $endpoint)->delete();
    }

    /**
     * Get all subscriptions for admin users.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PushSubscription>
     */
    public function getAdminSubscriptions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('subscribable_type', 'admin')
            ->get();
    }

    /**
     * Get all subscriptions for a specific customer.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PushSubscription>
     */
    public function getCustomerSubscriptions(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('subscribable_type', 'customer')
            ->where('subscribable_id', $customerId)
            ->get();
    }
}
