<x-admin::layouts>
    <x-slot:title>
        Delivery Zones
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Delivery Zones
        </p>

        <a href="{{ route('admin.settings.delivery_zones.create') }}">
            <div class="primary-button">
                Add Zone
            </div>
        </a>
    </div>

    <x-admin::datagrid :src="route('admin.settings.delivery_zones.index')" />
</x-admin::layouts>
