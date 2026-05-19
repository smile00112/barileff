<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class OrderStatusDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        return DB::table('order_statuses')
            ->select('id', 'code', 'name', 'color', 'sort_order', 'is_active', 'is_terminal', 'is_system', 'is_cancel_state');
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.settings.order-statuses.index.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'code',
            'label' => trans('admin::app.settings.order-statuses.index.datagrid.code'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.settings.order-statuses.index.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'sort_order',
            'label' => trans('admin::app.settings.order-statuses.index.datagrid.sort-order'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => trans('admin::app.settings.order-statuses.index.datagrid.is-active'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => $row->is_active
                ? '<p class="label-active">'.trans('admin::app.settings.order-statuses.index.datagrid.active').'</p>'
                : '<p class="label-info">'.trans('admin::app.settings.order-statuses.index.datagrid.inactive').'</p>',
        ]);

        $this->addColumn([
            'index' => 'is_terminal',
            'label' => trans('admin::app.settings.order-statuses.index.datagrid.is-terminal'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => $row->is_terminal
                ? '<p class="label-active">'.trans('admin::app.settings.order-statuses.index.datagrid.yes').'</p>'
                : '<p class="label-info">'.trans('admin::app.settings.order-statuses.index.datagrid.no').'</p>',
        ]);

        $this->addColumn([
            'index' => 'is_system',
            'label' => trans('admin::app.settings.order-statuses.index.datagrid.is-system'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => $row->is_system
                ? '<p class="label-warning">'.trans('admin::app.settings.order-statuses.index.datagrid.system').'</p>'
                : '<p class="label-info">'.trans('admin::app.settings.order-statuses.index.datagrid.custom').'</p>',
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('settings.order_statuses.edit')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'title' => trans('admin::app.settings.order-statuses.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.settings.order_statuses.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('settings.order_statuses.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'title' => trans('admin::app.settings.order-statuses.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.settings.order_statuses.destroy', $row->id),
            ]);
        }
    }
}
