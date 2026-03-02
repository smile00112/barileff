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
        $validated = $request->validated();
        $inventorySourceId = (int) $validated['inventory_source_ids'];
        $redirectCityId = (int) $request->input('redirect_city_id', 0);

        $zone = DeliveryZone::query()->create([
            'city_id' => (int) $validated['city_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'polygon_json' => json_decode((string) $validated['polygon_json'], true),
            'polygon_color' => $validated['polygon_color'] ?? '#0077cc',
            'polygon_fill_opacity' => (float) ($validated['polygon_fill_opacity'] ?? 0.2),
            'polygon_stroke_opacity' => (float) ($validated['polygon_stroke_opacity'] ?? 1),
            'delivery_time_minutes' => $validated['delivery_time_minutes'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        $zone->inventory_sources()->sync([$inventorySourceId]);

        foreach ($request->input('rates', []) as $index => $rate) {
            $zone->rates()->create([
                'min_order_total' => $rate['min_order_total'],
                'price' => $rate['price'],
                'sort_order' => (int) ($rate['sort_order'] ?? $index),
            ]);
        }

        session()->flash('success', 'Delivery zone created successfully.');

        if ($redirectCityId > 0) {
            return redirect()->route('admin.settings.delivery_cities.zones', $redirectCityId);
        }

        return redirect()->route('admin.settings.delivery_zones.index');
    }

    public function edit(int $id)
    {
        $deliveryZone = DeliveryZone::query()->with(['rates', 'inventory_sources'])->findOrFail($id);
        $cityId = (int) $deliveryZone->city_id;

        return view('delivery-zones::settings.delivery-zones.edit', [
            'deliveryZone' => $deliveryZone,
            'cities' => DeliveryCity::query()
                ->where(function ($query) use ($cityId) {
                    $query->where('is_active', true)
                        ->orWhere('id', $cityId);
                })
                ->orderBy('name')
                ->get(),
            'inventorySources' => InventorySource::query()->where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function update(DeliveryZoneRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();
        $inventorySourceId = (int) $validated['inventory_source_ids'];
        $redirectCityId = (int) $request->input('redirect_city_id', 0);

        $zone = DeliveryZone::query()->with('rates')->findOrFail($id);

        $zone->update([
            'city_id' => (int) $validated['city_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'polygon_json' => json_decode((string) $validated['polygon_json'], true),
            'polygon_color' => $validated['polygon_color'] ?? '#0077cc',
            'polygon_fill_opacity' => (float) ($validated['polygon_fill_opacity'] ?? 0.2),
            'polygon_stroke_opacity' => (float) ($validated['polygon_stroke_opacity'] ?? 1),
            'delivery_time_minutes' => $validated['delivery_time_minutes'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        $zone->inventory_sources()->sync([$inventorySourceId]);
        $zone->rates()->delete();

        foreach ($request->input('rates', []) as $index => $rate) {
            $zone->rates()->create([
                'min_order_total' => $rate['min_order_total'],
                'price' => $rate['price'],
                'sort_order' => (int) ($rate['sort_order'] ?? $index),
            ]);
        }

        session()->flash('success', 'Delivery zone updated successfully.');

        if ($redirectCityId > 0) {
            return redirect()->route('admin.settings.delivery_cities.zones', $redirectCityId);
        }

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
