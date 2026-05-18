<?php

namespace Webkul\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Sales\Models\OrderStatusTransition;

class OrderStatusTransitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transitions = [
            // From pending
            ['from_status_code' => 'pending', 'to_status_code' => 'processing'],
            ['from_status_code' => 'pending', 'to_status_code' => 'canceled'],
            ['from_status_code' => 'pending', 'to_status_code' => 'awaiting_confirmation'],
            ['from_status_code' => 'pending', 'to_status_code' => 'fraud'],
            // From pending_payment
            ['from_status_code' => 'pending_payment', 'to_status_code' => 'processing'],
            ['from_status_code' => 'pending_payment', 'to_status_code' => 'pending'],
            ['from_status_code' => 'pending_payment', 'to_status_code' => 'canceled'],
            ['from_status_code' => 'pending_payment', 'to_status_code' => 'fraud'],
            // From awaiting_confirmation
            ['from_status_code' => 'awaiting_confirmation', 'to_status_code' => 'processing'],
            ['from_status_code' => 'awaiting_confirmation', 'to_status_code' => 'canceled'],
            // From processing
            ['from_status_code' => 'processing', 'to_status_code' => 'completed'],
            ['from_status_code' => 'processing', 'to_status_code' => 'canceled'],
            ['from_status_code' => 'processing', 'to_status_code' => 'closed'],
            // From completed (allow closing after refund)
            ['from_status_code' => 'completed', 'to_status_code' => 'closed'],
        ];

        foreach ($transitions as $transition) {
            OrderStatusTransition::updateOrCreate(
                [
                    'from_status_code' => $transition['from_status_code'],
                    'to_status_code'   => $transition['to_status_code'],
                    'delivery_type'    => null,
                    'payment_type'     => null,
                    'channel'          => null,
                ],
                array_merge($transition, ['is_active' => true, 'priority' => 100])
            );
        }
    }
}
