<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        @lang('paymentconfirmation::app.admin.payment-details.fields.title') <span class="text-red-500">*</span>
    </label>
    <input type="text"
           name="title"
           value="{{ old('title', $detail?->title) }}"
           class="w-full rounded border border-gray-300 dark:border-gray-600 p-2 text-sm dark:bg-gray-800 dark:text-white"
           required />
    @error('title')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        @lang('paymentconfirmation::app.admin.payment-details.fields.instructions') <span class="text-red-500">*</span>
    </label>
    <textarea name="instructions"
              rows="8"
              class="w-full rounded border border-gray-300 dark:border-gray-600 p-2 text-sm dark:bg-gray-800 dark:text-white"
              required>{{ old('instructions', $detail?->instructions) }}</textarea>
    @error('instructions')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        @lang('paymentconfirmation::app.admin.payment-details.fields.inventory-source') <span class="text-red-500">*</span>
    </label>
    <select name="inventory_source_id"
            class="w-full rounded border border-gray-300 dark:border-gray-600 p-2 text-sm dark:bg-gray-800 dark:text-white"
            required>
        <option value="">
            @lang('paymentconfirmation::app.admin.payment-details.fields.inventory-source-placeholder')
        </option>
        @foreach ($inventorySources as $source)
            <option value="{{ $source->id }}"
                {{ old('inventory_source_id', $detail?->inventory_source_id) == $source->id ? 'selected' : '' }}>
                {{ $source->name }}
            </option>
        @endforeach
    </select>
    @error('inventory_source_id')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

<x-admin::form.control-group class="mb-4">
    <x-admin::form.control-group.label>
        @lang('paymentconfirmation::app.admin.payment-details.fields.active')
    </x-admin::form.control-group.label>

    <input type="hidden" name="is_active" value="0" />

    <x-admin::form.control-group.control
        type="switch"
        name="is_active"
        value="1"
        :checked="(bool) old('is_active', $detail?->is_active ?? true)"
    />
</x-admin::form.control-group>
