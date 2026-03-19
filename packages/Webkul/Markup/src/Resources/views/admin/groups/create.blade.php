<x-admin::layouts>
    <x-slot:title>
        @lang('markup::app.admin.groups.create.title')
    </x-slot>

    <x-admin::form :action="route('admin.markup.groups.store')">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('markup::app.admin.groups.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.markup.groups.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('admin::app.components.layouts.header.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('markup::app.admin.groups.create.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex flex-col gap-2">
            @php $group = null; @endphp
            @include('markup::admin.groups._form')
        </div>
    </x-admin::form>
</x-admin::layouts>
