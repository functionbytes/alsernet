<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergeConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('conversation'));
    }

    public function rules(): array
    {
        $source = $this->route('conversation')?->getKey();

        return [
            'target_id' => ['required', 'integer', 'not_in:'.$source],
        ];
    }

    public function messages(): array
    {
        return [
            'target_id.not_in' => __('helpdesk::helpdesk.messages.merge_self'),
        ];
    }
}
