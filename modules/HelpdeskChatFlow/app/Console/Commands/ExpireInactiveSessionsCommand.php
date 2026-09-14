<?php

namespace Modules\HelpdeskChatFlow\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Expires chat flow sessions that have been inactive (no update) for longer
 * than the given threshold, so abandoned conversations don't stay "active"
 * forever. Optionally sends a closing message to the customer.
 */
class ExpireInactiveSessionsCommand extends Command
{
    protected $signature = 'chatflow:expire-sessions
                            {--minutes=30 : Inactivity threshold in minutes}
                            {--status=abandoned : Final status (abandoned|transferred)}
                            {--message= : Optional closing message to send the customer}';

    protected $description = 'Expire chat flow sessions inactive for more than N minutes';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $status = in_array($this->option('status'), ['abandoned', 'transferred'], true)
            ? $this->option('status')
            : 'abandoned';
        $message = $this->option('message');
        $cutoff = now()->subMinutes($minutes);

        $expired = 0;

        ChatFlowSession::query()
            ->where('status', 'active')
            ->where(function ($q) use ($cutoff) {
                // Inactivity is measured from the customer's last reply; sessions
                // created before that column existed fall back to started_at.
                $q->where('last_customer_reply_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('last_customer_reply_at')->where('started_at', '<', $cutoff);
                    });
            })
            ->with('conversation')
            ->chunkById(100, function ($sessions) use (&$expired, $status, $message): void {
                foreach ($sessions as $session) {
                    if ($message && $session->conversation) {
                        $session->conversation->items()->create([
                            'type' => 'message',
                            'body' => $message,
                            'is_internal' => false,
                            'metadata' => ['sent_by_chatflow' => true, 'flow_expired' => true],
                        ]);
                    }

                    $session->update(['status' => $status, 'ended_at' => now()]);
                    ChatFlowCompleted::dispatch($session);
                    $expired++;
                }
            });

        $this->info("Expired {$expired} inactive session(s) older than {$minutes} min → {$status}.");

        return self::SUCCESS;
    }
}
