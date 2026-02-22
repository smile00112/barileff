<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryZonesDataGrid extends DataGrid
{
    public function prepareQueryBuilder()
    {
        return DB::table('delivery_zones')
            ->leftJoin('delivery_cities', 'delivery_cities.id', '=', 'delivery_zones.city_id')
            ->select(
                'delivery_zones.id',
                'delivery_zones.code',
                'delivery_zones.name',
                'delivery_zones.delivery_time_minutes',
                'delivery_zones.is_active',
                'delivery_cities.name as city_name'
            );
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
            'label' => 'Zone',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'city_name',
            'label' => 'City',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'delivery_time_minutes',
            'label' => 'Delivery Time (min)',
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => 'Status',
            'type' => 'boolean',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->is_active ? 'Active' : 'Inactive',
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => 'Edit',
            'method' => 'GET',
            'url' => fn ($row) => route('admin.settings.delivery_zones.edit', $row->id),
        ]);

        $this->addAction([
            'icon' => 'icon-delete',
            'title' => 'Delete',
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.settings.delivery_zones.delete', $row->id),
        ]);
    }
}
