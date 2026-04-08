<?php

namespace Webkul\Supplier\DataGrids;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataGrid\DataGrid;

class SupplierDataGrid extends DataGrid
{
    protected $primaryColumn = 'supplier_id';

    public function prepareQueryBuilder(): \Illuminate\Database\Query\Builder
    {
        $tablePrefix = DB::getTablePrefix();

        return DB::table('suppliers')
            ->leftJoin('products', 'suppliers.id', '=', 'products.supplier_id')
            ->select(
                'suppliers.id as supplier_id',
                'suppliers.name',
                'suppliers.image',
                'suppliers.sort_order',
                'suppliers.status',
            )
            ->addSelect(DB::raw('COUNT(DISTINCT '.$tablePrefix.'products.id) as products_count'))
            ->groupBy([
                'suppliers.id',
                'suppliers.name',
                'suppliers.image',
                'suppliers.sort_order',
                'suppliers.status',
            ])
            ->orderBy('suppliers.sort_order', 'asc')
            ->orderBy('suppliers.name', 'asc');
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'supplier_id',
            'label' => trans('supplier::app.admin.datagrid.id'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'image',
            'label' => trans('supplier::app.admin.datagrid.image'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                if ($row->image) {
                    $url = Storage::url($row->image);

                    return sprintf(
                        '<img src="%s" class="w-12 h-12 object-cover rounded" alt="%s">',
                        $url,
                        htmlspecialchars($row->name)
                    );
                }

                return '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                            <i class="icon-image text-gray-400"></i>
                        </div>';
            },
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('supplier::app.admin.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'products_count',
            'label' => trans('supplier::app.admin.datagrid.products-count'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->products_count > 0) {
                    $url = route('admin.catalog.products.index', [
                        'filters[supplier_id]' => $row->supplier_id,
                    ]);

                    return sprintf(
                        '<a href="%s" class="text-blue-600 hover:underline">%d</a>',
                        $url,
                        $row->products_count
                    );
                }

                return '<span class="text-gray-400">0</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'sort_order',
            'label' => trans('supplier::app.admin.datagrid.sort-order'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('supplier::app.admin.datagrid.status'),
            'type' => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => $row->status
                ? '<span class="badge badge-md badge-success">'.trans('supplier::app.admin.datagrid.active').'</span>'
                : '<span class="badge badge-md badge-danger">'.trans('supplier::app.admin.datagrid.inactive').'</span>',
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => trans('supplier::app.admin.datagrid.edit'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.suppliers.edit', $row->supplier_id),
        ]);

        $this->addAction([
            'icon' => 'icon-delete',
            'title' => trans('supplier::app.admin.datagrid.delete'),
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.suppliers.destroy', $row->supplier_id),
        ]);
    }
}
