<x-admin::layouts>
    <x-slot:title>
        @lang('menu::app.admin.menus.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('menu::app.admin.menus.title')
        </p>

        @if (bouncer()->hasPermission('menu.menus.create'))
            <a href="{{ route('admin.menu.menus.create') }}" class="primary-button">
                @lang('menu::app.admin.common.create')
            </a>
        @endif
    </div>

    <x-admin::datagrid :src="route('admin.menu.menus.index')" />
</x-admin::layouts>
