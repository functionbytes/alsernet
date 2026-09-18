<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\ProcessEmailInboundJob;

class EmailInboundController extends Controller
{
    public function handle(Request $request, string $provider): JsonResponse
    {
        if (! $this->verifySignature($request, $provider)) {
            Log::warning('Email inbound: invalid signature', [
                'provider' => $provider,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $parsed = $this->parsePayload($request, $provider);

        if (! $parsed || blank($parsed['from'])) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        ProcessEmailInboundJob::dispatch($parsed);

        return response()->json(['success' => true]);
    }

    private function verifySignature(Request $request, string $provider): bool
    {
        return match ($provider) {
            'mailgun' => $this->verifyMailgun($request),
            'sendgrid' => $this->verifySendgrid($request),
            'postmark' => $this->verifyPostmark($request),
            'generic' => app()->environment('local', 'testing'),  // Sin verificación de firma; solo dev/test
            default => false,
        };
    }

    private function verifyMailgun(Request $request): bool
    {
        $signingKey = config('helpdesk.email_inbound.mailgun_signing_key');

        if (! $signingKey) {
            return false;
        }

        $timestamp = $request->input('signature.timestamp', $request->input('timestamp', ''));
        $token = $request->input('signature.token', $request->input('token', ''));
        $signature = $request->input('signature.signature', $request->input('signature', ''));

        return hash_equals(
            hash_hmac('sha256', $timestamp.$token, $signingKey),
            $signature,
        );
    }

    private function verifySendgrid(Request $request): bool
    {
        $secret = config('helpdesk.email_inbound.sendgrid_webhook_secret');

        if (! $secret) {
            return $this->allowWithoutSecret('sendgrid');
        }

        $signature = $request->header('X-Sendgrid-Signature', $request->input('signature', ''));

        return hash_equals($secret, $signature);
    }

    private function verifyPostmark(Request $request): bool
    {
        $secret = config('helpdesk.email_inbound.postmark_webhook_secret');

        if (! $secret) {
            return $this->allowWithoutSecret('postmark');
        }

        $signature = $request->header('X-Postmark-Signature', '');
        $body = $request->getContent();

        return hash_equals(
            base64_encode(hash_hmac('sha256', $body, $secret, true)),
            $signature,
        );
    }

    /**
     * Fail-closed cuando falta el secreto del proveedor: solo se permite en
     * desarrollo local para no bloquear pruebas manuales.
     */
    private function allowWithoutSecret(string $provider): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        Log::warning('Email inbound rechazado: secreto de webhook no configurado.', ['provider' => $provider]);

        return false;
    }

    /**
     * @return array{from: string, from_name: ?string, subject: string, body: string, message_id: ?string}|null
     */
    private function parsePayload(Request $request, string $provider): ?array
    {
        return match ($provider) {
            'mailgun' => [
                'from' => $request->input('sender', $request->input('from', '')),
                'from_name' => null,
                'subject' => $request->input('subject', ''),
                'body' => $request->input('stripped-text', $request->input('body-plain', '')),
                'message_id' => $request->input('Message-Id'),
            ],
            'sendgrid' => [
                'from' => $request->input('from', ''),
                'from_name' => null,
                'subject' => $request->input('subject', ''),
                'body' => $request->input('text', $request->input('html', '')),
                'message_id' => $request->header('X-Message-Id'),
            ],
            'postmark' => [
                'from' => $request->input('From', ''),
                'from_name' => $request->input('FromName'),
                'subject' => $request->input('Subject', ''),
                'body' => $request->input('TextBody', $request->input('HtmlBody', '')),
                'message_id' => $request->input('MessageID'),
            ],
            'generic' => [
                'from' => $request->input('from', ''),
                'from_name' => $request->input('from_name'),
                'subject' => $request->input('subject', ''),
                'body' => $request->input('body', ''),
                'message_id' => $request->input('message_id'),
            ],
            default => null,
        };
    }
}
