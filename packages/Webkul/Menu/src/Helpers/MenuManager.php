<?php

namespace Webkul\Menu\Helpers;

use Illuminate\Support\Collection;
use Webkul\Menu\Repositories\MenuItemRepository;

class MenuManager
{
    public function __construct(protected MenuItemRepository $menuItemRepository) {}

    public function getByLocation(string $location): Collection
    {
        return $this->menuItemRepository->getTreeByLocation($location);
    }

    public function resolveUrl($item): string
    {
        if ($item->type === 'cms_page' && $item->cmsPage) {
            $urlKey = $item->cmsPage->translate(core()->getCurrentLocale()->code)?->url_key;

            return $urlKey ? url('page/'.$urlKey) : '#';
        }

        return $item->url ?: '#';
    }
}
