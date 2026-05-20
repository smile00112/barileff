<?php

namespace Webkul\PaymentConfirmation\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Services\OrderStatusTransitionService;
use Webkul\Sales\Services\TransitionContext;

class ReceiptController extends Controller
{
    public function upload(Request $request, int $orderId): RedirectResponse
    {
        $request->validate([
            'receipt' => 'required|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf|max:10240',
        ]);

        $order = Order::findOrFail($orderId);

        // Ensure the order belongs to the authenticated customer
        abort_if($order->customer_id !== auth('customer')->id(), 403);

        // Ensure order uses this payment method
        abort_if($order->payment?->method !== 'paymentconfirmation', 403);

        // Allow upload/re-upload for pending and awaiting-confirmation statuses
        abort_if(
            ! in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_AWAITING_CONFIRMATION], true),
            403
        );

        $receipt = OrderPaymentReceipt::where('order_id', $orderId)->firstOrFail();

        // Delete old file if replacing an existing receipt
        if ($receipt->receipt_path) {
            \Illuminate\Support\Facades\Storage::delete($receipt->receipt_path);
        }

        $file = $request->file('receipt');
        $path = $file->store('payment-receipts/'.$orderId);

        $receipt->update([
            'receipt_path' => $path,
            'receipt_original_name' => $file->getClientOriginalName(),
        ]);

        // Transition to awaiting-confirmation only from pending
        if ($order->status === Order::STATUS_PENDING) {
            app(OrderStatusTransitionService::class)->transition(
                $order,
                Order::STATUS_AWAITING_CONFIRMATION,
                TransitionContext::forSystem('payment-receipt-upload')
            );
        }

        session()->flash('success', 'Receipt uploaded successfully. Awaiting confirmation.');

        return back();
    }
}
