<?php

namespace Webkul\Markup\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MarkupGroupDataGrid extends DataGrid
{
    protected $primaryColumn = 'group_id';

    public function prepareQueryBuilder(): \Illuminate\Database\Query\Builder
    {
        return DB::table('markup_groups')
            ->select(
                'id as group_id',
                'name',
                'type',
                'schedule_type',
                'is_active',
                'is_applied',
                'sort_order',
            );
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'group_id',
            'label'      => trans('markup::app.admin.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('markup::app.admin.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('markup::app.admin.datagrid.type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => trans('markup::app.admin.datagrid.types.'.$row->type),
        ]);

        $this->addColumn([
            'index'      => 'schedule_type',
            'label'      => trans('markup::app.admin.datagrid.schedule-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => false,
            'closure'    => fn ($row) => trans('markup::app.admin.datagrid.schedule-types.'.$row->schedule_type),
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => trans('markup::app.admin.datagrid.status'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->is_active
                ? '<span class="badge badge-md badge-success">'.trans('markup::app.admin.datagrid.active').'</span>'
                : '<span class="badge badge-md badge-danger">'.trans('markup::app.admin.datagrid.inactive').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'is_applied',
            'label'      => trans('markup::app.admin.datagrid.applied'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->is_applied
                ? '<span class="badge badge-md badge-success">'.trans('markup::app.admin.datagrid.yes').'</span>'
                : '<span class="badge badge-md badge-warning">'.trans('markup::app.admin.datagrid.no').'</span>',
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon'   => 'icon-edit',
            'title'  => trans('markup::app.admin.datagrid.edit'),
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.markup.groups.edit', $row->group_id),
        ]);

        $this->addAction([
            'icon'   => 'icon-delete',
            'title'  => trans('markup::app.admin.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row) => route('admin.markup.groups.destroy', $row->group_id),
        ]);
    }
}
