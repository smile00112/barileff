<?php

namespace Webkul\DeliveryZones\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DeliveryZones\DataGrids\DeliveryZonesDataGrid;
use Webkul\DeliveryZones\Http\Requests\DeliveryZoneRequest;
use Webkul\DeliveryZones\Models\DeliveryCity;
use Webkul\DeliveryZones\Models\DeliveryZone;
use Webkul\Inventory\Models\InventorySource;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(DeliveryZonesDataGrid::class)->process();
        }

        return view('delivery-zones::settings.delivery-zones.index');
    }

    public function create()
    {
        return view('delivery-zones::settings.delivery-zones.create', [
            'cities' => DeliveryCity::query()->where('is_active', true)->orderBy('name')->get(),
            'inventorySources' => InventorySource::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function store(DeliveryZoneRequest $request): RedirectResponse
    {
        $zone = DeliveryZone::query()->create([
            'city_id' => (int) $request->input('city_id'),
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'polygon_json' => json_decode((string) $request->input('polygon_json'), true),
            'center_lat' => $request->input('center_lat'),
            'center_lng' => $request->input('center_lng'),
            'delivery_time_minutes' => $request->input('delivery_time_minutes'),
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        $zone->inventory_sources()->sync($request->input('inventory_source_ids', []));

        foreach ($request->input('rates', []) as $index => $rate) {
            $zone->rates()->create([
                'min_order_total' => $rate['min_order_total'],
                'price' => $rate['price'],
                'sort_order' => (int) ($rate['sort_order'] ?? $index),
            ]);
        }

        session()->flash('success', 'Delivery zone created successfully.');

        return redirect()->route('admin.settings.delivery_zones.index');
    }

    public function edit(int $id)
    {
        $deliveryZone = DeliveryZone::query()->with(['rates', 'inventory_sources'])->findOrFail($id);

        return view('delivery-zones::settings.delivery-zones.edit', [
            'deliveryZone' => $deliveryZone,
            'cities' => DeliveryCity::query()->where('is_active', true)->orderBy('name')->get(),
            'inventorySources' => InventorySource::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function update(DeliveryZoneRequest $request, int $id): RedirectResponse
    {
        $zone = DeliveryZone::query()->with('rates')->findOrFail($id);

        $zone->update([
            'city_id' => (int) $request->input('city_id'),
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'polygon_json' => json_decode((string) $request->input('polygon_json'), true),
            'center_lat' => $request->input('center_lat'),
            'center_lng' => $request->input('center_lng'),
            'delivery_time_minutes' => $request->input('delivery_time_minutes'),
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        $zone->inventory_sources()->sync($request->input('inventory_source_ids', []));
        $zone->rates()->delete();

        foreach ($request->input('rates', []) as $index => $rate) {
            $zone->rates()->create([
                'min_order_total' => $rate['min_order_total'],
                'price' => $rate['price'],
                'sort_order' => (int) ($rate['sort_order'] ?? $index),
            ]);
        }

        session()->flash('success', 'Delivery zone updated successfully.');

        return redirect()->route('admin.settings.delivery_zones.index');
    }

    public function destroy(int $id): JsonResponse
    {
        DeliveryZone::query()->findOrFail($id)->delete();

        return response()->json([
            'message' => 'Delivery zone deleted successfully.',
        ]);
    }
}
