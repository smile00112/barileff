<?php

namespace Webkul\ExternalPayments\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\ExternalPayments\Http\Controllers\Controller;
use Webkul\ExternalPayments\Http\Requests\Admin\InventorySourceConfigRequest;
use Webkul\ExternalPayments\Repositories\InventorySourceConfigRepository;
use Webkul\Inventory\Repositories\InventorySourceRepository;

class InventorySourceConfigController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected InventorySourceConfigRepository $configRepository,
        protected InventorySourceRepository $inventorySourceRepository,
    ) {}

    /**
     * Show list of inventory sources with their payment config status.
     */
    public function index(): View
    {
        $inventorySources = $this->inventorySourceRepository->all();

        $configs = $this->configRepository->all()
            ->keyBy('inventory_source_id');

        return view('external-payments::admin.inventory-source-configs.index', compact('inventorySources', 'configs'));
    }

    /**
     * Show edit form for a specific inventory source config.
     */
    public function edit(int $inventorySourceId): View
    {
        $inventorySource = $this->inventorySourceRepository->findOrFail($inventorySourceId);

        $config = $this->configRepository->findOneWhere(['inventory_source_id' => $inventorySourceId]);

        return view('external-payments::admin.inventory-source-configs.edit', compact('inventorySource', 'config'));
    }

    /**
     * Save (create or update) the config for a specific inventory source.
     */
    public function update(int $inventorySourceId, InventorySourceConfigRequest $request): RedirectResponse
    {
        $this->inventorySourceRepository->findOrFail($inventorySourceId);

        $data = $request->validated();
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['paid_order_status'] = $data['paid_order_status'] ?? 'processing';

        $this->configRepository->updateOrCreate(
            ['inventory_source_id' => $inventorySourceId],
            $data
        );

        session()->flash('success', trans('external-payments::app.admin.inventory-source-configs.saved'));

        return redirect()->route('admin.external-payments.inventory-source-configs.index');
    }
}
