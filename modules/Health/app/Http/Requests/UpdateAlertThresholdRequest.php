<?php

namespace Modules\Health\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlertThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:tickets_overdue,reviews_negative,forms_unread,attention_pending'],
            'conditions' => ['required', 'array'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', 'in:mail,database'],
            'recipients' => ['nullable', 'string'],
            'cooldown_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
