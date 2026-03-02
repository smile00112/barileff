<x-admin::layouts>
    <x-slot:title>
        Delivery Cities
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Delivery Cities
        </p>

        <a href="{{ route('admin.settings.delivery_cities.create') }}">
            <div class="primary-button">
                Add City
            </div>
        </a>
    </div>

    <x-admin::datagrid :src="route('admin.settings.delivery_cities.index')" />
</x-admin::layouts>
