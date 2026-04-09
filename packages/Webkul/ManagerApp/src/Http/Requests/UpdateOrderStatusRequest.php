<?php

namespace Webkul\ManagerApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\ManagerApp\Services\ManagerOrderService;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = implode(',', ManagerOrderService::ALLOWED_STATUSES);

        return [
            'status' => "required|string|in:{$allowed}",
        ];
    }
}
