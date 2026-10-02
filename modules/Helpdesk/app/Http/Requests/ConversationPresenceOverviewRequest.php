<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversationPresenceOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['nullable', 'string', 'max:1500'],
        ];
    }

    /**
     * Ids únicos y positivos (máx. 100) a partir de la lista separada por comas.
     *
     * @return list<int>
     */
    public function conversationIds(): array
    {
        return collect(explode(',', (string) $this->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }
}
