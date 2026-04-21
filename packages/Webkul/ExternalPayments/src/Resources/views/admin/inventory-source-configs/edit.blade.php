<x-admin::layouts>
    <x-slot:title>
        @lang('external-payments::app.admin.inventory-source-configs.edit-title', ['source' => $inventorySource->name])
    </x-slot>

    <x-admin::form
        :action="route('admin.external-payments.inventory-source-configs.update', $inventorySource->id)"
        method="PUT"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('external-payments::app.admin.inventory-source-configs.edit-title', ['source' => $inventorySource->name])
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.external-payments.inventory-source-configs.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:hover:bg-gray-800"
                >
                    @lang('admin::app.components.layouts.header.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('external-payments::app.admin.inventory-source-configs.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">

                {{-- Main settings --}}
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('external-payments::app.admin.inventory-source-configs.sections.general')
                    </p>

                    {{-- Active --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('external-payments::app.admin.inventory-source-configs.fields.active')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="active"
                            :value="1"
                            :checked="$config?->active ?? false"
                        />
                    </x-admin::form.control-group>

                    {{-- Title --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('external-payments::app.admin.inventory-source-configs.fields.title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="title"
                            :value="old('title', $config?->title)"
                            :placeholder="trans('external-payments::app.admin.inventory-source-configs.fields.title')"
                        />

                        <x-admin::form.control-group.error control-name="title" />
                    </x-admin::form.control-group>

                    {{-- Description --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('external-payments::app.admin.inventory-source-configs.fields.description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="description"
                            rows="3"
                            :value="old('description', $config?->description)"
                        />

                        <x-admin::form.control-group.error control-name="description" />
                    </x-admin::form.control-group>
                </div>

                {{-- API settings --}}
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('external-payments::app.admin.inventory-source-configs.sections.api')
                    </p>

                    {{-- API Server URL --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('external-payments::app.admin.inventory-source-configs.fields.api-server-url')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="api_server_url"
                            :value="old('api_server_url', $config?->api_server_url)"
                            placeholder="https://payment.example.com"
                        />

                        <x-admin::form.control-group.error control-name="api_server_url" />
                    </x-admin::form.control-group>

                    {{-- API Token --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('external-payments::app.admin.inventory-source-configs.fields.api-token')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="api_token"
                            :value="old('api_token', $config?->api_token)"
                        />

                        <x-admin::form.control-group.error control-name="api_token" />
                    </x-admin::form.control-group>

                    {{-- Paid Order Status --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('external-payments::app.admin.inventory-source-configs.fields.paid-order-status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="paid_order_status"
                            :value="old('paid_order_status', $config?->paid_order_status ?? 'processing')"
                        >
                            <option value="processing" @selected(($config?->paid_order_status ?? 'processing') === 'processing')>
                                @lang('external-payments::app.admin.inventory-source-configs.paid-status.processing')
                            </option>
                            <option value="completed" @selected(($config?->paid_order_status ?? '') === 'completed')>
                                @lang('external-payments::app.admin.inventory-source-configs.paid-status.completed')
                            </option>
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="paid_order_status" />
                    </x-admin::form.control-group>
                </div>

            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
