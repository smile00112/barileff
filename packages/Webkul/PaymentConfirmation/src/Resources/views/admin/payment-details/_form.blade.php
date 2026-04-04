<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Title <span class="text-red-500">*</span>
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
        Instructions (shown to customer after placing order) <span class="text-red-500">*</span>
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
        Inventory Source <span class="text-red-500">*</span>
    </label>
    <select name="inventory_source_id"
            class="w-full rounded border border-gray-300 dark:border-gray-600 p-2 text-sm dark:bg-gray-800 dark:text-white"
            required>
        <option value="">— Select Source —</option>
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

<div class="mb-4 flex items-center gap-2">
    <input type="hidden" name="is_active" value="0" />
    <input type="checkbox"
           name="is_active"
           value="1"
           id="is_active"
           {{ old('is_active', $detail?->is_active ?? true) ? 'checked' : '' }} />
    <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
</div>
