<?php

namespace Modules\Campaign\Http\Controllers\Public;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Campaign\Support\MailgunWebhookSignature;
use Modules\CampaignSendingServers\Events\BounceDetected;
use Modules\CampaignSendingServers\Events\FeedbackLoopDetected;
use Modules\CampaignSendingServers\Models\SendingServer;

class ProviderWebhookController extends Controller
{
    /**
     * El {serverUid} es el UUID (no enumerable) del sending server, configurado
     * en la URL del webhook del proveedor y que NUNCA viaja en el email. Exigir
     * que corresponda a un servidor existente convierte el endpoint de
     * "cualquiera puede forjar bounces/quejas conociendo un message_id" a
     * "hay que conocer además el UUID secreto del servidor". No sustituye a la
     * verificación de firma HMAC/SNS por proveedor (pendiente: requiere almacenar
     * el signing secret de cada proveedor), pero cierra el acceso anónimo.
     */
    private function assertKnownServer(string $serverUid): void
    {
        abort_unless(
            SendingServer::query()->where('uid', $serverUid)->exists(),
            404
        );
    }

    /**
     * SendGrid Event Webhook
     * POST /campaign/webhooks/sendgrid/{serverUid}
     */
    public function sendgrid(Request $request, string $serverUid): JsonResponse
    {
        $this->assertKnownServer($serverUid);

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
        $server = SendingServer::query()->where('uid', $serverUid)->first();
        abort_unless($server !== null, 404);

        // Firma HMAC-SHA256 de Mailgun (timestamp+token). Fail-open: si el
        // servidor no tiene webhook_signing_secret configurado se mantiene solo el
        // gate del serverUid; al configurarlo, la firma pasa a ser obligatoria.
        abort_unless(
            MailgunWebhookSignature::valid($server->webhook_signing_secret, (array) $request->input('signature', [])),
            403,
        );

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
        $this->assertKnownServer($serverUid);

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
        $this->assertKnownServer($serverUid);

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
