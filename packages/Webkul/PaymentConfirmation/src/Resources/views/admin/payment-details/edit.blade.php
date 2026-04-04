<x-admin::layouts>
    <x-slot:title>Edit Payment Detail</x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">Edit Payment Detail</p>
    </div>

    <div class="box-shadow mt-4 rounded bg-white dark:bg-gray-900 p-6">
        <form method="POST" action="{{ route('admin.payment-confirmation.payment-details.update', $detail->id) }}">
            @csrf
            @method('PUT')
            @include('paymentconfirmation::admin.payment-details._form', ['inventorySources' => $inventorySources, 'detail' => $detail])

            <div class="mt-4 flex gap-3">
                <button type="submit" class="primary-button">Update</button>
                <a href="{{ route('admin.payment-confirmation.payment-details.index') }}"
                   class="secondary-button">Cancel</a>
            </div>
        </form>
    </div>
</x-admin::layouts>
