<?php

namespace Webkul\Admin\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class ImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
            'delimiter' => ['required', 'in:comma,semicolon,tab,pipe'],
            'locale' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => trans('admin::app.catalog.imports.upload.validation.file-required'),
            'file.mimes' => trans('admin::app.catalog.imports.upload.validation.file-csv-only'),
            'file.max' => trans('admin::app.catalog.imports.upload.validation.file-too-large'),
        ];
    }
}
