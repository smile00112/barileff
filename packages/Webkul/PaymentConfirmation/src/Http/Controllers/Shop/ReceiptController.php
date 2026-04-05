<?php

namespace Webkul\PaymentConfirmation\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\Sales\Models\Order;

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

        // Ensure order is in pending state and uses this payment method
        abort_if($order->payment?->method !== 'paymentconfirmation', 403);
        abort_if($order->status !== Order::STATUS_PENDING, 403);

        $receipt = OrderPaymentReceipt::where('order_id', $orderId)->firstOrFail();

        $file = $request->file('receipt');
        $path = $file->store('payment-receipts/'.$orderId);

        $receipt->update([
            'receipt_path'          => $path,
            'receipt_original_name' => $file->getClientOriginalName(),
        ]);

        $order->update(['status' => Order::STATUS_AWAITING_CONFIRMATION]);

        session()->flash('success', 'Receipt uploaded successfully. Awaiting confirmation.');

        return back();
    }
}
