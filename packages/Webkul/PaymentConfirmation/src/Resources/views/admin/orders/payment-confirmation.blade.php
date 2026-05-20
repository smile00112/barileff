@php
    $receipt = \Webkul\PaymentConfirmation\Models\OrderPaymentReceipt::where('order_id', $order->id)->first();
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $isImage = $receipt && $receipt->receipt_path
        ? in_array(strtolower(pathinfo($receipt->receipt_path, PATHINFO_EXTENSION)), $imageExtensions)
        : false;
@endphp

@if ($receipt)
    <div class="box-shadow rounded bg-white dark:bg-gray-900 mt-4">
        <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
            <p class="text-base font-semibold text-gray-700 dark:text-white">
                @lang('paymentconfirmation::app.admin.orders.payment-confirmation.title')
            </p>
        </div>

        <div class="p-4 space-y-4">
            {{-- Instructions snapshot --}}
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                    @lang('paymentconfirmation::app.admin.orders.payment-confirmation.instructions-sent')
                </p>
                <div class="rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                    {{ $receipt->instructions_snapshot ?: '—' }}
                </div>
            </div>

            {{-- Receipt file --}}
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                    @lang('paymentconfirmation::app.admin.orders.payment-confirmation.receipt')
                </p>
                @if ($receipt->hasReceipt())
                    @if ($isImage)
                        <a href="{{ $receipt->receipt_url }}" target="_blank">
                            <img
                                src="{{ $receipt->receipt_url }}"
                                alt="{{ $receipt->receipt_original_name }}"
                                class="max-h-64 max-w-full rounded border border-gray-200 dark:border-gray-700 object-contain cursor-pointer hover:opacity-90 transition-opacity"
                            >
                        </a>
                        <div class="mt-1">
                            <a href="{{ $receipt->receipt_url }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 text-sm underline">
                                {{ $receipt->receipt_original_name ?? __('paymentconfirmation::app.admin.orders.payment-confirmation.download') }}
                            </a>
                        </div>
                    @else
                        <a href="{{ $receipt->receipt_url }}"
                           target="_blank"
                           class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 text-sm underline">
                            {{ $receipt->receipt_original_name ?? __('paymentconfirmation::app.admin.orders.payment-confirmation.download') }}
                        </a>
                    @endif
                @else
                    <span class="text-sm text-gray-400 dark:text-gray-500">
                        @lang('paymentconfirmation::app.admin.orders.payment-confirmation.not-uploaded')
                    </span>
                @endif
            </div>

            {{-- Approve button --}}
            @if ($order->status === \Webkul\Sales\Models\Order::STATUS_AWAITING_CONFIRMATION && $receipt->hasReceipt())
                <form method="POST"
                      action="{{ route('admin.payment-confirmation.approve', $order->id) }}">
                    @csrf
                    <button type="submit"
                            class="primary-button"
                            onclick="return confirm('{{ __('paymentconfirmation::app.admin.orders.payment-confirmation.approve-confirm') }}')">
                        @lang('paymentconfirmation::app.admin.orders.payment-confirmation.approve-btn')
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif
