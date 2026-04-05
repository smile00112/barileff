<?php

namespace Webkul\PaymentConfirmation\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\Sales\Models\Order;

class OrderReceiptController extends Controller
{
    public function approve(int $orderId): RedirectResponse
    {
        $order = Order::findOrFail($orderId);

        abort_if(
            $order->status !== Order::STATUS_AWAITING_CONFIRMATION,
            403,
            'Order is not awaiting confirmation.'
        );

        $receipt = OrderPaymentReceipt::where('order_id', $orderId)->firstOrFail();

        abort_if(! $receipt->hasReceipt(), 403, 'No receipt uploaded yet.');

        $order->update(['status' => Order::STATUS_PROCESSING]);

        session()->flash('success', 'Payment approved. Order is now processing.');

        return back();
    }
}
