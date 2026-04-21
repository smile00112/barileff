<x-admin::layouts>
    <x-slot:title>
        @lang('external-payments::app.admin.inventory-source-configs.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('external-payments::app.admin.inventory-source-configs.title')
        </p>
    </div>

    <div class="box-shadow mt-3.5 rounded bg-white dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <th class="px-4 py-3">
                        @lang('external-payments::app.admin.inventory-source-configs.columns.source')
                    </th>
                    <th class="px-4 py-3">
                        @lang('external-payments::app.admin.inventory-source-configs.columns.title')
                    </th>
                    <th class="px-4 py-3">
                        @lang('external-payments::app.admin.inventory-source-configs.columns.status')
                    </th>
                    <th class="px-4 py-3">
                        @lang('external-payments::app.admin.inventory-source-configs.columns.actions')
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($inventorySources as $source)
                    @php $config = $configs->get($source->id); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $source->name }}
                            <span class="ml-1 text-xs text-gray-400">({{ $source->code }})</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $config?->title ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($config?->active)
                                <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                                    @lang('external-payments::app.admin.inventory-source-configs.status.active')
                                </span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    @lang('external-payments::app.admin.inventory-source-configs.status.inactive')
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a
                                href="{{ route('admin.external-payments.inventory-source-configs.edit', $source->id) }}"
                                class="secondary-button inline-flex px-4 py-2 text-xs"
                            >
                                @lang('external-payments::app.admin.inventory-source-configs.actions.edit')
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            @lang('external-payments::app.admin.inventory-source-configs.no-sources')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
