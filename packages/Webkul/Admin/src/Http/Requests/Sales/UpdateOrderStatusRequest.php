<?php

namespace Webkul\Admin\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
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
            'status' => ['required', 'string', 'exists:order_statuses,code'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
