<x-admin::layouts>
    <x-slot:title>
        @lang('menu::app.admin.menus.create')
    </x-slot>

    <x-admin::form :action="route('admin.menu.menus.store')" method="POST">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">@lang('menu::app.admin.menus.create')</p>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.menu.menus.index') }}" class="secondary-button">
                    @lang('menu::app.admin.common.back')
                </a>
                <button type="submit" class="primary-button">@lang('menu::app.admin.common.save')</button>
            </div>
        </div>

        @include('menu::admin.menus._form')
    </x-admin::form>
</x-admin::layouts>
