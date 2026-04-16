<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_name' => ['nullable'],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'address' => ['required', 'array', 'min:1'],
            'country' => core()->isCountryRequired() ? ['required'] : ['nullable'],
            'state' => core()->isStateRequired() ? ['required'] : ['nullable'],
            'city' => ['required', 'string'],
            'postcode' => core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode],
            'phone' => ['required', new PhoneNumber],
            'email' => ['required'],
            'additional' => ['nullable', 'array'],
            'additional.label' => ['nullable', 'string', 'max:255'],
            'additional.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'additional.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'additional.zone_id' => ['nullable', 'integer', 'exists:delivery_zones,id'],
            'additional.zone_name' => ['nullable', 'string', 'max:255'],
            'additional.is_private_house' => ['nullable', 'boolean'],
            'additional.apartment' => ['nullable', 'string', 'max:255'],
            'additional.entrance' => ['nullable', 'string', 'max:255'],
            'additional.floor' => ['nullable', 'string', 'max:255'],
            'additional.intercom' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'address.*' => 'address',
        ];
    }
}
