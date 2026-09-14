<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDocumentStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:1000'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comments.max' => 'Los comentarios no pueden superar los 1000 caracteres.',
            'assigned_user_id.exists' => 'El validador seleccionado no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'comments' => 'comentarios',
            'assigned_user_id' => 'validador',
        ];
    }
}
