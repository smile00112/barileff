<x-admin::layouts>
    <x-slot:title>
        @lang('menu::app.admin.menus.edit')
    </x-slot>

    <x-admin::form :action="route('admin.menu.menus.update', $menu->id)" method="PUT">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">@lang('menu::app.admin.menus.edit')</p>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.menu.items.index', $menu->id) }}" class="secondary-button">
                    @lang('menu::app.admin.menus.actions.items')
                </a>
                <button type="submit" class="primary-button">@lang('menu::app.admin.common.save')</button>
            </div>
        </div>

        @include('menu::admin.menus._form')
    </x-admin::form>
</x-admin::layouts>
