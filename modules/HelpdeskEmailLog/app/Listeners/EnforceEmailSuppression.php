<?php

namespace Modules\HelpdeskEmailLog\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailLog\Listeners\Concerns\InspectsMailMessage;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailSuppression;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Bloquea el envío real a una dirección en la lista de supresión —
 * aprovecha que Illuminate\Mail\Mailer::shouldSendMessage() cancela el
 * envío si CUALQUIER listener de MessageSending retorna exactamente
 * `false` (mecanismo nativo de Laravel, no hace falta nada más para
 * impedir el envío).
 *
 * Registrado DESPUÉS de LogEmailQueued (ver
 * HelpdeskEmailLogServiceProvider::registerListeners()) — Illuminate\Events\
 * Dispatcher::until() llama a los listeners de un evento en orden de
 * registro y se detiene en el primero que devuelve algo distinto de null:
 * LogEmailQueued::handle() es void (siempre null), así que nunca detiene la
 * cadena y SIEMPRE crea la fila 'queued'; este listener corre después y
 * decide si cancela. Si el orden fuera al revés, al devolver false aquí la
 * cadena cortaría y LogEmailQueued nunca correría — se perdería el registro
 * de "se intentó enviar, se bloqueó" que precisamente queremos conservar.
 *
 * Funciona aunque helpdesk_emaillog_enabled() esté apagado — enforcement de
 * supresión es una obligación de cumplimiento (no reenviar a quien se dio
 * de baja o tuvo un rebote permanente), no una feature opcional de logging.
 * Evalúa TODOS los destinatarios (To, Cc y Bcc): las direcciones suprimidas
 * se eliminan del mensaje antes de que salga y el envío solo se cancela por
 * completo (retornando `false`) si no queda ningún destinatario válido —
 * así un Cc/Bcc suprimido ya no puede colarse simplemente por no ser el
 * primer "to" (comportamiento previo de esta v1: solo miraba to[0]).
 */
class EnforceEmailSuppression
{
    use InspectsMailMessage;

    public function handle(MessageSending $event): bool
    {
        /** @var Email $message */
        $message = $event->message;

        $context = $this->contextOf($message, $event->data);
        $module = $context['module'] ?? null;

        $suppressed = [];

        $to = $this->filterSuppressed($message->getTo(), $module, $suppressed);
        $cc = $this->filterSuppressed($message->getCc(), $module, $suppressed);
        $bcc = $this->filterSuppressed($message->getBcc(), $module, $suppressed);

        if ($suppressed === []) {
            return true;
        }

        $message->to(...$to);
        $message->cc(...$cc);
        $message->bcc(...$bcc);

        if ($to !== [] || $cc !== [] || $bcc !== []) {
            // Envío parcial: al menos un destinatario suprimido fue
            // eliminado, pero quedan otros legítimos — se deja pasar el
            // resto y solo se registra el intento bloqueado.
            $this->logSuppressedAttempt($message, $suppressed);

            return true;
        }

        try {
            $this->markLogAsSuppressed($message, $suppressed);
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailLog: fallo marcando envío suprimido', ['exception' => $e]);
        }

        return false;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @param  list<string>  $suppressed
     * @return list<Address>
     */
    private function filterSuppressed(array $addresses, ?string $module, array &$suppressed): array
    {
        $kept = [];

        foreach ($addresses as $address) {
            $email = $address->getAddress();

            if (EmailSuppression::isSuppressed($email, $module)) {
                $suppressed[] = $email;

                continue;
            }

            $kept[] = $address;
        }

        return $kept;
    }

    /**
     * @param  list<string>  $suppressed
     */
    private function markLogAsSuppressed(Email $message, array $suppressed): void
    {
        $messageId = $this->messageIdOf($message);

        $log = $messageId
            ? EmailLog::where('message_id', $messageId)->latest('id')->first()
            : null;

        $log?->markAsSuppressed('Destinatario(s) en lista de supresión: '.implode(', ', $suppressed));
    }

    /**
     * @param  list<string>  $suppressed
     */
    private function logSuppressedAttempt(Email $message, array $suppressed): void
    {
        try {
            $messageId = $this->messageIdOf($message);

            $log = $messageId
                ? EmailLog::where('message_id', $messageId)->latest('id')->first()
                : null;

            $log?->addSuppressedRecipientsNote($suppressed);
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailLog: fallo registrando destinatarios suprimidos parcialmente', ['exception' => $e]);
        }
    }
}
