<x-admin::layouts>
    <x-slot:title>
        @lang('paymentconfirmation::app.admin.payment-details.edit-title')
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('paymentconfirmation::app.admin.payment-details.edit-title')
        </p>
    </div>

    <div class="box-shadow mt-4 rounded bg-white dark:bg-gray-900 p-6">
        <form method="POST" action="{{ route('admin.payment-confirmation.payment-details.update', $detail->id) }}">
            @csrf
            @method('PUT')
            @include('paymentconfirmation::admin.payment-details._form', ['inventorySources' => $inventorySources, 'detail' => $detail])

            <div class="mt-4 flex gap-3">
                <button type="submit" class="primary-button">
                    @lang('paymentconfirmation::app.admin.payment-details.update-btn')
                </button>
                <a href="{{ route('admin.payment-confirmation.payment-details.index') }}"
                   class="secondary-button">
                    @lang('paymentconfirmation::app.admin.payment-details.cancel-btn')
                </a>
            </div>
        </form>
    </div>
</x-admin::layouts>
