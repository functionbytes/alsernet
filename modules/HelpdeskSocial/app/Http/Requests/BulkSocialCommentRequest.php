<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSocialCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:helpdesk_social_comments,id',
            'action' => 'required|string|in:spam,escalate,assign',
            'params' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debes seleccionar al menos un comentario.',
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción no es válida.',
        ];
    }
}
