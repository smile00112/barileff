<x-admin::layouts>
    <x-slot:title>
        @lang('markup::app.admin.groups.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('markup::app.admin.groups.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.markup.groups.create') }}">
                <div class="primary-button">
                    @lang('markup::app.admin.groups.index.create-btn')
                </div>
            </a>
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.markup.groups.index')" />
</x-admin::layouts>
