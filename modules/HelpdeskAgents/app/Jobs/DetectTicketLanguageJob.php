<?php

namespace Modules\HelpdeskAgents\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTranslate\Services\TranslationService;

/**
 * Detects the language of a ticket's first customer message and stamps it on
 * helpdesk_tickets.detected_language so assignment/routing can filter agents
 * by language. Detection is DELEGATED to HelpdeskTranslate's TranslationService
 * (LibreTranslate /detect, cached) — no duplicate detection logic here.
 *
 * This job only leaves the data ready: agent matching itself is out of scope.
 */
class DetectTicketLanguageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 5;

    public int $timeout = 30;

    public function __construct(public readonly int $ticketId) {}

    public function handle(): void
    {
        if (! config('helpdeskagents.ticket_ai.language_detection', true)) {
            return;
        }

        // Soft dependency: HelpdeskTranslate may be absent/disabled.
        if (! class_exists(TranslationService::class)) {
            return;
        }

        $ticket = Ticket::query()->find($this->ticketId);

        if (! $ticket || $ticket->detected_language) {
            return;
        }

        $text = $this->detectableText($ticket);

        if ($text === '') {
            return;
        }

        $detected = app(TranslationService::class)
            ->detectLanguage($text);

        if (! $detected) {
            return;
        }

        // forceFill + saveQuietly: the column is provisioned by this module,
        // so we neither touch the Ticket model's $fillable nor fire the
        // ticket update event cascade for a metadata-only stamp.
        $ticket->forceFill(['detected_language' => mb_substr($detected, 0, 8)])->saveQuietly();
    }

    private function detectableText(Ticket $ticket): string
    {
        $firstMessage = $ticket->items()
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->oldest('created_at')
            ->value('body');

        $text = trim(strip_tags($firstMessage ?? ''));

        if ($text === '') {
            $text = trim(strip_tags(($ticket->subject ?? '').' '.($ticket->description ?? '')));
        }

        return mb_substr($text, 0, 1000);
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('DetectTicketLanguageJob failed', [
            'ticket_id' => $this->ticketId,
            'error' => $exception->getMessage(),
        ]);
    }
}
