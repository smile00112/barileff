<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.order-statuses.create.title')
    </x-slot>

    @include('admin::settings.order-statuses._icons')

    <div class="flex items-center gap-4 max-sm:flex-wrap">
        <a
            href="{{ route('admin.settings.order_statuses.index') }}"
            class="icon-arrow-left text-2xl"
        ></a>

        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.settings.order-statuses.create.title')
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('admin.settings.order_statuses.store') }}"
        x-data="orderStatusFormData('{{ old('color', '#6b7280') }}', '{{ old('icon', '') }}')"
    >
        @csrf

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <div class="mb-4 grid grid-cols-2 gap-4">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.settings.order-statuses.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code"
                        :value="old('code')"
                    />

                    <x-admin::form.control-group.error control-name="code" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.settings.order-statuses.form.name')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="name"
                        :value="old('name')"
                    />

                    <x-admin::form.control-group.error control-name="name" />
                </x-admin::form.control-group>

                {{-- Color Picker --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.order-statuses.form.color')
                    </x-admin::form.control-group.label>

                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            value="{{ old('color', '#6b7280') }}"
                            class="h-10 w-10 cursor-pointer rounded border border-gray-300 p-0.5 dark:border-gray-700"
                            oninput="this.nextElementSibling.value = this.value"
                        />

                        <input
                            type="text"
                            name="color"
                            value="{{ old('color', '#6b7280') }}"
                            placeholder="#RRGGBB"
                            class="block w-full rounded-lg border bg-white px-3 py-2 text-sm leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            oninput="this.previousElementSibling.value = this.value"
                        />
                    </div>

                    <x-admin::form.control-group.error control-name="color" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.settings.order-statuses.form.sort-order')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="number"
                        name="sort_order"
                        :value="old('sort_order', 0)"
                        min="0"
                    />

                    <x-admin::form.control-group.error control-name="sort_order" />
                </x-admin::form.control-group>
            </div>

            {{-- Icon Picker --}}
            <div class="mb-6">
                <p class="mb-3 text-sm font-medium text-gray-800 dark:text-white">
                    @lang('admin::app.settings.order-statuses.form.icon')
                </p>

                <input type="hidden" name="icon" x-model="selectedIcon" />

                <div class="grid grid-cols-8 gap-2 sm:grid-cols-10 md:grid-cols-16" v-pre>
                    <template x-for="icon in icons" :key="icon.key">
                        <button
                            type="button"
                            @click="selectedIcon = icon.key"
                            :class="selectedIcon === icon.key
                                ? 'ring-2 ring-offset-1 bg-gray-50 dark:bg-gray-800'
                                : 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800'"
                            :style="selectedIcon === icon.key ? 'ring-color:' + color : ''"
                            class="group flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-2 transition-all duration-150 dark:border-gray-700"
                            :title="icon.label"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                :stroke="selectedIcon === icon.key ? color : '#9ca3af'"
                                class="h-6 w-6 transition-colors duration-150"
                                x-html="icon.svg"
                            ></svg>

                            <span
                                class="max-w-full truncate text-center text-xs text-gray-500 dark:text-gray-400"
                                x-text="icon.label"
                            ></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <x-admin::form.control-group class="flex items-center gap-2">
                    <x-admin::form.control-group.control
                        type="checkbox"
                        name="is_active"
                        id="is_active"
                        value="1"
                        :checked="old('is_active', true)"
                    />
                    <label for="is_active">@lang('admin::app.settings.order-statuses.form.is-active')</label>
                </x-admin::form.control-group>

                <x-admin::form.control-group class="flex items-center gap-2">
                    <x-admin::form.control-group.control
                        type="checkbox"
                        name="is_terminal"
                        id="is_terminal"
                        value="1"
                        :checked="old('is_terminal', false)"
                    />
                    <label for="is_terminal">@lang('admin::app.settings.order-statuses.form.is-terminal')</label>
                </x-admin::form.control-group>

                <x-admin::form.control-group class="flex items-center gap-2">
                    <x-admin::form.control-group.control
                        type="checkbox"
                        name="is_cancel_state"
                        id="is_cancel_state"
                        value="1"
                        :checked="old('is_cancel_state', false)"
                    />
                    <label for="is_cancel_state">@lang('admin::app.settings.order-statuses.form.is-cancel-state')</label>
                </x-admin::form.control-group>

                <x-admin::form.control-group class="flex items-center gap-2">
                    <x-admin::form.control-group.control
                        type="checkbox"
                        name="is_payment_required"
                        id="is_payment_required"
                        value="1"
                        :checked="old('is_payment_required', false)"
                    />
                    <label for="is_payment_required">@lang('admin::app.settings.order-statuses.form.is-payment-required')</label>
                </x-admin::form.control-group>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button
                type="submit"
                class="primary-button"
            >
                @lang('admin::app.settings.order-statuses.create.save-btn')
            </button>
        </div>
    </form>

    <script>
        function orderStatusFormData(initialColor, initialIcon) {
            return {
                color: initialColor || '#6b7280',
                selectedIcon: initialIcon || '',
                icons: getOrderStatusIcons(),
            };
        }
    </script>
</x-admin::layouts>
