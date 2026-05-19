<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.order-statuses.create.title')
    </x-slot>

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

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.order-statuses.form.color')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="color"
                        :value="old('color')"
                        placeholder="#RRGGBB"
                    />

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
</x-admin::layouts>
