<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.delivery_zones.cities-index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.settings.delivery_zones.cities-index.heading')
        </p>

        <a href="{{ route('admin.settings.delivery_cities.create') }}">
            <div class="primary-button">
                @lang('admin::app.settings.delivery_zones.cities-index.add-city')
            </div>
        </a>
    </div>

    <x-admin::datagrid :src="route('admin.settings.delivery_cities.index')" />
</x-admin::layouts>
