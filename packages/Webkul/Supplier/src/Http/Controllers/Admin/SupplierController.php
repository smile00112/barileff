<?php

namespace Webkul\Supplier\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Supplier\DataGrids\SupplierDataGrid;
use Webkul\Supplier\Http\Requests\SupplierRequest;
use Webkul\Supplier\Repositories\SupplierRepository;

class SupplierController extends Controller
{
    public function __construct(protected SupplierRepository $supplierRepository) {}

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(SupplierDataGrid::class)->toJson();
        }

        return view('supplier::admin.index');
    }

    public function create(): View
    {
        return view('supplier::admin.create');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $this->supplierRepository->create($request->validated());

        session()->flash('success', trans('supplier::app.admin.created'));

        return redirect()->route('admin.suppliers.index');
    }

    public function edit(int $id): View
    {
        $supplier = $this->supplierRepository->findOrFail($id);

        return view('supplier::admin.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, int $id): RedirectResponse
    {
        $this->supplierRepository->update($request->validated(), $id);

        session()->flash('success', trans('supplier::app.admin.updated'));

        return redirect()->route('admin.suppliers.index');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->supplierRepository->delete($id);

        return new JsonResponse(['message' => trans('supplier::app.admin.deleted')]);
    }
}
