<?php

namespace Webkul\Supplier\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class SupplierDataGrid extends DataGrid
{
    protected $primaryColumn = 'supplier_id';

    public function prepareQueryBuilder(): \Illuminate\Database\Query\Builder
    {
        return DB::table('suppliers')
            ->select(
                'id as supplier_id',
                'name',
                'contact_name',
                'contact_email',
                'contact_phone',
                'status',
            );
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
            'index' => 'name',
            'label' => trans('supplier::app.admin.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'contact_name',
            'label' => trans('supplier::app.admin.datagrid.contact-name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
        ]);

        $this->addColumn([
            'index' => 'contact_email',
            'label' => trans('supplier::app.admin.datagrid.contact-email'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
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
