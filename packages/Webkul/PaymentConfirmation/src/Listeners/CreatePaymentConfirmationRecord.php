<?php

namespace Webkul\PaymentConfirmation\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\PaymentConfirmation\Models\PaymentDetail;
use Webkul\Sales\Models\Order;

class CreatePaymentConfirmationRecord
{
    public function handle(Order $order): void
    {
        if ($order->payment?->method !== 'paymentconfirmation') {
            return;
        }

        try {
            // Already created (e.g. retry scenario)
            if (OrderPaymentReceipt::where('order_id', $order->id)->exists()) {
                return;
            }

            $detail = $this->selectDetail($order);

            OrderPaymentReceipt::create([
                'order_id' => $order->id,
                'payment_detail_id' => $detail?->id,
                'instructions_snapshot' => $detail?->instructions ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('CreatePaymentConfirmationRecord: failed to create receipt', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function selectDetail(Order $order): ?PaymentDetail
    {
        // Get product IDs from the order items
        $productIds = $order->items->pluck('product_id')->filter()->unique();

        // Find inventory source IDs for those products
        $sourceIds = DB::table('product_inventories')
            ->whereIn('product_id', $productIds)
            ->pluck('inventory_source_id')
            ->unique();

        // Try to find a matching active detail
        $detail = PaymentDetail::where('is_active', true)
            ->whereIn('inventory_source_id', $sourceIds)
            ->inRandomOrder()
            ->first();

        // Fallback: any active detail
        return $detail ?? PaymentDetail::where('is_active', true)->inRandomOrder()->first();
    }
}
