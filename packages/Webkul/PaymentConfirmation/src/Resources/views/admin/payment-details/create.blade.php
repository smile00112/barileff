<x-admin::layouts>
    <x-slot:title>
        @lang('paymentconfirmation::app.admin.payment-details.create-title')
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('paymentconfirmation::app.admin.payment-details.create-title')
        </p>
    </div>

    <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <form method="POST" action="{{ route('admin.payment-confirmation.payment-details.store') }}">
            @csrf
            @include('paymentconfirmation::admin.payment-details._form', ['inventorySources' => $inventorySources, 'detail' => null])

            <div class="mt-4 flex gap-3">
                <button type="submit" class="primary-button">
                    @lang('paymentconfirmation::app.admin.payment-details.save-btn')
                </button>
                <a href="{{ route('admin.payment-confirmation.payment-details.index') }}"
                   class="secondary-button">
                    @lang('paymentconfirmation::app.admin.payment-details.cancel-btn')
                </a>
            </div>
        </form>
            </div>
        </div>
    </div>
</x-admin::layouts>
