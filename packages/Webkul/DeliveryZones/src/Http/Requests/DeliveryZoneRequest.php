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
            'city_id' => ['required', 'exists:delivery_cities,id'],
            'code' => ['required', 'alpha_dash', 'max:255', 'unique:delivery_zones,code,'.$this->id.',id,city_id,'.$this->city_id],
            'name' => ['required', 'string', 'max:255'],
            'polygon_json' => ['required', 'json'],
            'delivery_time_minutes' => ['nullable', 'integer', 'min:0'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
            'inventory_source_ids' => ['required', 'array', 'min:1'],
            'inventory_source_ids.*' => ['integer', 'exists:inventory_sources,id'],
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.min_order_total' => ['required', 'numeric', 'min:0'],
            'rates.*.price' => ['required', 'numeric', 'min:0'],
            'rates.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
