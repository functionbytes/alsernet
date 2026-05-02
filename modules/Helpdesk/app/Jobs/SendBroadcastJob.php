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

class SendBroadcastJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        private readonly Broadcast $broadcast
    ) {
        $this->onQueue('broadcasts');
    }

    public function handle(
        WhatsAppBusinessService $whatsapp,
        FacebookMessengerService $facebook,
        InstagramService $instagram
    ): void {
        $this->broadcast->markAsSending();

        $recipients = BroadcastRecipient::query()
            ->where('broadcast_id', $this->broadcast->id)
            ->where('status', 'pending')
            ->with('customer')
            ->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            $customer = $recipient->customer;

            if (! $customer) {
                $recipient->markAsFailed('Customer not found');
                $failedCount++;

                continue;
            }

            try {
                $externalId = $this->sendToChannel($customer, $whatsapp, $facebook, $instagram);
                $recipient->markAsSent($externalId ?? '');
                $sentCount++;
            } catch (\Throwable $e) {
                Log::error('SendBroadcastJob: recipient delivery failed', [
                    'broadcast_id' => $this->broadcast->id,
                    'customer_id' => $customer->id,
                    'channel' => $this->broadcast->channel,
                    'error' => $e->getMessage(),
                ]);

                $recipient->markAsFailed($e->getMessage());
                $failedCount++;
            }
        }

        $totalCount = $sentCount + $failedCount;
        $allFailed = $totalCount > 0 && $sentCount === 0;

        $this->broadcast->update([
            'status' => $allFailed ? 'failed' : 'sent',
            'sent_at' => now(),
            'recipients_count' => $totalCount,
            'delivered_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);
    }

    private function sendToChannel(
        Customer $customer,
        WhatsAppBusinessService $whatsapp,
        FacebookMessengerService $facebook,
        InstagramService $instagram
    ): ?string {
        $body = $this->broadcast->body ?? '';

        return match ($this->broadcast->channel) {
            'whatsapp' => $this->sendWhatsApp($customer, $body, $whatsapp),
            'facebook' => $this->sendFacebook($customer, $body, $facebook),
            'instagram' => $this->sendInstagram($customer, $body, $instagram),
            'email' => $this->sendEmail($customer, $body),
            'web' => null,
            default => throw new \RuntimeException("Unsupported channel: {$this->broadcast->channel}"),
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

    private function sendEmail(Customer $customer, string $body): ?string
    {
        if (! $customer->email) {
            throw new \RuntimeException('Customer has no email address');
        }

        $subject = $this->broadcast->name;

        Mail::html($body, function ($mail) use ($customer, $subject) {
            $mail->to($customer->email)->subject($subject);
        });

        return null;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendBroadcastJob: job failed entirely', [
            'broadcast_id' => $this->broadcast->id,
            'error' => $exception->getMessage(),
        ]);

        $this->broadcast->markAsFailed();
    }
}
