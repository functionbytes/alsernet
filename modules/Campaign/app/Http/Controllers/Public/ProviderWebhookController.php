<?php

namespace Modules\Campaign\Http\Controllers\Public;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CampaignSendingServers\Events\BounceDetected;
use Modules\CampaignSendingServers\Events\FeedbackLoopDetected;

class ProviderWebhookController extends Controller
{
    /**
     * SendGrid Event Webhook
     * POST /campaign/webhooks/sendgrid/{serverUid}
     */
    public function sendgrid(Request $request, string $serverUid): JsonResponse
    {
        foreach ($request->all() as $event) {
            $eventType = $event['event'] ?? null;
            $messageId = $event['sg_message_id'] ?? null;
            $email = $event['email'] ?? null;
            $reason = $event['reason'] ?? ($event['response'] ?? null);

            if (! $messageId) {
                continue;
            }

            if (in_array($eventType, ['bounce', 'dropped'], true)) {
                BounceDetected::dispatch($messageId, $email, $reason, $serverUid);
            }

            if ($eventType === 'spamreport') {
                FeedbackLoopDetected::dispatch($messageId, $email, $serverUid);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Mailgun Event Webhook
     * POST /campaign/webhooks/mailgun/{serverUid}
     */
    public function mailgun(Request $request, string $serverUid): JsonResponse
    {
        $eventData = $request->input('event-data', []);
        $eventType = $eventData['event'] ?? null;
        $messageId = $eventData['message']['headers']['message-id'] ?? null;
        $email = $eventData['recipient'] ?? null;
        $reason = $eventData['delivery-status']['description']
            ?? ($eventData['delivery-status']['message'] ?? null);

        if (! $messageId) {
            return response()->json(['status' => 'ignored']);
        }

        if (in_array($eventType, ['failed', 'rejected'], true)) {
            BounceDetected::dispatch($messageId, $email, $reason, $serverUid);
        }

        if ($eventType === 'complained') {
            FeedbackLoopDetected::dispatch($messageId, $email, $serverUid);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Amazon SES Event Webhook (SNS JSON)
     * POST /campaign/webhooks/ses/{serverUid}
     */
    public function ses(Request $request, string $serverUid): JsonResponse
    {
        $payload = $request->all();

        // SNS SubscriptionConfirmation
        if (($payload['Type'] ?? null) === 'SubscriptionConfirmation') {
            return response()->json(['status' => 'confirmed']);
        }

        $message = json_decode($payload['Message'] ?? '{}', true);
        $eventType = $message['eventType'] ?? $message['notificationType'] ?? null;
        $mail = $message['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;
        $email = $mail['destination'][0] ?? null;

        if (! $messageId) {
            return response()->json(['status' => 'ignored']);
        }

        if ($eventType === 'Bounce') {
            $bounce = $message['bounce'] ?? [];
            $reason = $bounce['bouncedRecipients'][0]['diagnosticCode'] ?? null;
            BounceDetected::dispatch($messageId, $email, $reason, $serverUid);
        }

        if ($eventType === 'Complaint') {
            FeedbackLoopDetected::dispatch($messageId, $email, $serverUid);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Postmark Event Webhook
     * POST /campaign/webhooks/postmark/{serverUid}
     */
    public function postmark(Request $request, string $serverUid): JsonResponse
    {
        $eventType = $request->input('Type') ?? $request->input('RecordType') ?? null;
        $messageId = $request->input('MessageID') ?? $request->input('MessageId') ?? null;
        $email = $request->input('Recipient') ?? $request->input('Email') ?? null;
        $reason = $request->input('Description') ?? $request->input('Details') ?? null;

        if (! $messageId) {
            return response()->json(['status' => 'ignored']);
        }

        if (in_array($eventType, ['Bounce', 'Delivery', 'SMTPAPIBounce'], true)) {
            $bounceType = $request->input('TypeCode') ?? $request->input('BounceType') ?? null;
            if ($bounceType !== 'Delivery') {
                BounceDetected::dispatch((string) $messageId, $email, $reason, $serverUid);
            }
        }

        if ($eventType === 'SpamComplaint') {
            FeedbackLoopDetected::dispatch((string) $messageId, $email, $serverUid);
        }

        return response()->json(['status' => 'ok']);
    }
}
