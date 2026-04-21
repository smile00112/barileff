<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.imports.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.settings.data-transfer.imports.index.title')
        </p>

        <div class="flex items-center gap-x-2.5"></div>
    </div>

    <x-admin::datagrid :src="route('admin.settings.data_transfer.imports.index')"/>
</x-admin::layouts>
