<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.imports.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.imports.index.title')
        </p>

        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-2">
            @if (bouncer()->hasPermission('catalog.imports'))
                <button
                    type="button"
                    id="catalog-imports-mass-delete"
                    class="danger-button"
                    disabled
                >
                    @lang('admin::app.catalog.imports.index.button-delete-selected')
                </button>

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
                        @if (bouncer()->hasPermission('catalog.imports'))
                            <th class="w-10 px-4 py-3 text-left font-medium">
                                <label class="inline-flex cursor-pointer items-center">
                                    <input
                                        type="checkbox"
                                        id="catalog-imports-select-all"
                                        class="peer sr-only"
                                    >
                                    <span
                                        class="icon-uncheckbox text-2xl text-gray-500 peer-checked:icon-checked peer-checked:text-blue-600 dark:text-gray-400"
                                    ></span>
                                    <span class="sr-only">@lang('admin::app.catalog.imports.index.button-delete-selected')</span>
                                </label>
                            </th>
                        @endif
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
                            @if (bouncer()->hasPermission('catalog.imports'))
                                <td class="px-4 py-3">
                                    <label class="inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            name="catalog_import_ids[]"
                                            value="{{ $session->id }}"
                                            class="catalog-import-row-checkbox peer sr-only"
                                        >
                                        <span
                                            class="icon-uncheckbox text-2xl text-gray-500 peer-checked:icon-checked peer-checked:text-blue-600 dark:text-gray-400"
                                        ></span>
                                    </label>
                                </td>
                            @endif
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
                                <div class="flex flex-wrap items-center gap-3">
                                    <a
                                        href="{{ route('admin.catalog.imports.show', $session->id) }}"
                                        class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                    >
                                        <span class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center icon-edit"></span>
                                    </a>

                                    @if (bouncer()->hasPermission('catalog.imports'))
                                        <button
                                            type="button"
                                            class="cursor-pointer inline-flex items-center p-1.5 gap-1 text-rose-600 hover:bg-gray-200 dark:hover:bg-gray-800"
                                            data-catalog-import-delete="{{ route('admin.catalog.imports.delete', $session->id) }}"
                                            data-catalog-import-confirm="{{ __('admin::app.catalog.imports.index.delete-confirm') }}"
                                            title="{{ __('admin::app.catalog.imports.index.columns.delete') }}"
                                        >
                                            <span class="icon-delete text-xl"></span>
                                            <span class="sr-only">@lang('admin::app.catalog.imports.index.columns.delete')</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ bouncer()->hasPermission('catalog.imports') ? 7 : 6 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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

    @if (bouncer()->hasPermission('catalog.imports'))
        @push('scripts')
            <script>
                (function () {
                    const massDeleteUrl = @json(route('admin.catalog.imports.mass_delete'));
                    const massConfirm = @json(__('admin::app.catalog.imports.index.confirm-mass-delete'));

                    const rowChecks = () =>
                        Array.from(document.querySelectorAll('.catalog-import-row-checkbox'));

                    function syncMassButton() {
                        const checked = rowChecks().filter((el) => el.checked);
                        const massBtn = document.getElementById('catalog-imports-mass-delete');

                        if (massBtn) {
                            massBtn.disabled = checked.length === 0;
                        }

                        const selectAll = document.getElementById('catalog-imports-select-all');

                        if (selectAll) {
                            const boxes = rowChecks();

                            if (boxes.length === 0) {
                                selectAll.checked = false;
                                selectAll.indeterminate = false;
                            } else if (checked.length === boxes.length) {
                                selectAll.checked = true;
                                selectAll.indeterminate = false;
                            } else if (checked.length === 0) {
                                selectAll.checked = false;
                                selectAll.indeterminate = false;
                            } else {
                                selectAll.checked = false;
                                selectAll.indeterminate = true;
                            }
                        }
                    }

                    document.addEventListener('change', function (e) {
                        const target = e.target;

                        if (target && target.id === 'catalog-imports-select-all') {
                            const on = target.checked;

                            rowChecks().forEach((el) => {
                                el.checked = on;
                            });

                            syncMassButton();

                            return;
                        }

                        if (target && target.classList && target.classList.contains('catalog-import-row-checkbox')) {
                            syncMassButton();
                        }
                    });

                    document.addEventListener('click', async function (e) {
                        const deleteBtn = e.target.closest('[data-catalog-import-delete]');

                        if (deleteBtn) {
                            const url = deleteBtn.getAttribute('data-catalog-import-delete');
                            const msg = deleteBtn.getAttribute('data-catalog-import-confirm');

                            if (! url || ! window.confirm(msg)) {
                                return;
                            }

                            try {
                                await window.axios.delete(url);

                                window.location.reload();
                            } catch (err) {
                                const message =
                                    err.response?.data?.message ||
                                    @json(__('admin::app.catalog.imports.index.delete-failed'));

                                window.alert(message);
                            }

                            return;
                        }

                        const massBtn = e.target.closest('#catalog-imports-mass-delete');

                        if (! massBtn) {
                            return;
                        }

                        const indices = rowChecks()
                            .filter((el) => el.checked)
                            .map((el) => parseInt(el.value, 10))
                            .filter((id) => ! Number.isNaN(id));

                        if (indices.length === 0) {
                            return;
                        }

                        if (! window.confirm(massConfirm)) {
                            return;
                        }

                        try {
                            await window.axios.post(massDeleteUrl, { indices });

                            window.location.reload();
                        } catch (err) {
                            const message =
                                err.response?.data?.message ||
                                @json(__('admin::app.catalog.imports.index.delete-failed'));

                            window.alert(message);
                        }
                    });

                    window.addEventListener('load', function () {
                        syncMassButton();
                    });
                })();
            </script>
        @endpush
    @endif
</x-admin::layouts>
