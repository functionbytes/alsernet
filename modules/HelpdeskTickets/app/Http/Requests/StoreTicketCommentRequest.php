<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        // User must be the ticket owner, assigned agent, or an admin
        return $this->user()->can('update', $ticket);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => 'required|exists:helpdesk_tickets,id',
            'body' => 'nullable|string|max:10000|required_without:html_body',
            'html_body' => 'nullable|string|max:20000|required_without:body',
            'is_internal' => 'boolean',
            'attachment_urls' => 'nullable|array|max:10',
            'attachment_urls.*' => 'url|max:2048',
            'mentioned_user_ids' => 'nullable|array|max:50',
            'mentioned_user_ids.*' => 'integer|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ticket_id.required' => 'El ticket es obligatorio',
            'ticket_id.exists' => 'El ticket especificado no existe',
            'body.required_without' => 'El comentario debe contener texto o HTML',
            'body.max' => 'El texto del comentario no puede exceder 10,000 caracteres',
            'html_body.max' => 'El HTML del comentario no puede exceder 20,000 caracteres',
            'attachment_urls.max' => 'No puedes adjuntar más de 10 archivos',
            'attachment_urls.*.url' => 'Todas las URLs de archivo deben ser válidas',
            'mentioned_user_ids.max' => 'No puedes mencionar a más de 50 usuarios',
            'mentioned_user_ids.*.exists' => 'Uno o más usuarios mencionados no existen',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            'ticket_id' => 'ticket',
            'body' => 'comentario',
            'html_body' => 'comentario en HTML',
            'is_internal' => 'visibilidad',
            'attachment_urls' => 'archivos adjuntos',
            'mentioned_user_ids' => 'usuarios mencionados',
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

        // Parse mentioned user IDs from mentions syntax
        if ($this->has('body') || $this->has('html_body')) {
            $content = $this->input('body') ?: $this->input('html_body');
            $this->mergeMentionedUserIds($content);
        }
    }

    /**
     * Merge mentioned user IDs from @mention syntax.
     */
    protected function mergeMentionedUserIds(string $content): void
    {
        $existingIds = $this->input('mentioned_user_ids', []);

        // Parse @username mentions
        if (preg_match_all('/@(\d+)/', $content, $matches)) {
            $newIds = array_map('intval', $matches[1]);
            $allIds = array_unique(array_merge($existingIds, $newIds));
            $this->merge(['mentioned_user_ids' => $allIds]);
        }
    }
}
