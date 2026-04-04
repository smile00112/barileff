<?php

namespace Webkul\PaymentConfirmation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => 'required|string|max:255',
            'instructions'        => 'required|string',
            'inventory_source_id' => 'required|integer|exists:inventory_sources,id',
            'is_active'           => 'boolean',
        ];
    }
}
