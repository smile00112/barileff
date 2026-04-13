<?php

namespace Webkul\Menu\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Menu\Models\MenuItem;

class MenuItemRepository extends Repository
{
    public function model(): string
    {
        return MenuItem::class;
    }

    public function getTreeByMenuId(int $menuId): Collection
    {
        $items = $this->model
            ->with(['cmsPage', 'children'])
            ->where('menu_id', $menuId)
            ->orderBy('sort_order')
            ->get();

        return $this->buildTree($items);
    }

    public function getTreeByLocation(string $location): Collection
    {
        $items = $this->model
            ->with(['cmsPage', 'menu'])
            ->whereHas('menu', function ($query) use ($location) {
                $query->where('location', $location)->where('is_active', 1);
            })
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        return $this->buildTree($items);
    }

    protected function buildTree(Collection $items, ?int $parentId = null): Collection
    {
        return $items
            ->where('parent_id', $parentId)
            ->sortBy('sort_order')
            ->values()
            ->map(function ($item) use ($items) {
                $item->setRelation('children', $this->buildTree($items, $item->id));

                return $item;
            });
    }
}
