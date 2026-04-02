<?php

namespace Webkul\PushNotification\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PushNotification\Models\PushVapidSetting;

class PushVapidSettingRepository extends Repository
{
    public function model(): string
    {
        return PushVapidSetting::class;
    }

    public function getCurrent(): ?PushVapidSetting
    {
        return $this->model->first();
    }

    /**
     * Update the existing record or create a new one.
     */
    public function updateOrCreateSingleton(array $data): PushVapidSetting
    {
        $current = $this->getCurrent();

        if ($current !== null) {
            $current->update($data);

            return $current->fresh();
        }

        return $this->model->create($data);
    }
}
