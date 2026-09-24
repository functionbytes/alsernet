<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskPrestashop\Services\Ext\OpslogEventStore;

/**
 * Filtros de "Eventos recibidos": estado (Todos/Procesados/Pendientes) y tipo.
 */
class OpslogEventsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdeskprestashop.ops.view');
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['all', 'processed', 'pending'])],
            'event' => ['nullable', 'string', Rule::in(array_keys(OpslogEventStore::EVENTS))],
        ];
    }

    public function status(): string
    {
        return (string) ($this->validated('status') ?? 'all');
    }

    public function eventName(): ?string
    {
        return $this->validated('event');
    }
}
