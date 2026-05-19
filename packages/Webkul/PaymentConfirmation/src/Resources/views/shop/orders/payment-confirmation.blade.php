@php
    $receipt = \Webkul\PaymentConfirmation\Models\OrderPaymentReceipt::where('order_id', $order->id)->first();
@endphp

@if ($receipt)
    <div class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mt-6 p-5">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-3">
            @lang('paymentconfirmation::app.shop.orders.payment-confirmation.title')
        </h3>

        <div class="mb-4 rounded bg-gray-50 dark:bg-gray-800 p-4 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap border border-gray-100 dark:border-gray-700">
            {{ $receipt->instructions_snapshot }}
        </div>

        @if ($order->status === \Webkul\Sales\Models\Order::STATUS_PENDING && ! $receipt->hasReceipt())
            {{-- Upload form --}}
            <form method="POST"
                  action="{{ route('shop.payment-confirmation.upload', $order->id) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        @lang('paymentconfirmation::app.shop.orders.payment-confirmation.attach-label')
                    </label>
                    <input type="file"
                           name="receipt"
                           accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                           class="block w-full text-sm text-gray-700 dark:text-gray-300
                                  file:mr-4 file:py-2 file:px-4 file:rounded
                                  file:border-0 file:text-sm file:font-medium
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100" />
                    @error('receipt')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    @lang('paymentconfirmation::app.shop.orders.payment-confirmation.submit-btn')
                </button>
            </form>

        @elseif ($order->status === \Webkul\Sales\Models\Order::STATUS_AWAITING_CONFIRMATION)
            <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                @lang('paymentconfirmation::app.shop.orders.payment-confirmation.awaiting')
            </div>

        @elseif (in_array($order->status, [
            \Webkul\Sales\Models\Order::STATUS_PROCESSING,
            \Webkul\Sales\Models\Order::STATUS_COMPLETED,
        ]))
            <div class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                @lang('paymentconfirmation::app.shop.orders.payment-confirmation.confirmed')
            </div>
        @endif
    </div>
@endif
