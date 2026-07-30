<?php

namespace Modules\Helpdesk\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class WebRtcAgentIceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'candidate' => ['required', 'array'],
            'candidate.candidate' => ['nullable', 'string', 'max:5000'],
            'candidate.sdpMid' => ['nullable', 'string', 'max:50'],
            'candidate.sdpMLineIndex' => ['nullable', 'integer'],
        ];
    }
}
