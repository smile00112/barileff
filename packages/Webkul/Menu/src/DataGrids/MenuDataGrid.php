<?php

namespace Webkul\Menu\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MenuDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('site_menus')
            ->select('id', 'name', 'code', 'location', 'is_active', 'created_at');

        $this->addFilter('id', 'site_menus.id');
        $this->addFilter('name', 'site_menus.name');
        $this->addFilter('code', 'site_menus.code');
        $this->addFilter('location', 'site_menus.location');
        $this->addFilter('is_active', 'site_menus.is_active');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('menu::app.admin.menus.fields.name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'code',
            'label' => trans('menu::app.admin.menus.fields.code'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'location',
            'label' => trans('menu::app.admin.menus.fields.location'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => trans('menu::app.admin.menus.fields.status'),
            'type' => 'boolean',
            'sortable' => true,
            'closure' => fn ($row) => $row->is_active
                ? trans('menu::app.admin.common.active')
                : trans('menu::app.admin.common.inactive'),
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('menu.menus.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('menu::app.admin.common.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.menu.menus.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('menu.items')) {
            $this->addAction([
                'index' => 'items',
                'icon' => 'icon-sort-left',
                'title' => trans('menu::app.admin.menus.actions.items'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.menu.items.index', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('menu.menus.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('menu::app.admin.common.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.menu.menus.delete', $row->id),
            ]);
        }
    }
}
