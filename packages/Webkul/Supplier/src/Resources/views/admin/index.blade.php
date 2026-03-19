<x-admin::layouts>
    <x-slot:title>
        @lang('supplier::app.admin.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('supplier::app.admin.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.suppliers.create') }}">
                <div class="primary-button">
                    @lang('supplier::app.admin.index.create-btn')
                </div>
            </a>
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.suppliers.index')" />
</x-admin::layouts>
