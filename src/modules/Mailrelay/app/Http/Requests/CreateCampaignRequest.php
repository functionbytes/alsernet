<?php

namespace Modules\Mailrelay\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Mailrelay\Enums\CampaignStatus;

class CreateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'subject' => [
                'required',
                'string',
                'max:255',
            ],
            'html_content' => [
                'required',
                'string',
            ],
            'plain_content' => [
                'nullable',
                'string',
            ],
            'sender_id' => [
                'required',
                'integer',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::enum(CampaignStatus::class),
            ],
            'scheduled_at' => [
                'nullable',
                'date',
                'after:now',
            ],
            'recipient_group_ids' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'recipient_group_ids.*' => [
                'integer',
                'exists:mailrelay_groups,id',
            ],
            'metadata' => [
                'sometimes',
                'array',
            ],
            'metadata.*' => [
                'string',
                'max:1000',
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
            'name.required' => 'The campaign name is required.',
            'name.max' => 'The campaign name must not exceed 255 characters.',
            'subject.required' => 'The email subject is required.',
            'subject.max' => 'The email subject must not exceed 255 characters.',
            'html_content.required' => 'The email content is required.',
            'sender_id.required' => 'A sender must be selected.',
            'sender_id.integer' => 'The sender ID must be a valid integer.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
            'recipient_group_ids.min' => 'At least one recipient group must be selected.',
            'recipient_group_ids.*.exists' => 'One or more selected recipient groups do not exist.',
        ];
    }
}
