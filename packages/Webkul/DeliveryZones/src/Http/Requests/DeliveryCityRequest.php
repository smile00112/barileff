<?php

namespace Webkul\DeliveryZones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryCityRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'code' => ['required', 'alpha_dash', 'max:255', 'unique:delivery_cities,code,'.$id],
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:2'],
            'state' => ['nullable', 'string', 'max:255'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'polygon_json' => ['required', 'json'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
