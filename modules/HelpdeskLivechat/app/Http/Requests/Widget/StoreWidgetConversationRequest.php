<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class StoreWidgetConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'website_token' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:200'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:5000'],
            'language' => ['nullable', 'string', 'max:10'],
            'customer_id' => ['nullable', 'integer'],
            'custom_attributes' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $ca = $this->input('custom_attributes');
        if (is_string($ca) && str_starts_with(trim($ca), '{')) {
            $decoded = json_decode($ca, true);
            if (is_array($decoded)) {
                $this->merge(['custom_attributes' => $decoded]);
            }
        }
    }
}
