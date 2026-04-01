<?php

namespace Webkul\DeliveryZones\DataGrids;

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
            'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.id'),
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'code',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.code'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.name'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'country',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.country'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'state',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.state'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.status'),
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->is_active
                ? trans('admin::app.settings.delivery_zones.datagrid.cities.active')
                : trans('admin::app.settings.delivery_zones.datagrid.cities.inactive'),
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-view',
            'title' => trans('admin::app.settings.delivery_zones.datagrid.cities.manage-zones'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.settings.delivery_cities.zones', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-edit',
            'title' => trans('admin::app.settings.delivery_zones.datagrid.cities.edit'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.settings.delivery_cities.edit', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-delete',
            'title' => trans('admin::app.settings.delivery_zones.datagrid.cities.delete'),
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.settings.delivery_cities.delete', $row->id),
        ]);
    }
}
