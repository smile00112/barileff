<?php

namespace Webkul\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Sales\Models\OrderWorkflowSetting;

class OrderWorkflowSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'new_order_status', 'value' => 'pending'],
            ['key' => 'tab_groups', 'value' => []],
        ];

        foreach ($settings as $setting) {
            OrderWorkflowSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
