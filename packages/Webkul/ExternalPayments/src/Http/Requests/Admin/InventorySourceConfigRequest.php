<?php

namespace Webkul\ExternalPayments\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InventorySourceConfigRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'active' => ['nullable', 'boolean'],
            'title' => ['nullable', 'required_if:active,1', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'api_server_url' => ['nullable', 'required_if:active,1', 'url', 'max:500'],
            'api_token' => ['nullable', 'required_if:active,1', 'string', 'max:500'],
            'paid_order_status' => ['nullable', 'in:processing,completed'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required_if' => trans('external-payments::app.admin.inventory-source-configs.validation.title-required'),
            'api_server_url.required_if' => trans('external-payments::app.admin.inventory-source-configs.validation.api-url-required'),
            'api_server_url.url' => trans('external-payments::app.admin.inventory-source-configs.validation.api-url-invalid'),
            'api_token.required_if' => trans('external-payments::app.admin.inventory-source-configs.validation.api-token-required'),
        ];
    }
}
