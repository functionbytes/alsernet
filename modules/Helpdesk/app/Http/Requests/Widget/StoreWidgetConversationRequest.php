<?php

namespace Modules\Helpdesk\Http\Requests\Widget;

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
        ];
    }
}
