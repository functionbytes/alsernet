<?php

namespace Modules\Chat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class UploadWidgetFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.mimes' => 'The file must be a JPEG, PNG, JPG, PDF, DOC, or DOCX file.',
            'file.max' => 'The file size must not exceed 10 MB.',
        ];
    }
}
