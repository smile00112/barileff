<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.imports.upload.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.imports.upload.title')
        </p>

        <a
            href="{{ route('admin.catalog.imports.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            @lang('admin::app.catalog.imports.upload.back-btn')
        </a>
    </div>

    <div class="box-shadow mt-3.5 rounded-sm bg-white p-4 dark:bg-gray-900">
        <form
            method="POST"
            action="{{ route('admin.catalog.imports.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            <!-- CSV File -->
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    @lang('admin::app.catalog.imports.upload.fields.file')
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="file"
                    name="file"
                    accept=".csv,.txt"
                    required
                    class="block w-full rounded-sm border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:outline-none focus:border-indigo-500"
                />

                @error('file')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('admin::app.catalog.imports.upload.fields.file-hint')
                </p>
            </div>

            <!-- Delimiter -->
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    @lang('admin::app.catalog.imports.upload.fields.delimiter')
                    <span class="text-red-500">*</span>
                </label>

                <div class="flex flex-wrap gap-4">
                    @foreach ([
                        'comma'     => trans('admin::app.catalog.imports.upload.delimiters.comma'),
                        'semicolon' => trans('admin::app.catalog.imports.upload.delimiters.semicolon'),
                        'tab'       => trans('admin::app.catalog.imports.upload.delimiters.tab'),
                        'pipe'      => trans('admin::app.catalog.imports.upload.delimiters.pipe'),
                    ] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="radio"
                                name="delimiter"
                                value="{{ $value }}"
                                {{ old('delimiter', 'comma') === $value ? 'checked' : '' }}
                                class="accent-indigo-600"
                            />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                @error('delimiter')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Locale -->
            <div class="mb-6">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    @lang('admin::app.catalog.imports.upload.fields.locale')
                    <span class="text-red-500">*</span>
                </label>

                <select
                    name="locale"
                    class="block w-full max-w-xs rounded-sm border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:outline-none focus:border-indigo-500"
                >
                    @foreach ($locales as $locale)
                        <option
                            value="{{ $locale->code }}"
                            {{ old('locale', app()->getLocale()) === $locale->code ? 'selected' : '' }}
                        >
                            {{ $locale->name }} ({{ $locale->code }})
                        </option>
                    @endforeach
                </select>

                @error('locale')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('admin::app.catalog.imports.upload.fields.locale-hint')
                </p>
            </div>

            <!-- Import Options -->
            <div class="mb-6 rounded-sm border border-gray-200 p-4 dark:border-gray-700">
                <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                    @lang('admin::app.catalog.imports.upload.fields.options-title')
                </p>

                <!-- allow_insert -->
                <label class="mb-3 flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        name="allow_insert"
                        value="1"
                        {{ old('allow_insert', '1') ? 'checked' : '' }}
                        class="mt-0.5 accent-indigo-600"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        @lang('admin::app.catalog.imports.upload.fields.allow-insert')
                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                            @lang('admin::app.catalog.imports.upload.fields.allow-insert-hint')
                        </span>
                    </span>
                </label>

                <!-- allow_update -->
                <label class="mb-3 flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        name="allow_update"
                        value="1"
                        {{ old('allow_update', '1') ? 'checked' : '' }}
                        class="mt-0.5 accent-indigo-600"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        @lang('admin::app.catalog.imports.upload.fields.allow-update')
                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                            @lang('admin::app.catalog.imports.upload.fields.allow-update-hint')
                        </span>
                    </span>
                </label>

                <!-- new_products_active -->
                <label class="mb-3 flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        name="new_products_active"
                        value="1"
                        {{ old('new_products_active', '1') ? 'checked' : '' }}
                        class="mt-0.5 accent-indigo-600"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        @lang('admin::app.catalog.imports.upload.fields.new-products-active')
                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                            @lang('admin::app.catalog.imports.upload.fields.new-products-active-hint')
                        </span>
                    </span>
                </label>

                <!-- new_products_in_stock -->
                <label class="mb-3 flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        name="new_products_in_stock"
                        value="1"
                        {{ old('new_products_in_stock', '1') ? 'checked' : '' }}
                        class="mt-0.5 accent-indigo-600"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        @lang('admin::app.catalog.imports.upload.fields.new-products-in-stock')
                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                            @lang('admin::app.catalog.imports.upload.fields.new-products-in-stock-hint')
                        </span>
                    </span>
                </label>

                <!-- create_categories -->
                <label
                    class="mb-2 flex cursor-pointer items-start gap-3"
                    x-data="{ createCategories: {{ old('create_categories') ? 'true' : 'false' }} }"
                >
                    <input
                        type="checkbox"
                        name="create_categories"
                        value="1"
                        x-model="createCategories"
                        {{ old('create_categories') ? 'checked' : '' }}
                        class="mt-0.5 accent-indigo-600"
                    />
                    <span class="w-full text-sm text-gray-700 dark:text-gray-300">
                        @lang('admin::app.catalog.imports.upload.fields.create-categories')
                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                            @lang('admin::app.catalog.imports.upload.fields.create-categories-hint')
                        </span>

                        <!-- parent category select, shown only when create_categories is checked -->
                        <div x-show="createCategories" x-cloak class="mt-2">
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::app.catalog.imports.upload.fields.parent-category')
                            </label>

                            <select
                                name="parent_category_id"
                                class="block w-full max-w-xs rounded-sm border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:outline-none focus:border-indigo-500"
                            >
                                @foreach ($parentCategoryOptions as $cat)
                                    <option
                                        value="{{ $cat['id'] }}"
                                        {{ (int) old('parent_category_id', 1) === $cat['id'] ? 'selected' : '' }}
                                    >
                                        {{ $cat['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                @lang('admin::app.catalog.imports.upload.fields.parent-category-hint')
                            </p>

                            @error('parent_category_id')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </span>
                </label>
            </div>

            <button type="submit" class="primary-button">
                @lang('admin::app.catalog.imports.upload.submit')
            </button>
        </form>
    </div>
</x-admin::layouts>
