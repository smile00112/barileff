<x-admin::layouts>
    <x-slot:title>
        Edit Delivery City
    </x-slot>

    <x-admin::form :action="route('admin.settings.delivery_cities.update', $deliveryCity->id)" method="PUT">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Edit Delivery City
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.settings.delivery_cities.index') }}" class="transparent-button">
                    Back
                </a>

                <button type="submit" class="primary-button">
                    Save
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Code</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="code" rules="required" :value="old('code', $deliveryCity->code)" />
                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Name</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="name" rules="required" :value="old('name', $deliveryCity->name)" />
                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Country</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="country" rules="required" :value="old('country', $deliveryCity->country)" />
                        <x-admin::form.control-group.error control-name="country" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>State</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="state" :value="old('state', $deliveryCity->state)" />
                        <x-admin::form.control-group.error control-name="state" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex w-[360px] max-w-full flex-col gap-2">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">Settings</p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.control type="hidden" name="is_active" value="0" />
                            <x-admin::form.control-group.control type="switch" name="is_active" value="1" :checked="(bool) old('is_active', $deliveryCity->is_active)" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
