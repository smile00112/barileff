<?php

namespace Webkul\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;
use Webkul\Customer\Rules\VatIdRule;

class CartAddressRequest extends FormRequest
{
    /**
     * Rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Determine if the product is authorized to make this request.
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
        if ($this->has('billing')) {
            $this->mergeAddressRules('billing');
        }

        if (! $this->input('billing.use_for_shipping')) {
            $this->mergeAddressRules('shipping');
        }

        $this->mergeWithRules([
            'delivery_point_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_point_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_zone_id' => ['nullable', 'integer', 'exists:delivery_zones,id'],
            'billing.additional' => ['nullable', 'array'],
            'billing.additional.label' => ['nullable', 'string', 'max:255'],
            'billing.additional.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'billing.additional.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'billing.additional.zone_id' => ['nullable', 'integer', 'exists:delivery_zones,id'],
            'billing.additional.zone_name' => ['nullable', 'string', 'max:255'],
            'billing.additional.is_private_house' => ['nullable', 'boolean'],
            'billing.additional.apartment' => ['nullable', 'string', 'max:255'],
            'billing.additional.entrance' => ['nullable', 'string', 'max:255'],
            'billing.additional.floor' => ['nullable', 'string', 'max:255'],
            'billing.additional.intercom' => ['nullable', 'string', 'max:255'],
            'shipping.additional' => ['nullable', 'array'],
            'shipping.additional.label' => ['nullable', 'string', 'max:255'],
            'shipping.additional.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'shipping.additional.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'shipping.additional.zone_id' => ['nullable', 'integer', 'exists:delivery_zones,id'],
            'shipping.additional.zone_name' => ['nullable', 'string', 'max:255'],
            'shipping.additional.is_private_house' => ['nullable', 'boolean'],
            'shipping.additional.apartment' => ['nullable', 'string', 'max:255'],
            'shipping.additional.entrance' => ['nullable', 'string', 'max:255'],
            'shipping.additional.floor' => ['nullable', 'string', 'max:255'],
            'shipping.additional.intercom' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->rules;
    }

    /**
     * Merge new address rules.
     */
    private function mergeAddressRules(string $addressType): void
    {
        $this->mergeWithRules([
            "{$addressType}.company_name" => ['nullable'],
            "{$addressType}.first_name" => ['required'],
            "{$addressType}.last_name" => ['required'],
            "{$addressType}.email" => ['required'],
            "{$addressType}.address" => ['required', 'array', 'min:1'],
            "{$addressType}.city" => ['required'],
            "{$addressType}.country" => core()->isCountryRequired() ? ['required'] : ['nullable'],
            "{$addressType}.state" => core()->isStateRequired() ? ['required'] : ['nullable'],
            "{$addressType}.postcode" => core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode],
            "{$addressType}.phone" => ['required', new PhoneNumber],
        ]);

        if ($addressType == 'billing') {
            $this->mergeWithRules([
                "{$addressType}.vat_id" => [(new VatIdRule)->setCountry($this->input('billing.country'))],
            ]);
        }
    }

    /**
     * Merge additional rules.
     */
    private function mergeWithRules($rules): void
    {
        $this->rules = array_merge($this->rules, $rules);
    }
}
