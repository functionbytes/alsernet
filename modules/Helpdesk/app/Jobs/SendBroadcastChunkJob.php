<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Modules\Helpdesk\Models\Campaigns\BroadcastRecipient;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

/**
 * Envía un LOTE de destinatarios de un broadcast. SendBroadcastJob (dispatcher)
 * trocea la campaña en estos jobs para que las campañas grandes no revienten el
 * timeout de un único job y para tener reintento PARCIAL por lote. El último
 * lote en terminar (sin destinatarios 'pending') finaliza el estado del broadcast.
 */
class SendBroadcastChunkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 30;

    /**
     * @param  array<int, int>  $recipientIds
     */
    public function __construct(
        private readonly int $broadcastId,
        private readonly array $recipientIds,
    ) {
        $this->onQueue('broadcasts');
    }

    public function handle(
        WhatsAppBusinessService $whatsapp,
        FacebookMessengerService $facebook,
        InstagramService $instagram,
    ): void {
        $broadcast = Broadcast::find($this->broadcastId);

        if (! $broadcast) {
            return;
        }

        $recipients = BroadcastRecipient::query()
            ->whereIn('id', $this->recipientIds)
            ->where('status', 'pending')
            ->with('customer')
            ->get();

        foreach ($recipients as $recipient) {
            $customer = $recipient->customer;

            if (! $customer) {
                $recipient->markAsFailed('Customer not found');

                continue;
            }

            try {
                $externalId = $this->sendToChannel($broadcast, $customer, $whatsapp, $facebook, $instagram);
                $recipient->markAsSent($externalId ?? '');
            } catch (\Throwable $e) {
                Log::error('SendBroadcastChunkJob: recipient delivery failed', [
                    'broadcast_id' => $broadcast->id,
                    'customer_id' => $customer->id,
                    'channel' => $broadcast->channel,
                    'error' => $e->getMessage(),
                ]);

                $recipient->markAsFailed($e->getMessage());
            }
        }

        $this->finalizeIfDone($broadcast);
    }

    /**
     * Finaliza el estado del broadcast cuando ya no quedan destinatarios pendientes.
     * Idempotente: si dos lotes terminan a la vez, ambos calculan el mismo total.
     */
    private function finalizeIfDone(Broadcast $broadcast): void
    {
        $pending = BroadcastRecipient::query()
            ->where('broadcast_id', $broadcast->id)
            ->where('status', 'pending')
            ->count();

        if ($pending > 0) {
            return;
        }

        $sent = BroadcastRecipient::query()->where('broadcast_id', $broadcast->id)->where('status', 'sent')->count();
        $failed = BroadcastRecipient::query()->where('broadcast_id', $broadcast->id)->where('status', 'failed')->count();
        $total = $sent + $failed;

        $broadcast->update([
            'status' => ($total > 0 && $sent === 0) ? 'failed' : 'sent',
            'sent_at' => now(),
            'recipients_count' => $total,
            'delivered_count' => $sent,
            'failed_count' => $failed,
        ]);
    }

    private function sendToChannel(
        Broadcast $broadcast,
        Customer $customer,
        WhatsAppBusinessService $whatsapp,
        FacebookMessengerService $facebook,
        InstagramService $instagram,
    ): ?string {
        $body = $broadcast->body ?? '';

        return match ($broadcast->channel) {
            'whatsapp' => $this->sendWhatsApp($customer, $body, $whatsapp),
            'facebook' => $this->sendFacebook($customer, $body, $facebook),
            'instagram' => $this->sendInstagram($customer, $body, $instagram),
            'email' => $this->sendEmail($customer, $body, $broadcast->name),
            'web' => null,
            default => throw new \RuntimeException("Unsupported channel: {$broadcast->channel}"),
        };
    }

    private function sendWhatsApp(Customer $customer, string $body, WhatsAppBusinessService $whatsapp): ?string
    {
        $phone = $customer->whatsapp_phone ?? $customer->phone;

        if (! $phone) {
            throw new \RuntimeException('Customer has no phone number');
        }

        return $whatsapp->sendText($phone, $body);
    }

    private function sendFacebook(Customer $customer, string $body, FacebookMessengerService $facebook): ?string
    {
        if (! $customer->facebook_psid) {
            throw new \RuntimeException('Customer has no Facebook PSID');
        }

        return $facebook->sendText($customer->facebook_psid, $body);
    }

    private function sendInstagram(Customer $customer, string $body, InstagramService $instagram): ?string
    {
        if (! $customer->instagram_id) {
            throw new \RuntimeException('Customer has no Instagram ID');
        }

        return $instagram->sendText($customer->instagram_id, $body);
    }

    private function sendEmail(Customer $customer, string $body, string $subject): ?string
    {
        if (! $customer->email) {
            throw new \RuntimeException('Customer has no email address');
        }

        Mail::html($body, function ($mail) use ($customer, $subject) {
            $mail->to($customer->email)->subject($subject);
        });

        return null;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendBroadcastChunkJob failed', [
            'broadcast_id' => $this->broadcastId,
            'error' => $exception->getMessage(),
        ]);
    }
}
