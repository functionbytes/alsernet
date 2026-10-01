<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Models\Conversation;

class RunAgentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $conversation = $this->route('conversation');

        return $user !== null
            && $conversation instanceof Conversation
            && $user->can('helpdesk.ai-prompts.agent-actions')
            && $user->can('view', $conversation);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action_key' => ['required', 'string', 'max:48'],
            'args' => ['nullable', 'array'],
            'args.*' => ['nullable', 'string', 'max:500'],
            'identity_confirmed' => ['nullable', 'boolean'],
            'write_confirmed' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identity_confirmed' => $this->boolean('identity_confirmed'),
            'write_confirmed' => $this->boolean('write_confirmed'),
        ]);
    }
}
