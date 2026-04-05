<x-admin::layouts>
    <x-slot:title>
        Payment Confirmation Details
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Payment Confirmation Details
        </p>

        <a href="{{ route('admin.payment-confirmation.payment-details.create') }}"
           class="primary-button">
            Add Payment Detail
        </a>
    </div>

    <div class="box-shadow rounded bg-white dark:bg-gray-900 mt-4">
        <div class="p-4">
            @if (session('success'))
                <div class="mb-4 rounded bg-green-100 dark:bg-green-800 p-3 text-sm text-green-800 dark:text-green-100">
                    {{ session('success') }}
                </div>
            @endif

            @if ($details->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 py-8 text-center">No payment details found. Add one above.</p>
            @else
                <table class="w-full text-sm text-left">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Title</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Inventory Source</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $detail)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3 text-gray-800 dark:text-white">{{ $detail->title }}</td>
                                <td class="px-4 py-3 text-gray-800 dark:text-white">
                                    {{ $detail->inventorySource?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($detail->is_active)
                                        <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-800">Active</span>
                                    @else
                                        <span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 flex gap-3 items-center">
                                    <a href="{{ route('admin.payment-confirmation.payment-details.edit', $detail->id) }}"
                                       class="text-blue-600 dark:text-blue-400 text-sm hover:underline">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.payment-confirmation.payment-details.destroy', $detail->id) }}"
                                          onsubmit="return confirm('Delete this payment detail?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 dark:text-red-400 text-sm hover:underline">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-admin::layouts>
