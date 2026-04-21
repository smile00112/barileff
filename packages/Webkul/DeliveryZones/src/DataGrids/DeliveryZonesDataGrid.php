<?php

namespace Webkul\DeliveryZones\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryZonesDataGrid extends DataGrid
{
    public function prepareQueryBuilder()
    {
        return DB::table('delivery_zones')
            ->leftJoin('delivery_cities', 'delivery_cities.id', '=', 'delivery_zones.city_id')
            ->leftJoin('delivery_zone_inventory_sources', 'delivery_zone_inventory_sources.zone_id', '=', 'delivery_zones.id')
            ->leftJoin('inventory_sources', 'inventory_sources.id', '=', 'delivery_zone_inventory_sources.inventory_source_id')
            ->select(
                'delivery_zones.id',
                'delivery_zones.code',
                'delivery_zones.name',
                'delivery_zones.delivery_time_minutes',
                'delivery_zones.is_active',
                'delivery_cities.name as city_name',
                'inventory_sources.name as inventory_source_name'
            );
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.id'),
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'code',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.code'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.zone'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'city_name',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.city'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'inventory_source_name',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.inventory-source'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'delivery_time_minutes',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.delivery-time-min'),
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.status'),
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->is_active
                ? trans('admin::app.settings.delivery_zones.datagrid.zones.active')
                : trans('admin::app.settings.delivery_zones.datagrid.zones.inactive'),
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => trans('admin::app.settings.delivery_zones.datagrid.zones.edit'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.settings.delivery_zones.edit', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-delete',
            'title' => trans('admin::app.settings.delivery_zones.datagrid.zones.delete'),
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.settings.delivery_zones.delete', $row->id),
        ]);
    }
}
