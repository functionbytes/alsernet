<?php

namespace Modules\HelpdeskEmailActivity\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Listeners\Concerns\InspectsMailMessage;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailLogLink;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Records an email as "queued" right before it is handed to the transport.
 *
 * Responsibilities:
 *  - Create the EmailLog row (status = queued).
 *  - Assign a Message-ID so {@see LogEmailSent} can correlate the MessageSent
 *    event back to this row (the transport may clone the message, so object
 *    identity cannot be relied upon).
 *  - Strip the internal X-* tracking headers so they never reach the recipient.
 *
 * Tracking must never break delivery, so every failure here is swallowed.
 */
class LogEmailQueued
{
    use InspectsMailMessage;

    public function handle(MessageSending $event): void
    {
        /** @var Email $message */
        $message = $event->message;

        // Read context/headers and strip internal X-* headers BEFORE any DB
        // operation so they can never reach the recipient even if the insert fails.
        $messageId = $this->ensureMessageId($message);
        $context = $this->contextOf($message, $event->data);
        $from = $this->fromAddressOf($message);
        $this->stripInternalHeaders($message);

        if (! helpdesk_emaillog_enabled()) {
            return;
        }

        try {
            // Solo se sigue lo que declara helpdeskemailactivity.tracked_modules —
            // no todo el correo saliente pasa por aquí queriendo píxel
            // (notificaciones, resets de contraseña, OTP...).
            //
            // Era una comparación literal contra 'HelpdeskTickets': cualquier
            // otro módulo que midiera aperturas veía 0% para siempre sin
            // ninguna pista de por qué.
            $trackedModules = (array) config('helpdeskemailactivity.tracked_modules', ['HelpdeskTickets']);
            $moduleWantsTracking = in_array($context['module'] ?? null, $trackedModules, true);

            // Interruptor global del panel de configuración (ver
            // EmailLogSettingsController) que además debe estar activo para
            // que el píxel llegue a insertarse. Se persiste en metadata para
            // que la vista de detalle sepa si "0 aperturas" significa de
            // verdad cero, o simplemente que este envío nunca tuvo píxel.
            $injectPixel = $moduleWantsTracking && $this->pixelTrackingEnabled();

            $emailLog = EmailLog::create([
                ...$context,
                ...$this->currentCauser(),
                'from_address' => $from?->getAddress() ?: config('mail.from.address') ?: 'unknown@localhost',
                'from_name' => $from?->getName() ?: null,
                'to_addresses' => $this->addressesOf($message->getTo()),
                'cc_addresses' => $this->addressesOf($message->getCc()) ?: null,
                'bcc_addresses' => $this->addressesOf($message->getBcc()) ?: null,
                'reply_to' => $this->addressesOf($message->getReplyTo()) ?: null,
                'subject' => (string) ($message->getSubject() ?? ''),
                'message_id' => $messageId,
                'body_html' => $this->bodyOf($message->getHtmlBody(), $context),
                'body_text' => $this->bodyOf($message->getTextBody(), $context),
                'raw_headers' => $this->headersOf($message, $context),
                'attachments' => $this->attachmentsOf($message) ?: null,
                'metadata' => [
                    ...$this->metaOf($message, $context),
                    'open_tracking_enabled' => $injectPixel,
                    // El click tracking no depende del interruptor del píxel —
                    // se guarda como flag propio (no reutilizando
                    // open_tracking_enabled) para poder divergir el alcance de
                    // cada uno el día que haga falta sin tocar filas ya escritas.
                    'click_tracking_enabled' => $moduleWantsTracking,
                ],
                'status' => EmailStatus::Queued,
            ]);

            if ($injectPixel) {
                $this->injectOpenTrackingPixel($message, $emailLog);
            }

            if ($moduleWantsTracking) {
                $this->injectClickTracking($message, $emailLog);
            }
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailActivity: failed to record queued email', ['exception' => $e]);
        }
    }

    private function injectOpenTrackingPixel(Email $message, EmailLog $emailLog): void
    {
        $html = $message->getHtmlBody();

        if (! is_string($html) || $html === '') {
            return;
        }

        try {
            $pixel = '<img src="'.route('helpdeskemailactivity.pixel', $emailLog).'" width="1" height="1" alt="" style="display:none" />';
            $message->html($html.$pixel);
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailActivity: failed to inject open-tracking pixel', ['exception' => $e]);
        }
    }

    /**
     * Reescribe cada enlace http(s) absoluto del cuerpo HTML para que pase
     * por la redirección propia de EmailClickTrackingController antes de
     * llegar al destino real — mismo principio que injectOpenTrackingPixel():
     * solo modifica lo que se ENVÍA, nunca el body_html/body_text ya
     * persistido (ese debe seguir siendo el contenido original, sin
     * reescribir, para que descargas/reenvíos repliquen el correo tal cual
     * se redactó).
     *
     * Se ignoran a propósito mailto:/tel:/anclas/rutas relativas (el regex
     * solo matchea http(s)://) — no tiene sentido "trackear" un clic que ni
     * siquiera sale del cliente de correo. Un mismo destino repetido varias
     * veces en el mismo correo reutiliza el mismo token (un solo
     * EmailLogLink), para no inflar el conteo de "enlaces distintos" con
     * duplicados del mismo CTA.
     */
    private function injectClickTracking(Email $message, EmailLog $emailLog): void
    {
        $html = $message->getHtmlBody();

        if (! is_string($html) || $html === '') {
            return;
        }

        try {
            $tokens = [];

            $rewritten = preg_replace_callback(
                '/(<a\b[^>]*\bhref\s*=\s*)(["\'])(https?:\/\/[^"\']+)\2/i',
                function (array $m) use ($emailLog, &$tokens) {
                    $url = html_entity_decode($m[3], ENT_QUOTES);

                    if (! isset($tokens[$url])) {
                        $tokens[$url] = EmailLogLink::create([
                            'email_log_id' => $emailLog->id,
                            'token' => Str::random(40),
                            'url' => $url,
                            'created_at' => now(),
                        ])->token;
                    }

                    $trackedUrl = route('helpdeskemailactivity.click', ['emailLog' => $emailLog, 'token' => $tokens[$url]]);

                    return $m[1].$m[2].$trackedUrl.$m[2];
                },
                $html
            );

            if (is_string($rewritten)) {
                $message->html($rewritten);
            }
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailActivity: failed to inject click tracking', ['exception' => $e]);
        }
    }

    private function ensureMessageId(Email $message): string
    {
        $headers = $message->getHeaders();

        if ($headers->has('Message-ID')) {
            return trim($headers->get('Message-ID')->getBodyAsString(), '<>');
        }

        $domain = Str::after(config('mail.from.address') ?: 'localhost', '@');
        $id = Str::orderedUuid()->toString().'@'.($domain ?: 'localhost');

        $headers->addIdHeader('Message-ID', $id);

        return $id;
    }

    private function stripInternalHeaders(Email $message): void
    {
        $headers = $message->getHeaders();

        foreach ((array) config('helpdeskemailactivity.internal_headers', []) as $name) {
            if ($headers->has($name)) {
                $headers->remove($name);
            }
        }
    }
}
