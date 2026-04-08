<?php

namespace Webkul\Admin\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $path = $value->getRealPath();

                    if (! $path || ! is_readable($path)) {
                        $fail(trans('admin::app.catalog.imports.upload.validation.file-unreadable'));

                        return;
                    }

                    $sample = file_get_contents($path, false, null, 0, 1024 * 1024);

                    if ($sample === false) {
                        $fail(trans('admin::app.catalog.imports.upload.validation.file-unreadable'));

                        return;
                    }

                    if ($sample === '') {
                        return;
                    }

                    if (str_starts_with($sample, "\xEF\xBB\xBF")) {
                        $sample = (string) substr($sample, 3);
                    }

                    if (! mb_check_encoding($sample, 'UTF-8')) {
                        $fail(trans('admin::app.catalog.imports.upload.validation.file-invalid-encoding'));
                    }
                },
            ],
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
