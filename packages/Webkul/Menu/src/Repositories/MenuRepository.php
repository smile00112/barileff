<?php

namespace Webkul\Menu\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Menu\Models\Menu;

class MenuRepository extends Repository
{
    public function model(): string
    {
        return Menu::class;
    }
}
