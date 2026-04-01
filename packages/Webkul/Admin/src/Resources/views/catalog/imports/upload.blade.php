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

    <div class="box-shadow mt-3.5 rounded-sm bg-white p-6 dark:bg-gray-900">
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

            <button type="submit" class="primary-button">
                @lang('admin::app.catalog.imports.upload.submit')
            </button>
        </form>
    </div>
</x-admin::layouts>
