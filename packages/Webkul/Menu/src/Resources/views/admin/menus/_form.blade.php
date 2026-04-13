<div class="mt-3.5 box-shadow rounded bg-white p-4 dark:bg-gray-900">
    <x-admin::form.control-group>
        <x-admin::form.control-group.label class="required">
            @lang('menu::app.admin.menus.fields.name')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="text"
            name="name"
            rules="required"
            :value="old('name', $menu->name ?? '')"
            :label="trans('menu::app.admin.menus.fields.name')"
            :placeholder="trans('menu::app.admin.menus.fields.name')"
        />

        <x-admin::form.control-group.error control-name="name" />
    </x-admin::form.control-group>

    <x-admin::form.control-group>
        <x-admin::form.control-group.label class="required">
            @lang('menu::app.admin.menus.fields.code')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="text"
            name="code"
            rules="required|alpha_dash"
            :value="old('code', $menu->code ?? '')"
            :label="trans('menu::app.admin.menus.fields.code')"
            :placeholder="trans('menu::app.admin.menus.fields.code')"
        />

        <x-admin::form.control-group.error control-name="code" />
    </x-admin::form.control-group>

    <x-admin::form.control-group>
        <x-admin::form.control-group.label class="required">
            @lang('menu::app.admin.menus.fields.location')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="select"
            name="location"
            rules="required"
            :label="trans('menu::app.admin.menus.fields.location')"
        >
            <option value="">@lang('menu::app.admin.menus.fields.location-select-placeholder')</option>

            @foreach (($locations ?? collect()) as $location)
                <option value="{{ $location }}" @selected(old('location', $menu->location ?? '') === $location)>
                    {{ $location }}
                </option>
            @endforeach
        </x-admin::form.control-group.control>

        <x-admin::form.control-group.error control-name="location" />
    </x-admin::form.control-group>

    <x-admin::form.control-group class="!mb-0 flex select-none items-center gap-2.5">
        <input type="hidden" name="is_active" value="0" />
        <x-admin::form.control-group.control
            type="checkbox"
            name="is_active"
            id="is_active"
            value="1"
            for="is_active"
            :checked="(bool) old('is_active', $menu->is_active ?? true)"
        />

        <label for="is_active" class="cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('menu::app.admin.menus.fields.status')
        </label>
    </x-admin::form.control-group>
</div>
