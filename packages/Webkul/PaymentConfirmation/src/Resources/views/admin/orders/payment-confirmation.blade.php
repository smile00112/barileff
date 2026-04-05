@php
    $receipt = \Webkul\PaymentConfirmation\Models\OrderPaymentReceipt::where('order_id', $order->id)->first();
@endphp

@if ($receipt)
    <div class="box-shadow rounded bg-white dark:bg-gray-900 mt-4">
        <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
            <p class="text-base font-semibold text-gray-700 dark:text-white">
                Payment Confirmation
            </p>
        </div>

        <div class="p-4 space-y-4">
            {{-- Instructions snapshot --}}
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                    Instructions sent to customer
                </p>
                <div class="rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                    {{ $receipt->instructions_snapshot ?: '—' }}
                </div>
            </div>

            {{-- Receipt file --}}
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Payment Receipt</p>
                @if ($receipt->hasReceipt())
                    <a href="{{ $receipt->receipt_url }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 text-sm underline">
                        {{ $receipt->receipt_original_name ?? 'Download Receipt' }}
                    </a>
                @else
                    <span class="text-sm text-gray-400 dark:text-gray-500">Not yet uploaded.</span>
                @endif
            </div>

            {{-- Approve button --}}
            @if ($order->status === \Webkul\Sales\Models\Order::STATUS_AWAITING_CONFIRMATION && $receipt->hasReceipt())
                <form method="POST"
                      action="{{ route('admin.payment-confirmation.approve', $order->id) }}">
                    @csrf
                    <button type="submit"
                            class="primary-button"
                            onclick="return confirm('Approve this payment and move order to Processing?')">
                        Approve Payment
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif
