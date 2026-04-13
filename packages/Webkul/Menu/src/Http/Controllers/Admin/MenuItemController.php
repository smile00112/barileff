<?php

namespace Webkul\Menu\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\CMS\Repositories\PageRepository;
use Webkul\Menu\Http\Requests\MenuItemRequest;
use Webkul\Menu\Repositories\MenuItemRepository;
use Webkul\Menu\Repositories\MenuRepository;

class MenuItemController extends Controller
{
    public function __construct(
        protected MenuRepository $menuRepository,
        protected MenuItemRepository $menuItemRepository,
        protected PageRepository $pageRepository
    ) {}

    public function index(int $menuId)
    {
        $menu = $this->menuRepository->findOrFail($menuId);

        $items = $this->menuItemRepository->getTreeByMenuId($menuId);

        $cmsPages = $this->pageRepository->all(['id'])
            ->map(fn ($page) => [
                'id' => $page->id,
                'title' => $page->translate(core()->getCurrentLocale()->code)?->page_title ?? 'Page #'.$page->id,
            ]);

        $flatItems = $this->menuItemRepository->findWhere(['menu_id' => $menuId], ['id', 'title']);

        return view('menu::admin.menus.items.index', compact('menu', 'items', 'cmsPages', 'flatItems'));
    }

    public function store(MenuItemRequest $request, int $menuId)
    {
        $data = $request->validated();
        $data['menu_id'] = $menuId;
        $data['is_active'] = (bool) $request->boolean('is_active');
        $data['sort_order'] = $this->menuItemRepository->findWhere(['menu_id' => $menuId])->count();
        $data['cms_page_id'] = $data['type'] === 'cms_page' ? ($data['cms_page_id'] ?? null) : null;
        $data['url'] = $data['type'] === 'custom_url' ? ($data['url'] ?? null) : null;

        $this->menuItemRepository->create($data);

        return new JsonResponse([
            'message' => trans('menu::app.admin.items.messages.create-success'),
        ]);
    }

    public function update(MenuItemRequest $request, int $menuId, int $id)
    {
        $item = $this->menuItemRepository->findOrFail($id);

        if ((int) $item->menu_id !== $menuId) {
            abort(404);
        }

        $data = $request->validated();
        $data['is_active'] = (bool) $request->boolean('is_active');
        $data['cms_page_id'] = $data['type'] === 'cms_page' ? ($data['cms_page_id'] ?? null) : null;
        $data['url'] = $data['type'] === 'custom_url' ? ($data['url'] ?? null) : null;

        $this->menuItemRepository->update($data, $id);

        return new JsonResponse([
            'message' => trans('menu::app.admin.items.messages.update-success'),
        ]);
    }

    public function delete(int $menuId, int $id): JsonResponse
    {
        $item = $this->menuItemRepository->findOrFail($id);

        if ((int) $item->menu_id !== $menuId) {
            abort(404);
        }

        $this->menuItemRepository->delete($id);

        return new JsonResponse([
            'message' => trans('menu::app.admin.items.messages.delete-success'),
        ]);
    }

    public function sort(int $menuId): JsonResponse
    {
        $tree = request()->input('tree', []);

        DB::transaction(function () use ($menuId, $tree) {
            $this->syncTree($menuId, $tree);
        });

        return new JsonResponse([
            'message' => trans('menu::app.admin.items.messages.sort-success'),
        ]);
    }

    protected function syncTree(int $menuId, array $items, ?int $parentId = null): void
    {
        foreach ($items as $index => $item) {
            $model = $this->menuItemRepository->findOrFail((int) $item['id']);

            if ((int) $model->menu_id !== $menuId) {
                continue;
            }

            $this->menuItemRepository->update([
                'parent_id' => $parentId,
                'sort_order' => $index,
            ], $model->id);

            if (! empty($item['children']) && is_array($item['children'])) {
                $this->syncTree($menuId, $item['children'], $model->id);
            }
        }
    }
}
