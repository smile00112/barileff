<?php

namespace Webkul\Supplier\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
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
        try {
            $this->supplierRepository->create($request->validated());

            session()->flash('success', trans('supplier::app.admin.created'));

            return redirect()->route('admin.suppliers.index');
        } catch (\Exception $e) {
            Log::error('Supplier creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->safe()->only(['name', 'sort_order']),
            ]);

            session()->flash('error', trans('supplier::app.admin.create-failed'));

            return redirect()->back()->withInput();
        }
    }

    public function edit(int $id): View
    {
        $supplier = $this->supplierRepository->findOrFail($id);

        return view('supplier::admin.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, int $id): RedirectResponse
    {
        try {
            $this->supplierRepository->update($request->validated(), $id);

            session()->flash('success', trans('supplier::app.admin.updated'));

            return redirect()->route('admin.suppliers.index');
        } catch (\Exception $e) {
            Log::error('Supplier update failed', [
                'supplier_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->safe()->only(['name', 'sort_order']),
            ]);

            session()->flash('error', trans('supplier::app.admin.update-failed'));

            return redirect()->back()->withInput();
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->supplierRepository->delete($id);

            return new JsonResponse([
                'message' => trans('supplier::app.admin.deleted'),
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier deletion failed', [
                'supplier_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'message' => trans('supplier::app.admin.delete-failed', ['count' => 0]),
            ], 400);
        }
    }
}
