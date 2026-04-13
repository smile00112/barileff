<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.delivery_zones.zones-import.title')
    </x-slot>

    <form
        action="{{ route('admin.settings.delivery_zones.import.store') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.settings.delivery_zones.zones-import.heading')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.settings.delivery_zones.index') }}" class="transparent-button">
                    @lang('admin::app.settings.delivery_zones.zones-import.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('admin::app.settings.delivery_zones.zones-import.import-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 box-shadow rounded bg-white p-4 dark:bg-gray-900">
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- File --}}
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                    @lang('admin::app.settings.delivery_zones.zones-import.file')
                </label>
                <input
                    type="file"
                    name="file"
                    accept=".json,application/json"
                    class="control w-full"
                    required
                />
            </div>

            {{-- Default city --}}
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">
                    @lang('admin::app.settings.delivery_zones.zones-import.default-city')
                </label>
                <select name="default_city_id" class="control w-full">
                    <option value="">@lang('admin::app.settings.delivery_zones.zones-import.select-city')</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('default_city_id') == $city->id)>
                            {{ $city->name }} ({{ $city->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Inventory source --}}
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                    @lang('admin::app.settings.delivery_zones.zones-import.inventory-source')
                </label>
                <select name="inventory_source_id" class="control w-full" required>
                    <option value="">@lang('admin::app.settings.delivery_zones.zones-import.select-inventory-source')</option>
                    @foreach ($inventorySources as $source)
                        <option value="{{ $source->id }}" @selected(old('inventory_source_id') == $source->id)>
                            {{ $source->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Default rate --}}
            <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.settings.delivery_zones.zones-import.default-rate')
            </p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                        @lang('admin::app.settings.delivery_zones.zones-import.min-order-total')
                    </label>
                    <input
                        type="number"
                        name="default_rate[min_order_total]"
                        step="0.01"
                        min="0"
                        value="{{ old('default_rate.min_order_total', 0) }}"
                        class="control w-full"
                        required
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                        @lang('admin::app.settings.delivery_zones.zones-import.price')
                    </label>
                    <input
                        type="number"
                        name="default_rate[price]"
                        step="0.01"
                        min="0"
                        value="{{ old('default_rate.price') }}"
                        class="control w-full"
                        required
                    />
                </div>
            </div>
        </div>
    </form>
</x-admin::layouts>
