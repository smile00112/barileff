<x-admin::layouts>
    <x-slot:title>
        @lang('paymentconfirmation::app.admin.payment-details.index-title')
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('paymentconfirmation::app.admin.payment-details.index-title')
        </p>

        <a href="{{ route('admin.payment-confirmation.payment-details.create') }}"
           class="primary-button">
            @lang('paymentconfirmation::app.admin.payment-details.add-btn')
        </a>
    </div>

    <div class="box-shadow rounded bg-white dark:bg-gray-900 mt-4">
        <div class="p-4">
            @if (session('success'))
                <div class="mb-4 rounded bg-green-100 dark:bg-green-800 p-3 text-sm text-green-800 dark:text-green-100">
                    {{ session('success') }}
                </div>
            @endif

            @if (blank($details))
                <p class="text-gray-500 dark:text-gray-400 py-8 text-center">
                    @lang('paymentconfirmation::app.admin.payment-details.empty')
                </p>
            @else
                <table class="w-full text-sm text-left">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">
                                @lang('paymentconfirmation::app.admin.payment-details.columns.title')
                            </th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">
                                @lang('paymentconfirmation::app.admin.payment-details.columns.inventory-source')
                            </th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">
                                @lang('paymentconfirmation::app.admin.payment-details.columns.status')
                            </th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">
                                @lang('paymentconfirmation::app.admin.payment-details.columns.actions')
                            </th>
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
                                        <span class="label-active">
                                            @lang('paymentconfirmation::app.admin.payment-details.status.active')
                                        </span>
                                    @else
                                        <span class="label-info">
                                            @lang('paymentconfirmation::app.admin.payment-details.status.inactive')
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.payment-confirmation.payment-details.edit', $detail->id) }}"
                                       class="secondary-button inline-flex px-4 py-2 text-xs">
                                        @lang('paymentconfirmation::app.admin.payment-details.actions.edit')
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.payment-confirmation.payment-details.destroy', $detail->id) }}"
                                          onsubmit="return confirm('@lang('paymentconfirmation::app.admin.payment-details.delete-confirm')')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="danger-button inline-flex px-4 py-2 text-xs">
                                            @lang('paymentconfirmation::app.admin.payment-details.actions.delete')
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-admin::layouts>
