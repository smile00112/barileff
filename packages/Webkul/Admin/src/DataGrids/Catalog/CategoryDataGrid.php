<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CategoryDataGrid extends DataGrid
{
    /**
     * Index.
     *
     * @var string
     */
    protected $primaryColumn = 'category_id';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('categories')
            ->select(
                'categories.id as category_id',
                'category_translations.name',
                'category_translations.slug',
                'categories.position',
                'categories.status',
                'category_translations.locale',
            )
            ->leftJoin('category_translations', function ($join) {
                $join->on('categories.id', '=', 'category_translations.category_id')
                    ->where('category_translations.locale', '=', app()->getLocale());
            })
            ->where('category_translations.locale', app()->getLocale())
            ->groupBy([
                'categories.id',
                'category_translations.name',
                'category_translations.slug',
                'categories.position',
                'categories.status',
                'category_translations.locale',
            ]);

        $this->addFilter('category_id', 'categories.id');

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'category_id',
            'label' => trans('admin::app.catalog.categories.index.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.catalog.categories.index.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'position',
            'label' => trans('admin::app.catalog.categories.index.datagrid.position'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.catalog.categories.index.datagrid.status'),
            'type' => 'boolean',
            'filterable' => true,
            'filterable_options' => [
                [
                    'label' => trans('admin::app.catalog.categories.index.datagrid.active'),
                    'value' => 1,
                ],
                [
                    'label' => trans('admin::app.catalog.categories.index.datagrid.inactive'),
                    'value' => 0,
                ],
            ],
            'sortable' => true,
            'closure' => function ($value) {
                if ($value->status) {
                    return '<span class="badge badge-md badge-success">'.trans('admin::app.catalog.categories.index.datagrid.active').'</span>';
                }

                return '<span class="badge badge-md badge-danger">'.trans('admin::app.catalog.categories.index.datagrid.inactive').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'products_count',
            'label' => trans('admin::app.catalog.categories.index.datagrid.no-of-products'),
            'type' => 'string',
            'sortable' => false,
            'closure' => function ($row) {
                $count = Cache::remember(
                    'cat_product_count_'.$row->category_id,
                    3600,
                    fn () => DB::table('product_categories')
                        ->where('category_id', $row->category_id)
                        ->count()
                );

                $url = route('admin.catalog.products.index')
                    .'?filters[category_name][0]='.$row->category_id;

                return '<a href="'.$url.'">'.$count.'</a>';
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-view',
            'title' => trans('admin::app.catalog.categories.index.datagrid.view-on-site'),
            'method' => 'GET',
            'target' => '_blank',
            'url' => function ($row) {
                return url($row->slug);
            },
        ]);

        if (bouncer()->hasPermission('catalog.categories.edit')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'title' => trans('admin::app.catalog.categories.index.datagrid.edit'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.catalog.categories.edit', $row->category_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.categories.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'title' => trans('admin::app.catalog.categories.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => function ($row) {
                    return route('admin.catalog.categories.delete', $row->category_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.categories.delete')) {
            $this->addMassAction([
                'title' => trans('admin::app.catalog.categories.index.datagrid.delete'),
                'method' => 'POST',
                'url' => route('admin.catalog.categories.mass_delete'),
            ]);
        }

        if (bouncer()->hasPermission('catalog.categories.edit')) {
            $this->addMassAction([
                'title' => trans('admin::app.catalog.categories.index.datagrid.update-status'),
                'method' => 'POST',
                'url' => route('admin.catalog.categories.mass_update'),
                'options' => [
                    [
                        'label' => trans('admin::app.catalog.categories.index.datagrid.active'),
                        'value' => 1,
                    ], [
                        'label' => trans('admin::app.catalog.categories.index.datagrid.inactive'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
