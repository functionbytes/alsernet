<?php

namespace Modules\Document\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Document\Entities\Document;

/**
 * Trait for controllers that send document emails
 *
 * Consolidates common logic for:
 * - Capturing adminId before session release
 * - Session management
 * - Document retrieval and authorization
 * - Error handling for email sending
 */
trait SendsDocumentEmails
{
    /**
     * Send a document email with standardized flow
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $uid  Document UID
     * @param  callable  $sender  Callback that sends the email. Receives ($document, $adminId, $request)
     * @param  string|null  $authPolicy  Authorization policy method (default: null = no authorization)
     */
    protected function sendEmail(
        Request $request,
        string $uid,
        callable $sender,
        ?string $authPolicy = null
    ): JsonResponse {
        // 1. Capture adminId BEFORE releasing session
        $adminId = auth()->id();

        // 2. Release session to prevent blocking
        $this->releaseSession($request);

        // 3. Find document
        $document = Document::where('uid', $uid)->firstOrFail();

        // 4. Authorize if policy specified
        // if ($authPolicy) {
        //   $this->authorize($authPolicy, $document);
        // }

        // 5. Check for rate limiting BEFORE calling sender
        $emailType = $this->getEmailTypeFromRequest($request);
        $rateLimit = $this->checkRateLimit($document, $emailType);

        if ($rateLimit['limited']) {
            return response()->json([
                'success' => false,
                'error_type' => 'rate_limit',
                'message' => 'Límite de envío alcanzado',
                'retry_after' => $rateLimit['retry_after'],
                'seconds_remaining' => $rateLimit['seconds_remaining'],
            ], 429);
        }

        // 6. Check for missing customer email
        if (empty($document->customer_email)) {
            return response()->json([
                'success' => false,
                'error_type' => 'missing_email',
                'message' => 'El documento no tiene email del cliente registrado.',
            ], 422);
        }

        // 7. Call sender callback and handle response
        $sent = $sender($document, $adminId, $request);

        // 8. Return standardized response
        if (! $sent) {
            return response()->json([
                'success' => false,
                'error_type' => 'send_failed',
                'message' => 'No se pudo enviar el email. Intenta de nuevo en unos segundos.',
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email enviado correctamente',
            'recipient' => $document->customer_email,
        ]);
    }

    /**
     * Check if email sending is rate limited
     */
    private function checkRateLimit($document, ?string $emailType = 'request'): array
    {
        $rateLimitKey = "email_sent:{$document->id}:{$emailType}";

        if (\Illuminate\Support\Facades\Cache::has($rateLimitKey)) {
            $retryTime = \Illuminate\Support\Facades\Cache::get($rateLimitKey);
            $secondsRemaining = now()->diffInSeconds($retryTime, false);

            // Redondear hacia arriba para asegurar que el usuario espere tiempo suficiente
            $secondsRemainingRounded = (int) ceil(max(0, $secondsRemaining));

            return [
                'limited' => true,
                'retry_after' => $retryTime->toIso8601String(),
                'seconds_remaining' => $secondsRemainingRounded,
            ];
        }

        return ['limited' => false];
    }

    /**
     * Get email type from request path and context
     */
    private function getEmailTypeFromRequest(Request $request): string
    {
        $path = $request->path();

        // Map URL patterns to email types
        $patterns = [
            'send-notification' => 'request',
            'send-reminder' => 'reminder',
            'send-missing' => 'missing',
            'send-approval' => 'approval',
            'send-rejection' => 'rejection',
            'send-custom-email' => 'custom',
            'send-upload-confirmation' => 'upload',
            'request-initial' => 'request',
            'request-missing' => 'missing',
        ];

        foreach ($patterns as $pattern => $type) {
            if (str_contains($path, $pattern)) {
                return $type;
            }
        }

        // Default to 'request' if not determinable
        return 'request';
    }

    /**
     * Release session to prevent blocking concurrent requests
     *
     * @param  Request  $request  The HTTP request
     */
    protected function releaseSession(Request $request): void
    {
        if ($request->hasSession()) {
            session()->save();
            session()->migrate(false);
        }
    }

    /**
     * Validate request data with custom messages
     *
     * @param  Request  $request  The HTTP request
     * @param  array  $rules  Validation rules
     * @param  array  $messages  Custom error messages
     * @return array Validated data
     */
    protected function validateEmailRequest(Request $request, array $rules, array $messages = []): array
    {
        return $request->validate($rules, $messages);
    }
}
