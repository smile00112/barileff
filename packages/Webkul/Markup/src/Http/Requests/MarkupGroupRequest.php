<?php

namespace Webkul\Markup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkupGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                                  => ['required', 'string', 'max:255'],
            'description'                           => ['nullable', 'string', 'max:2000'],
            'type'                                  => ['required', Rule::in(['markup', 'discount'])],
            'is_active'                             => ['required', 'boolean'],
            'schedule_type'                         => ['required', Rule::in(['daily', 'weekly'])],
            'apply_to_all_sources'                  => ['required', 'boolean'],
            'sort_order'                            => ['nullable', 'integer', 'min:0'],
            'inventory_sources'                     => Rule::when(
                ! $this->boolean('apply_to_all_sources'),
                ['required', 'array', 'min:1'],
                ['nullable', 'array'],
            ),
            'inventory_sources.*'                   => ['integer', 'exists:inventory_sources,id'],
            'schedules'                             => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week'               => ['nullable', 'integer', 'min:0', 'max:6'],
            'schedules.*.time_from'                 => ['required', 'date_format:H:i'],
            'schedules.*.time_to'                   => ['required', 'date_format:H:i', 'after:schedules.*.time_from'],
            'conditions'                            => ['required', 'array', 'min:1'],
            'conditions.*.cost_from'                => ['nullable', 'numeric', 'min:0'],
            'conditions.*.cost_to'                  => ['nullable', 'numeric', 'min:0'],
            'conditions.*.adjustment_type'          => ['required', Rule::in(['percent', 'fixed'])],
            'conditions.*.adjustment_value'         => ['required', 'numeric', 'min:0.0001'],
            'conditions.*.sort_order'               => ['nullable', 'integer', 'min:0'],
            'conditions.*.categories'               => ['nullable', 'array'],
            'conditions.*.categories.*'             => ['integer', 'exists:categories,id'],
            'conditions.*.products'                 => ['nullable', 'array'],
            'conditions.*.products.*'               => ['integer', 'exists:products,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_sources'   => trans('markup::app.admin.groups.form.inventory-sources'),
            'inventory_sources.*'   => trans('markup::app.admin.groups.form.inventory-sources'),
        ];
    }
}
