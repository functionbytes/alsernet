<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'social_comment_id' => 'required|exists:helpdesk_social_comments,id',
            'body' => 'required|string',
            'type' => 'nullable|string|in:internal,escalation,info',
        ];
    }

    public function messages(): array
    {
        return [
            'social_comment_id.required' => 'El comentario es obligatorio.',
            'body.required' => 'El contenido de la nota es obligatorio.',
        ];
    }
}
