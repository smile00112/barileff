<?php

namespace Webkul\DeliveryZones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'city_id' => ['nullable', 'integer', 'exists:delivery_cities,id'],
            'code' => ['required', 'alpha_dash', 'max:255', 'unique:delivery_zones,code,'.$this->id],
            'name' => ['required', 'string', 'max:255'],
            'polygon_json' => ['required', 'json'],
            'polygon_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'polygon_fill_opacity' => ['required', 'numeric', 'between:0,1'],
            'polygon_stroke_opacity' => ['required', 'numeric', 'between:0,1'],
            'delivery_time_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'inventory_source_ids' => ['required', 'integer', 'exists:inventory_sources,id'],
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.min_order_total' => ['required', 'numeric', 'min:0'],
            'rates.*.price' => ['required', 'numeric', 'min:0'],
            'rates.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'city_id' => __('admin::app.settings.delivery_zones.edit.city'),
            'code' => __('admin::app.settings.delivery_zones.edit.code'),
            'name' => __('admin::app.settings.delivery_zones.edit.zone-name'),
            'polygon_json' => __('admin::app.settings.delivery_zones.edit.polygon-json'),
            'polygon_color' => __('admin::app.settings.delivery_zones.edit.polygon-color'),
            'polygon_fill_opacity' => __('admin::app.settings.delivery_zones.edit.polygon-fill-opacity'),
            'polygon_stroke_opacity' => __('admin::app.settings.delivery_zones.edit.border-opacity'),
            'delivery_time_minutes' => __('admin::app.settings.delivery_zones.edit.delivery-time-min'),
            'is_active' => __('admin::app.settings.delivery_zones.edit.active'),
            'inventory_source_ids' => __('admin::app.settings.delivery_zones.edit.inventory-source'),
            'rates' => __('admin::app.settings.delivery_zones.edit.zone-rates'),
            'rates.*.min_order_total' => __('admin::app.settings.delivery_zones.edit.min-order-total'),
            'rates.*.price' => __('admin::app.settings.delivery_zones.edit.price'),
            'rates.*.sort_order' => __('admin::app.settings.delivery_zones.edit.sort-order'),
        ];
    }
}
