<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketDraft;
use Modules\HelpdeskTickets\Models\TicketReview;
use Modules\HelpdeskTickets\Models\TicketTask;

/**
 * Checklist interna, subtickets/ticket padre y borradores en servidor.
 * Extraído de TicketDetailDataService (30-sep-2026) al trocear ese servicio
 * por sección de panel.
 */
class WorkBuilder
{
    /**
     * @return array{category_fields: array<int, array<string, mixed>>, tasks: array<int, array<string, mixed>>, subtickets: array<int, array<string, mixed>>, parent: ?array<string, mixed>}
     */
    public function workFor(Ticket $ticket): array
    {
        $row = fn (Ticket $t) => [
            'id' => $t->id,
            'ticket_number' => $t->ticket_number,
            'subject' => $t->subject,
            'status_name' => $t->status?->name,
            'status_slug' => $t->statusSlug(),
            'closed' => $t->closed_at !== null,
        ];

        $parentLink = $ticket->links()->where('link_type', 'subticket_of')->with('linkedTicket.status')->first();

        return [
            // Definición de los campos de la categoría + valores actuales,
            // para editarlos desde el panel.
            'category_fields' => $ticket->category
                ? $ticket->category->fields()->where('is_visible', true)->where('type', '!=', 'file')->ordered()->get()
                    ->map(fn ($f) => [
                        'key' => $f->key,
                        'label' => $f->label,
                        'type' => $f->type,
                        'options' => $f->options ?: [],
                        'is_required' => (bool) $f->is_required,
                        'placeholder' => $f->placeholder,
                        'value' => ($ticket->custom_fields ?: [])[$f->key] ?? $f->default_value,
                    ])->values()->all()
                : [],
            // Revisión de calidad por muestreo (TicketQualityReviewService) y
            // su disputa: antes no se enseñaba en ningún sitio.
            'quality_review' => ($review = TicketReview::query()->where('ticket_id', $ticket->id)->first()) ? [
                'score' => $review->score,
                'summary' => $review->summary,
                'issues' => array_values(array_filter((array) ($review->issues ?? []), 'is_string')),
                'disputed' => (bool) $review->disputed,
                'dispute_note' => $review->dispute_note,
            ] : null,
            'tasks' => TicketTask::query()->where('ticket_id', $ticket->id)->orderBy('position')->get()
                ->map(fn (TicketTask $task) => $task->toPanelRow())->all(),
            'subtickets' => $ticket->linkedBy()->where('link_type', 'subticket_of')->with('ticket.status')->get()
                ->pluck('ticket')->filter()->map($row)->values()->all(),
            'parent' => $parentLink?->linkedTicket ? $row($parentLink->linkedTicket) : null,
        ];
    }

    /**
     * @return array{my_draft: ?array{body: string, mode: string, updated_at: ?string}, others_drafting: array<int, array{name: string, updated_at_human: ?string}>}
     */
    public function draftsFor(Ticket $ticket): array
    {
        $userId = auth()->id();

        $drafts = TicketDraft::query()
            ->where('ticket_id', $ticket->id)
            ->where('updated_at', '>=', now()->subDay())
            ->with('user:id,firstname,lastname')
            ->get();

        $mine = $drafts->firstWhere('user_id', $userId);

        return [
            'my_draft' => $mine ? [
                'body' => $mine->body,
                'mode' => $mine->mode,
                'updated_at' => $mine->updated_at?->toIso8601String(),
            ] : null,
            'others_drafting' => $drafts->where('user_id', '!=', $userId)
                ->map(fn (TicketDraft $d) => [
                    'name' => trim(($d->user->firstname ?? '').' '.($d->user->lastname ?? '')) ?: 'Otro agente',
                    'updated_at_human' => $d->updated_at?->diffForHumans(),
                ])->values()->all(),
        ];
    }
}
