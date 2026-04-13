<?php

namespace Webkul\Menu\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Menu\DataGrids\MenuDataGrid;
use Webkul\Menu\Http\Requests\MenuRequest;
use Webkul\Menu\Repositories\MenuRepository;

class MenuController extends Controller
{
    public function __construct(protected MenuRepository $menuRepository) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(MenuDataGrid::class)->process();
        }

        return view('menu::admin.menus.index');
    }

    public function create()
    {
        $locations = $this->menuRepository->getAvailableLocations();

        return view('menu::admin.menus.create', compact('locations'));
    }

    public function store(MenuRequest $request)
    {
        $this->menuRepository->create([
            ...$request->validated(),
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        session()->flash('success', trans('menu::app.admin.menus.messages.create-success'));

        return redirect()->route('admin.menu.menus.index');
    }

    public function edit(int $id)
    {
        $menu = $this->menuRepository->findOrFail($id);

        $locations = $this->menuRepository
            ->getAvailableLocations()
            ->prepend($menu->location)
            ->unique()
            ->values();

        return view('menu::admin.menus.edit', compact('menu', 'locations'));
    }

    public function update(MenuRequest $request, int $id)
    {
        $this->menuRepository->update([
            ...$request->validated(),
            'is_active' => (bool) $request->boolean('is_active'),
        ], $id);

        session()->flash('success', trans('menu::app.admin.menus.messages.update-success'));

        return redirect()->route('admin.menu.menus.index');
    }

    public function delete(int $id): JsonResponse
    {
        $this->menuRepository->delete($id);

        return new JsonResponse([
            'message' => trans('menu::app.admin.menus.messages.delete-success'),
        ]);
    }
}
