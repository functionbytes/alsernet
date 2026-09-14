<?php

namespace Modules\Helpdesk\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class WebRtcAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'sdp' => ['required', 'string', 'min:32', 'max:65535'],
            'type' => ['required', 'string', 'in:answer'],
        ];
    }
}
