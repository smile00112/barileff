<x-admin::layouts>
    <x-slot:title>Add Payment Detail</x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">Add Payment Detail</p>
    </div>

    <div class="box-shadow mt-4 rounded bg-white dark:bg-gray-900 p-6">
        <form method="POST" action="{{ route('admin.payment-confirmation.payment-details.store') }}">
            @csrf
            @include('paymentconfirmation::admin.payment-details._form', ['inventorySources' => $inventorySources, 'detail' => null])

            <div class="mt-4 flex gap-3">
                <button type="submit" class="primary-button">Save</button>
                <a href="{{ route('admin.payment-confirmation.payment-details.index') }}"
                   class="secondary-button">Cancel</a>
            </div>
        </form>
    </div>
</x-admin::layouts>
