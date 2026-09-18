<?php

namespace Modules\HelpdeskLivechat\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;

/**
 * Applies the per-channel auto actions (previously inert settings) to idle
 * widget conversations:
 *
 *  - auto_close:    close conversations with no activity for N minutes.
 *  - auto_transfer: return an assigned conversation whose last visitor message
 *                   went unanswered for N minutes to the unassigned queue, so
 *                   another agent can pick it up.
 *  - auto_inactive: flag a conversation as inactive (metadata.inactive_since)
 *                   after N minutes of silence; the flag is cleared when the
 *                   visitor sends a new message (see WidgetConversationService).
 *
 * Idle is measured from `last_message_at`. Scheduled in the ServiceProvider.
 */
class ProcessAutoActionsCommand extends Command
{
    protected $signature = 'helpdesk-livechat:process-auto-actions {--limit=500 : Max conversations processed per channel}';

    protected $description = 'Apply per-channel auto-transfer / auto-inactive / auto-close to idle widget conversations';

    public function handle(): int
    {
        $webs = Web::query()
            ->where(fn ($q) => $q
                ->where('enable_auto_transfer', true)
                ->orWhere('enable_auto_inactive', true)
                ->orWhere('enable_auto_close', true))
            ->get();

        $limit = max(1, (int) $this->option('limit'));
        $totals = ['transferred' => 0, 'inactive' => 0, 'closed' => 0];

        foreach ($webs as $web) {
            $inboxId = Inbox::query()
                ->where('channel_type', 'web')
                ->where('channel_id', $web->id)
                ->value('id');

            if ($inboxId) {
                $this->processChannel($web, (int) $inboxId, $limit, $totals);
            }
        }

        $this->info(sprintf(
            'Auto-actions: %d transferred, %d marked inactive, %d closed.',
            $totals['transferred'],
            $totals['inactive'],
            $totals['closed'],
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $totals
     */
    private function processChannel(Web $web, int $inboxId, int $limit, array &$totals): void
    {
        $thresholds = array_filter([
            $web->enable_auto_transfer ? (int) $web->auto_transfer_minutes : 0,
            $web->enable_auto_inactive ? (int) $web->auto_inactive_minutes : 0,
            $web->enable_auto_close ? (int) $web->auto_close_minutes : 0,
        ], fn (int $minutes) => $minutes > 0);

        if ($thresholds === []) {
            return;
        }

        $cutoff = now()->subMinutes(min($thresholds));

        $conversations = Conversation::query()
            ->where('inbox_id', $inboxId)
            ->whereNotNull('last_message_at')
            ->where('last_message_at', '<=', $cutoff)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->orderBy('last_message_at')
            ->limit($limit)
            ->get();

        foreach ($conversations as $conversation) {
            $this->applyActions($web, $conversation, $totals);
        }
    }

    /**
     * @param  array<string, int>  $totals
     */
    private function applyActions(Web $web, Conversation $conversation, array &$totals): void
    {
        $idleMinutes = (int) abs($conversation->last_message_at->diffInMinutes(now()));

        // Close is terminal — it supersedes transfer/inactive for this conversation.
        if ($web->enable_auto_close && (int) $web->auto_close_minutes > 0 && $idleMinutes >= (int) $web->auto_close_minutes) {
            $conversation->close();
            $totals['closed']++;

            return;
        }

        if ($web->enable_auto_transfer
            && (int) $web->auto_transfer_minutes > 0
            && $conversation->assignee_id
            && $idleMinutes >= (int) $web->auto_transfer_minutes
            && $this->visitorIsWaiting($conversation)) {
            $conversation->assignTo(null);
            $totals['transferred']++;
        }

        if ($web->enable_auto_inactive
            && (int) $web->auto_inactive_minutes > 0
            && $idleMinutes >= (int) $web->auto_inactive_minutes
            && $this->markInactive($conversation)) {
            $totals['inactive']++;
        }
    }

    /**
     * The last visitor (incoming) message went unanswered: the most recent
     * non-internal message carries no agent user_id.
     */
    private function visitorIsWaiting(Conversation $conversation): bool
    {
        $lastItem = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('is_internal', false)
            ->where('type', 'message')
            ->latest('id')
            ->first(['id', 'user_id']);

        return $lastItem !== null && $lastItem->user_id === null;
    }

    /**
     * Set metadata.inactive_since once. Returns true only when newly flagged.
     */
    private function markInactive(Conversation $conversation): bool
    {
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];

        if (! empty($metadata['inactive_since'])) {
            return false;
        }

        $metadata['inactive_since'] = now()->toIso8601String();
        $conversation->update(['metadata' => $metadata]);

        return true;
    }
}
