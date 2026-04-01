<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.imports.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.imports.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('catalog.imports'))
                <a
                    href="{{ route('admin.catalog.imports.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.catalog.imports.index.button-new')
                </a>
            @endif
        </div>
    </div>

    <div class="box-shadow mt-3.5 rounded-sm bg-white dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm text-gray-600 dark:text-gray-300">
                <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">#</th>
                        <th class="px-4 py-3 text-left font-medium">@lang('admin::app.catalog.imports.index.columns.file')</th>
                        <th class="px-4 py-3 text-left font-medium">@lang('admin::app.catalog.imports.index.columns.locale')</th>
                        <th class="px-4 py-3 text-left font-medium">@lang('admin::app.catalog.imports.index.columns.state')</th>
                        <th class="px-4 py-3 text-left font-medium">@lang('admin::app.catalog.imports.index.columns.date')</th>
                        <th class="px-4 py-3 text-left font-medium">@lang('admin::app.catalog.imports.index.columns.actions')</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($sessions as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3">{{ $session->id }}</td>

                            <td class="px-4 py-3">{{ $session->file_name }}</td>

                            <td class="px-4 py-3">{{ strtoupper($session->locale) }}</td>

                            <td class="px-4 py-3">
                                @php
                                    $stateColors = [
                                        'pending'    => 'bg-yellow-100 text-yellow-700',
                                        'ready'      => 'bg-blue-100 text-blue-700',
                                        'processing' => 'bg-indigo-100 text-indigo-700',
                                        'completed'  => 'bg-green-100 text-green-700',
                                        'failed'     => 'bg-red-100 text-red-700',
                                    ];
                                @endphp

                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stateColors[$session->state] ?? 'bg-gray-100 text-gray-700' }}">
                                    @lang('admin::app.catalog.imports.states.' . $session->state)
                                </span>
                            </td>

                            <td class="px-4 py-3">{{ $session->created_at->format('d.m.Y H:i') }}</td>

                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('admin.catalog.imports.show', $session->id) }}"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                >
                                    @lang('admin::app.catalog.imports.index.columns.view')
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                @lang('admin::app.catalog.imports.index.no-records')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sessions->hasPages())
            <div class="p-4">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</x-admin::layouts>
