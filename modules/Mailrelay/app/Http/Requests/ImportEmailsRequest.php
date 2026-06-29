<?php

namespace Modules\Mailrelay\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportEmailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->hasPermissionTo('mailrelay.subscribers.import') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240', // 10MB max
            ],
            'validate_emails' => [
                'sometimes',
                'boolean',
            ],
            'sync_to_mailrelay' => [
                'sometimes',
                'boolean',
            ],
            'group_ids' => [
                'sometimes',
                'array',
            ],
            'group_ids.*' => [
                'integer',
                'exists:mailrelay_groups,id',
            ],
            'skip_duplicates' => [
                'sometimes',
                'boolean',
            ],
            'validation_types' => [
                'sometimes',
                'array',
            ],
            'validation_types.*' => [
                'string',
                'in:syntax,dns,smtp,external,ml',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a file to import.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'The file must be a CSV, TXT, or Excel file.',
            'file.max' => 'The file size must not exceed 10MB.',
            'group_ids.*.exists' => 'One or more selected groups do not exist.',
            'validation_types.*.in' => 'The validation type must be one of: syntax, dns, smtp, external, ml.',
        ];
    }
}
