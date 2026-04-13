<?php

namespace Webkul\Menu\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Menu\Models\Menu;

class MenuRepository extends Repository
{
    public function model(): string
    {
        return Menu::class;
    }

    public function getAvailableLocations(): Collection
    {
        return $this->model
            ->newQuery()
            ->whereNotNull('location')
            ->where('location', '<>', '')
            ->select('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->values();
    }
}
