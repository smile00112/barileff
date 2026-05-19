<?php

namespace Webkul\Admin\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderStatusTransitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_status_code' => ['required', 'string', 'exists:order_statuses,code'],
            'to_status_code' => ['required', 'string', 'exists:order_statuses,code', 'different:from_status_code'],
            'delivery_type' => ['nullable', 'string', 'max:50'],
            'payment_type' => ['nullable', 'string', 'max:50'],
            'channel' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'priority' => ['integer', 'min:0'],
        ];
    }
}
