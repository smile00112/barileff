<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.delivery_zones.zones-index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.settings.delivery_zones.zones-index.heading')
        </p>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.settings.delivery_zones.export') }}" download="delivery-zones.json">
                <div class="transparent-button">
                    @lang('admin::app.settings.delivery_zones.zones-index.export-zones')
                </div>
            </a>

            <a href="{{ route('admin.settings.delivery_zones.import') }}">
                <div class="transparent-button">
                    @lang('admin::app.settings.delivery_zones.zones-index.import-zones')
                </div>
            </a>

            <a href="{{ route('admin.settings.delivery_zones.create') }}">
                <div class="primary-button">
                    @lang('admin::app.settings.delivery_zones.zones-index.add-zone')
                </div>
            </a>
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.settings.delivery_zones.index')" />
</x-admin::layouts>
