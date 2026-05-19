<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.order-statuses.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.settings.order-statuses.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('settings.order_statuses.create'))
                <a
                    href="{{ route('admin.settings.order_statuses.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.settings.order-statuses.index.create-btn')
                </a>
            @endif

            <a
                href="{{ route('admin.settings.order_statuses.workflow') }}"
                class="secondary-button"
            >
                @lang('admin::app.settings.order-statuses.workflow.title')
            </a>
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.settings.order_statuses.index')" />
</x-admin::layouts>
