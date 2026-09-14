<?php

namespace Modules\Helpdesk\Services\Exports;

use Generator;
use Modules\Helpdesk\Models\CsatRating;

class CsatExporter
{
    public function __construct(
        private readonly array $filters = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            'ID',
            'Conversación',
            'Cliente',
            'Agente (ID)',
            'Rating',
            'Comentario',
            'Fecha',
        ];
    }

    public function rows(): Generator
    {
        $query = CsatRating::query()
            ->with(['customer'])
            ->when($this->filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->orderBy('id');

        foreach ($query->lazy(200) as $csat) {
            yield [
                $csat->id,
                $csat->conversation_id ?? '',
                $csat->customer?->name ?? '',
                $csat->agent_id ?? '',
                $csat->rating ?? '',
                $csat->comment ?? '',
                $csat->answered_at?->toDateTimeString() ?? $csat->created_at?->toDateTimeString() ?? '',
            ];
        }
    }
}
