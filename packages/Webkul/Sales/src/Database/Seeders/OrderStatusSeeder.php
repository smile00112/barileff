<?php

namespace Webkul\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Sales\Models\OrderStatus;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code'                => 'pending',
                'name'                => 'Не оплачен',
                'icon'                => 'icon-pending',
                'color'               => '#f59e0b',
                'sort_order'          => 1,
                'is_system'           => true,
                'is_active'           => true,
                'is_terminal'         => false,
                'is_cancel_state'     => false,
                'is_payment_required' => false,
            ],
            [
                'code'                => 'pending_payment',
                'name'                => 'Ожидает оплаты',
                'icon'                => 'icon-pending-payment',
                'color'               => '#f97316',
                'sort_order'          => 2,
                'is_system'           => true,
                'is_active'           => true,
                'is_terminal'         => false,
                'is_cancel_state'     => false,
                'is_payment_required' => true,
            ],
            [
                'code'                => 'awaiting_confirmation',
                'name'                => 'Ожидает проверки',
                'icon'                => 'icon-info',
                'color'               => '#3b82f6',
                'sort_order'          => 3,
                'is_system'           => true,
                'is_active'           => true,
                'is_terminal'         => false,
                'is_cancel_state'     => false,
                'is_payment_required' => false,
            ],
            [
                'code'                => 'processing',
                'name'                => 'Обрабатывается',
                'icon'                => 'icon-processing',
                'color'               => '#8b5cf6',
                'sort_order'          => 4,
                'is_system'           => true,
                'is_active'           => true,
                'is_terminal'         => false,
                'is_cancel_state'     => false,
                'is_payment_required' => false,
            ],
            [
                'code'                => 'completed',
                'name'                => 'Завершен',
                'icon'                => 'icon-done',
                'color'               => '#10b981',
                'sort_order'          => 5,
                'is_system'           => true,
                'is_active'           => true,
                'is_terminal'         => true,
                'is_cancel_state'     => false,
                'is_payment_required' => false,
            ],
            [
                'code'                => 'canceled',
                'name'                => 'Отменен',
                'icon'                => 'icon-cancel',
                'color'               => '#ef4444',
                'sort_order'          => 6,
                'is_system'           => true,
                'is_active'           => true,
                'is_terminal'         => true,
                'is_cancel_state'     => true,
                'is_payment_required' => false,
            ],
            [
                'code'                => 'fraud',
                'name'                => 'Мошенничество',
                'icon'                => 'icon-alert',
                'color'               => '#dc2626',
                'sort_order'          => 8,
                'is_system'           => true,
                'is_active'           => true,
                'is_terminal'         => true,
                'is_cancel_state'     => true,
                'is_payment_required' => false,
            ],
        ];

        foreach ($statuses as $status) {
            OrderStatus::updateOrCreate(['code' => $status['code']], $status);
        }
    }
}
