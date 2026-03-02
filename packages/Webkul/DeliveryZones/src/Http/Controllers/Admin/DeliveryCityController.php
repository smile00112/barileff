<?php

namespace Webkul\DeliveryZones\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DeliveryZones\DataGrids\DeliveryCitiesDataGrid;
use Webkul\DeliveryZones\Http\Requests\DeliveryCityRequest;
use Webkul\DeliveryZones\Models\DeliveryCity;
use Webkul\Inventory\Models\InventorySource;

class DeliveryCityController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(DeliveryCitiesDataGrid::class)->process();
        }

        return view('delivery-zones::settings.delivery-cities.index');
    }

    public function create()
    {
        return view('delivery-zones::settings.delivery-cities.create');
    }

    public function store(DeliveryCityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DeliveryCity::query()->create([
            ...$validated,
            'polygon_json' => json_decode((string) $validated['polygon_json'], true),
            'center_lat' => $validated['center_lat'] ?? null,
            'center_lng' => $validated['center_lng'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        session()->flash('success', 'Delivery city created successfully.');

        return redirect()->route('admin.settings.delivery_cities.index');
    }

    public function edit(int $id)
    {
        $deliveryCity = DeliveryCity::query()->findOrFail($id);

        return view('delivery-zones::settings.delivery-cities.edit', compact('deliveryCity'));
    }

    public function zones(int $id)
    {
        $deliveryCity = DeliveryCity::query()
            ->with([
                'zones.rates',
                'zones.inventory_sources',
            ])
            ->findOrFail($id);

        return view('delivery-zones::settings.delivery-cities.zones', [
            'deliveryCity' => $deliveryCity,
            'inventorySources' => InventorySource::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function update(DeliveryCityRequest $request, int $id): RedirectResponse
    {
        $deliveryCity = DeliveryCity::query()->findOrFail($id);
        $validated = $request->validated();

        $deliveryCity->update([
            ...$validated,
            'polygon_json' => json_decode((string) $validated['polygon_json'], true),
            'center_lat' => $validated['center_lat'] ?? null,
            'center_lng' => $validated['center_lng'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        session()->flash('success', 'Delivery city updated successfully.');

        return redirect()->route('admin.settings.delivery_cities.index');
    }

    public function destroy(int $id): JsonResponse
    {
        DeliveryCity::query()->findOrFail($id)->delete();

        return response()->json([
            'message' => 'Delivery city deleted successfully.',
        ]);
    }
}
