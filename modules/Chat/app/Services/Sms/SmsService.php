<?php

namespace Modules\Chat\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Channels\Sms;

class SmsService
{
    /**
     * Send test SMS message via configured provider.
     */
    public function sendTestMessage(Sms $sms, string $toNumber, ?string $message = null): array
    {
        $inboxName = $sms->inbox?->name ?? 'Chat';
        $message = $message ?? "This is a test message from {$inboxName}. Your SMS channel is working correctly!";

        try {
            return match ($sms->provider) {
                'twilio' => $this->sendViaTwilio($sms, $toNumber, $message),
                'bandwidth' => $this->sendViaBandwidth($sms, $toNumber, $message),
                'telnyx' => $this->sendViaTelnyx($sms, $toNumber, $message),
                default => throw new \Exception("Unsupported SMS provider: {$sms->provider}"),
            };
        } catch (\Exception $e) {
            Log::error('SMS test send failed', [
                'provider' => $sms->provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS via Twilio.
     */
    protected function sendViaTwilio(Sms $sms, string $toNumber, string $message): array
    {
        $accountSid = $sms->api_key;
        $authToken = $sms->api_secret;
        $fromNumber = $sms->phone_number;

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'To' => $toNumber,
                'From' => $fromNumber,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'message' => 'Test SMS sent successfully via Twilio!',
                'message_id' => $data['sid'] ?? null,
            ];
        }

        $error = $response->json('message') ?? 'Unknown error';

        throw new \Exception("Twilio API error: {$error}");
    }

    /**
     * Send SMS via Bandwidth.
     */
    protected function sendViaBandwidth(Sms $sms, string $toNumber, string $message): array
    {
        $userId = $sms->api_key;
        $apiToken = $sms->api_secret;
        $applicationId = $sms->application_id;
        $fromNumber = $sms->phone_number;

        if (! $applicationId) {
            throw new \Exception('Application ID is required for Bandwidth');
        }

        $response = Http::withBasicAuth($userId, $apiToken)
            ->post("https://messaging.bandwidth.com/api/v2/users/{$userId}/messages", [
                'applicationId' => $applicationId,
                'to' => [$toNumber],
                'from' => $fromNumber,
                'text' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'message' => 'Test SMS sent successfully via Bandwidth!',
                'message_id' => $data['id'] ?? null,
            ];
        }

        $error = $response->json('message') ?? 'Unknown error';

        throw new \Exception("Bandwidth API error: {$error}");
    }

    /**
     * Send SMS via Telnyx.
     */
    protected function sendViaTelnyx(Sms $sms, string $toNumber, string $message): array
    {
        $apiKey = $sms->api_key;
        $fromNumber = $sms->phone_number;

        $response = Http::withToken($apiKey)
            ->post('https://api.telnyx.com/v2/messages', [
                'from' => $fromNumber,
                'to' => $toNumber,
                'text' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'message' => 'Test SMS sent successfully via Telnyx!',
                'message_id' => $data['data']['id'] ?? null,
            ];
        }

        $errors = $response->json('errors.0.detail') ?? 'Unknown error';

        throw new \Exception("Telnyx API error: {$errors}");
    }

    /**
     * Send SMS message for conversations (non-test).
     */
    public function sendMessage(Sms $sms, string $toNumber, string $message): array
    {
        return $this->sendTestMessage($sms, $toNumber, $message);
    }
}
