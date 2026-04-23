<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Notifications\TicketMentionNotification;

class MentionService
{
    /**
     * Parse @mentions in a message body and send notifications.
     */
    public function notifyMentions(string $body, Ticket $ticket): void
    {
        if (! preg_match_all('/@([\w][\w\s]*?)(?=[,.\n]|$)/u', $body, $matches)) {
            return;
        }

        $currentUserId = auth()->id();
        $notified = [];

        foreach ($matches[1] as $name) {
            $name = trim($name);
            if (! $name) {
                continue;
            }

            $user = User::where('available', true)
                ->where('verified', true)
                ->whereRaw("TRIM(CONCAT(firstname, ' ', lastname)) LIKE ?", [$name.'%'])
                ->first();

            if ($user && $user->id !== $currentUserId && ! in_array($user->id, $notified)) {
                $notified[] = $user->id;
                $user->notify(new TicketMentionNotification($ticket, auth()->user()));
            }
        }
    }
}
