<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryCitiesDataGrid extends DataGrid
{
    public function prepareQueryBuilder()
    {
        return DB::table('delivery_cities')
            ->select('id', 'code', 'name', 'country', 'state', 'is_active');
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => 'ID',
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'code',
            'label' => 'Code',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => 'Name',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'country',
            'label' => 'Country',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'state',
            'label' => 'State',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => 'Status',
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                if ($row->is_active) {
                    return '<span class="badge badge-md badge-success">Active</span>';
                }

                return '<span class="badge badge-md badge-danger">Inactive</span>';
            },
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-view',
            'title' => 'Manage Zones',
            'method' => 'GET',
            'url' => fn ($row) => route('admin.settings.delivery_cities.zones', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-edit',
            'title' => 'Edit',
            'method' => 'GET',
            'url' => fn ($row) => route('admin.settings.delivery_cities.edit', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-delete',
            'title' => 'Delete',
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.settings.delivery_cities.delete', $row->id),
        ]);
    }
}
