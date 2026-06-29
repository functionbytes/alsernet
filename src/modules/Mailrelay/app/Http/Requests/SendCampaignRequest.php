<?php

namespace Modules\Mailrelay\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->hasPermissionTo('mailrelay.campaigns.send') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campaign_id' => [
                'required',
                'string',
                'exists:campaigns,id',
            ],
            'send_now' => [
                'sometimes',
                'boolean',
            ],
            'scheduled_at' => [
                'required_if:send_now,false',
                'nullable',
                'date',
                'after:now',
            ],
            'test_mode' => [
                'sometimes',
                'boolean',
            ],
            'test_email' => [
                'required_if:test_mode,true',
                'nullable',
                'email:rfc,dns',
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
            'send_to_active_only' => [
                'sometimes',
                'boolean',
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
            'campaign_id.required' => 'A campaign must be selected.',
            'campaign_id.exists' => 'The selected campaign does not exist.',
            'scheduled_at.required_if' => 'A scheduled time is required when not sending immediately.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
            'test_email.required_if' => 'A test email address is required in test mode.',
            'test_email.email' => 'The test email address must be valid.',
            'recipient_group_ids.min' => 'At least one recipient group must be selected.',
            'recipient_group_ids.*.exists' => 'One or more selected recipient groups do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        if (! $this->has('send_now')) {
            $this->merge(['send_now' => false]);
        }

        if (! $this->has('test_mode')) {
            $this->merge(['test_mode' => false]);
        }

        if (! $this->has('send_to_active_only')) {
            $this->merge(['send_to_active_only' => true]);
        }
    }
}
