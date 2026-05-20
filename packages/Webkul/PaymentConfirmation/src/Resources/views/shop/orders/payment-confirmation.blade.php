@php
    $receipt = \Webkul\PaymentConfirmation\Models\OrderPaymentReceipt::where('order_id', $order->id)->first();
    $canUpload = $receipt && in_array($order->status, [
        \Webkul\Sales\Models\Order::STATUS_PENDING,
        \Webkul\Sales\Models\Order::STATUS_AWAITING_CONFIRMATION,
    ], true);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $isImage = $receipt && $receipt->receipt_path
        ? in_array(strtolower(pathinfo($receipt->receipt_path, PATHINFO_EXTENSION)), $imageExtensions)
        : false;
@endphp

@if ($receipt)
    <div class="rounded border border-gray-200 bg-white mt-6 p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-3">
            @lang('paymentconfirmation::app.shop.orders.payment-confirmation.title')
        </h3>

        <div class="mb-4 rounded bg-gray-50 p-4 text-sm text-gray-700 whitespace-pre-wrap border border-gray-100">
            {{ $receipt->instructions_snapshot }}
        </div>

        {{-- Preview of already uploaded receipt --}}
        @if ($receipt->hasReceipt())
            <div class="mb-4">
                <p class="text-sm font-medium text-gray-700 mb-2">
                    @lang('paymentconfirmation::app.shop.orders.payment-confirmation.receipt-preview')
                </p>
                @if ($isImage)
                    <a href="{{ $receipt->receipt_url }}" target="_blank">
                        <img
                            src="{{ $receipt->receipt_url }}"
                            alt="{{ $receipt->receipt_original_name }}"
                            class="max-h-56 max-w-full rounded border border-gray-200 object-contain cursor-pointer hover:opacity-90 transition-opacity"
                        >
                    </a>
                    <div class="mt-1">
                        <a href="{{ $receipt->receipt_url }}"
                           target="_blank"
                           class="text-blue-600 text-sm underline">
                            {{ $receipt->receipt_original_name }}
                        </a>
                    </div>
                @else
                    <a href="{{ $receipt->receipt_url }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 text-blue-600 text-sm underline">
                        {{ $receipt->receipt_original_name ?? __('paymentconfirmation::app.shop.orders.payment-confirmation.receipt-preview') }}
                    </a>
                @endif
            </div>
        @endif

        {{-- Upload / re-upload form --}}
        @if ($canUpload)
            <form method="POST"
                  action="{{ route('shop.payment-confirmation.upload', $order->id) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        @if ($receipt->hasReceipt())
                            @lang('paymentconfirmation::app.shop.orders.payment-confirmation.re-upload-label')
                        @else
                            @lang('paymentconfirmation::app.shop.orders.payment-confirmation.attach-label')
                        @endif
                    </label>
                    <input type="file"
                           name="receipt"
                           accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                           class="block w-full text-sm text-gray-700
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
                    @if ($receipt->hasReceipt())
                        @lang('paymentconfirmation::app.shop.orders.payment-confirmation.re-upload-btn')
                    @else
                        @lang('paymentconfirmation::app.shop.orders.payment-confirmation.submit-btn')
                    @endif
                </button>
            </form>
        @endif

        {{-- Status messages --}}
        @if ($order->status === \Webkul\Sales\Models\Order::STATUS_AWAITING_CONFIRMATION)
            <div class="flex items-center gap-2 text-yellow-600 text-sm mt-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                @lang('paymentconfirmation::app.shop.orders.payment-confirmation.awaiting')
            </div>

        @elseif (in_array($order->status, [
            \Webkul\Sales\Models\Order::STATUS_PROCESSING,
            \Webkul\Sales\Models\Order::STATUS_COMPLETED,
        ]))
            <div class="flex items-center gap-2 text-green-600 text-sm mt-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                @lang('paymentconfirmation::app.shop.orders.payment-confirmation.confirmed')
            </div>
        @endif
    </div>
@endif
