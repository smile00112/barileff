{!! view_render_event('bagisto.admin.catalog.product.edit.form.supplier.before', ['product' => $product]) !!}

<!-- Panel -->
<div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
    <!-- Panel Header -->
    <p class="mb-4 flex justify-between text-base font-semibold text-gray-800 dark:text-white">
        @lang('admin::app.catalog.products.edit.supplier.title')
    </p>

    {!! view_render_event('bagisto.admin.catalog.product.edit.form.supplier.controls.before', ['product' => $product]) !!}

    <!-- Panel Content -->
    <div class="text-sm text-gray-600 dark:text-gray-300">
        @php
            $suppliers = app(\Webkul\Supplier\Contracts\Supplier::class)
                ->orderBy('name')
                ->get();
        @endphp

        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.catalog.products.edit.supplier.name')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="supplier_id"
                :value="old('supplier_id', $product->supplier_id)"
            >
                <option value="">
                    @lang('admin::app.catalog.products.edit.supplier.select')
                </option>

                @foreach ($suppliers as $supplier)
                    <option
                        value="{{ $supplier->id }}"
                        {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}
                    >
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </x-admin::form.control-group.control>

            <x-admin::form.control-group.error control-name="supplier_id" />
        </x-admin::form.control-group>
    </div>

    {!! view_render_event('bagisto.admin.catalog.product.edit.form.supplier.controls.after', ['product' => $product]) !!}
</div>

{!! view_render_event('bagisto.admin.catalog.product.edit.form.supplier.after', ['product' => $product]) !!}
