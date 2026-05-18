<?php

namespace Webkul\Sales\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Contracts\OrderWorkflowSetting;

class OrderWorkflowSettingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return OrderWorkflowSetting::class;
    }

    /**
     * Get a setting value by key.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->model->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Set (upsert) a setting value.
     */
    public function setValue(string $key, mixed $value): void
    {
        $this->model->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
