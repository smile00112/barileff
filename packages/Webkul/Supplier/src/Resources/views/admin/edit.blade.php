<x-admin::layouts>
    <x-slot:title>
        @lang('supplier::app.admin.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.suppliers.update', $supplier->id)"
        method="PUT"
        enctype="multipart/form-data"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('supplier::app.admin.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.suppliers.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('admin::app.components.layouts.header.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('supplier::app.admin.edit.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('supplier::app.admin.edit.title')
                    </p>

                    {{-- Name --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('supplier::app.admin.create.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            rules="required"
                            :value="$supplier->name"
                            :label="trans('supplier::app.admin.create.name')"
                            :placeholder="trans('supplier::app.admin.create.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    {{-- Description --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.edit.description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="description"
                            rows="3"
                            :value="$supplier->description"
                            :placeholder="trans('supplier::app.admin.edit.description')"
                        />

                        <x-admin::form.control-group.error control-name="description" />
                    </x-admin::form.control-group>

                    {{-- Image --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.edit.image')
                        </x-admin::form.control-group.label>

                        @if($supplier->image)
                            <div class="mb-2">
                                <img src="{{ Storage::url($supplier->image) }}"
                                     alt="{{ $supplier->name }}"
                                     class="w-32 h-32 object-cover rounded">
                            </div>

                            <x-admin::form.control-group.control
                                type="checkbox"
                                name="remove_image"
                                id="remove_image"
                                value="1"
                            />
                            <label for="remove_image" class="ml-1 cursor-pointer">
                                @lang('supplier::app.admin.edit.remove-image')
                            </label>
                        @endif

                        <x-admin::form.control-group.control
                            type="file"
                            name="image"
                            accept="image/*"
                        />

                        <x-admin::form.control-group.error control-name="image" />
                    </x-admin::form.control-group>

                    {{-- Sort Order --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.edit.sort-order')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="sort_order"
                            min="0"
                            :value="$supplier->sort_order ?? 0"
                            :placeholder="trans('supplier::app.admin.edit.sort-order')"
                        />

                        <x-admin::form.control-group.error control-name="sort_order" />
                    </x-admin::form.control-group>

                    {{-- Contact Name --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.create.contact-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="contact_name"
                            :value="$supplier->contact_name"
                            :placeholder="trans('supplier::app.admin.create.contact-name')"
                        />

                        <x-admin::form.control-group.error control-name="contact_name" />
                    </x-admin::form.control-group>

                    {{-- Contact Email --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.create.contact-email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="email"
                            name="contact_email"
                            :value="$supplier->contact_email"
                            :placeholder="trans('supplier::app.admin.create.contact-email')"
                        />

                        <x-admin::form.control-group.error control-name="contact_email" />
                    </x-admin::form.control-group>

                    {{-- Contact Phone --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.create.contact-phone')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="contact_phone"
                            :value="$supplier->contact_phone"
                            :placeholder="trans('supplier::app.admin.create.contact-phone')"
                        />

                        <x-admin::form.control-group.error control-name="contact_phone" />
                    </x-admin::form.control-group>

                    {{-- Address --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.create.address')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="address"
                            rows="3"
                            :value="$supplier->address"
                            :placeholder="trans('supplier::app.admin.create.address')"
                        />

                        <x-admin::form.control-group.error control-name="address" />
                    </x-admin::form.control-group>

                    {{-- Notes --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('supplier::app.admin.create.notes')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="notes"
                            rows="3"
                            :value="$supplier->notes"
                            :placeholder="trans('supplier::app.admin.create.notes')"
                        />

                        <x-admin::form.control-group.error control-name="notes" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex w-[360px] flex-col gap-2 max-xl:w-full">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('supplier::app.admin.create.status')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('supplier::app.admin.create.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="status"
                            rules="required"
                            :label="trans('supplier::app.admin.create.status')"
                        >
                            <x-admin::form.control-group.control.select-option
                                value="1"
                                :selected="$supplier->status"
                            >
                                @lang('supplier::app.admin.datagrid.active')
                            </x-admin::form.control-group.control.select-option>

                            <x-admin::form.control-group.control.select-option
                                value="0"
                                :selected="! $supplier->status"
                            >
                                @lang('supplier::app.admin.datagrid.inactive')
                            </x-admin::form.control-group.control.select-option>
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
