<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

/**
 * Sends a best-effort "sender action" (seen receipt / typing indicator) to a
 * customer's external channel.
 *
 * Runs OUT OF BAND so opening a conversation or typing a reply doesn't block
 * on a Meta Graph API round trip — see MarkWhatsAppMessageReadJob for the
 * same rationale applied to inbound WhatsApp reads.
 */
class SendSenderActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 15;

    /**
     * @param  'whatsapp'|'facebook'|'instagram'  $channel
     * @param  string  $externalId  For facebook/instagram this is the recipient's
     *                              PSID/IGSID. For whatsapp's mark_seen this is
     *                              instead the external message id (wamid) —
     *                              WhatsApp read receipts are keyed off the
     *                              message, not the sender.
     * @param  'mark_seen'|'typing_on'|'typing_off'  $action
     */
    public function __construct(
        private readonly string $channel,
        private readonly string $externalId,
        private readonly string $action,
    ) {
        $this->onQueue('helpdesk-webhooks');
    }

    public function handle(
        FacebookMessengerService $facebook,
        InstagramService $instagram,
        WhatsAppBusinessService $whatsapp,
    ): void {
        match ($this->channel) {
            'facebook' => $facebook->sendSenderAction($this->externalId, $this->action),
            'instagram' => $instagram->sendSenderAction($this->externalId, $this->action),
            // WhatsApp has no typing indicator API; only the read receipt applies.
            'whatsapp' => $this->action === 'mark_seen' ? $whatsapp->markAsRead($this->externalId) : null,
            default => null,
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('SendSenderActionJob failed', [
            'channel' => $this->channel,
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);
    }
}
