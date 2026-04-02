<?php

namespace Webkul\PushNotification\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PushNotification\Models\PushNotificationSetting;

class PushNotificationSettingRepository extends Repository
{
    public function model(): string
    {
        return PushNotificationSetting::class;
    }

    /**
     * Get all active settings for an event, optionally filtered by target.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PushNotificationSetting>
     */
    public function getActiveForEvent(string $event, ?string $target = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model
            ->where('event', $event)
            ->where('is_active', true);

        if ($target !== null) {
            $query->where(function ($q) use ($target) {
                $q->where('target', $target)->orWhere('target', 'both');
            });
        }

        return $query->get();
    }

    /**
     * Upsert settings for an event+target combination.
     */
    public function upsertForEvent(string $event, string $target, array $data): PushNotificationSetting
    {
        return $this->model->updateOrCreate(
            ['event' => $event, 'target' => $target],
            $data
        );
    }

    /**
     * Return all settings keyed by "event|target".
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PushNotificationSetting>
     */
    public function getAllKeyed(): \Illuminate\Support\Collection
    {
        return $this->model->all()->keyBy(fn ($s) => $s->event.'|'.$s->target);
    }
}
