<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        // Only the comment author or admins can edit comments
        return $this->user()->id === $comment->user_id ||
               $this->user()->hasPermissionTo('edit all ticket comments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => 'nullable|string|max:10000|required_without:html_body',
            'html_body' => 'nullable|string|max:20000|required_without:body',
            'is_internal' => 'boolean',
            'edit_reason' => 'nullable|string|max:500',
            'attachment_urls' => 'nullable|array|max:10',
            'attachment_urls.*' => 'url|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'body.required_without' => 'El comentario debe contener texto o HTML',
            'body.max' => 'El texto del comentario no puede exceder 10,000 caracteres',
            'html_body.max' => 'El HTML del comentario no puede exceder 20,000 caracteres',
            'edit_reason.max' => 'La razón de edición no puede exceder 500 caracteres',
            'attachment_urls.max' => 'No puedes adjuntar más de 10 archivos',
            'attachment_urls.*.url' => 'Todas las URLs de archivo deben ser válidas',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            'body' => 'comentario',
            'html_body' => 'comentario en HTML',
            'is_internal' => 'visibilidad',
            'edit_reason' => 'razón de edición',
            'attachment_urls' => 'archivos adjuntos',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure body and html_body are either provided
        if (! $this->has('body') && ! $this->has('html_body')) {
            $this->merge(['body' => null, 'html_body' => null]);
        }
    }
}
