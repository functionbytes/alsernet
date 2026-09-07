<?php

namespace Modules\HelpdeskAgents\Listeners;

use Modules\HelpdeskAgents\Jobs\ClassifyTicketJob;
use Modules\HelpdeskAgents\Jobs\DetectTicketLanguageJob;
use Modules\HelpdeskAgents\Jobs\ExtractTicketFieldsJob;
use Modules\HelpdeskAgents\Jobs\IndexTicketEmbeddingJob;
use Modules\HelpdeskTickets\Events\TicketCreated;

/**
 * Fans out the ticket-creation AI enrichment as queued jobs (never inline in
 * the request): language detection for routing, LLM auto-classification
 * (category + priority + sentiment in a single turn), and extraction of the
 * category's custom fields. Todo salvo la deteccion de idioma esta detras de
 * su propio flag, apagado por defecto.
 */
class QueueTicketAiOnTicketCreated
{
    public function handle(TicketCreated $event): void
    {
        $ticketId = $event->ticket->id;

        if (config('helpdeskagents.ticket_ai.language_detection', true)) {
            DetectTicketLanguageJob::dispatch($ticketId);
        }

        if (config('helpdeskagents.ticket_ai.auto_classification', false)) {
            ClassifyTicketJob::dispatch($ticketId);
        }

        // Un ticket que YA nace con categoria (alta desde el panel, plantilla,
        // regla de ingesta) no pasa por la parte de ClassifyTicketJob que
        // encadena la extraccion, asi que se despacha aqui. El job comprueba
        // por su cuenta que la categoria tenga campos y que esten vacios: sin
        // nada que extraer, sale sin tocar la red.
        if ($event->ticket->category_id && config('helpdeskagents.ticket_ai.field_extraction', false)) {
            ExtractTicketFieldsJob::dispatch($ticketId);
        }

        // Vector del texto, para el aviso de duplicado y la deteccion de
        // incidencias masivas. Es la unica de estas funciones cuyo coste es
        // proporcional al volumen de tickets, de ahi que vaya apagada por
        // defecto y con su propio flag.
        if (config('helpdeskagents.ticket_similarity.enabled', false)) {
            IndexTicketEmbeddingJob::dispatch($ticketId);
        }
    }
}
