<?php

namespace Webkul\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menuId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'alpha_dash', 'max:191', Rule::unique('site_menus', 'code')->ignore($menuId)],
            'location' => ['required', 'string', 'max:191'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
