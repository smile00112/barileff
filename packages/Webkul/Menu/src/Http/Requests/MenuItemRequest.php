<?php

namespace Webkul\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'parent_id' => ['nullable', 'integer', 'exists:site_menu_items,id'],
            'type' => ['required', Rule::in(['cms_page', 'custom_url'])],
            'cms_page_id' => ['nullable', 'integer', 'exists:cms_pages,id'],
            'url' => ['nullable', 'string', 'max:2048'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
