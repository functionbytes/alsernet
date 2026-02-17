<?php

namespace Modules\Attention\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Attention\Mail\AttentionAssignedMail;
use Modules\Attention\Mail\AttentionConfirmationMail;
use Modules\Attention\Mail\AttentionInProcessMail;
use Modules\Attention\Mail\AttentionResolutionMail;
use Modules\Attention\Models\Attention;
use Throwable;

/**
 * Service for handling PQRSF notifications.
 * Tries Mailer templates first, falls back to legacy Mailable classes.
 */
class AttentionNotificationService
{
    /**
     * Send confirmation email to citizen after submission
     */
    public function sendConfirmation(Attention $attention): bool
    {
        if ($attention->is_anonymous || ! $attention->customer_email) {
            return false;
        }

        // Try template-based email first
        if (AttentionEmailTemplateService::sendConfirmation($attention)) {
            return true;
        }

        // Fallback to legacy Mailable
        return $this->sendLegacy($attention, 'confirmation', new AttentionConfirmationMail($attention));
    }

    /**
     * Send notification to assigned user
     */
    public function notifyAssigned(Attention $attention): bool
    {
        if (! $attention->assignedUser || ! $attention->assignedUser->email) {
            return false;
        }

        // Try template-based email first
        if (AttentionEmailTemplateService::sendAssignedNotification($attention)) {
            return true;
        }

        // Fallback to legacy Mailable
        return $this->sendLegacy($attention, 'assigned', new AttentionAssignedMail($attention), $attention->assignedUser->email);
    }

    /**
     * Send in-process notification to citizen
     */
    public function sendInProcess(Attention $attention): bool
    {
        if ($attention->is_anonymous || ! $attention->customer_email) {
            return false;
        }

        // Try template-based email first
        if (AttentionEmailTemplateService::sendInProcessNotification($attention)) {
            return true;
        }

        // Fallback to legacy Mailable
        return $this->sendLegacy($attention, 'in_process', new AttentionInProcessMail($attention));
    }

    /**
     * Send resolution email to citizen
     */
    public function sendResolution(Attention $attention): bool
    {
        if ($attention->is_anonymous || ! $attention->customer_email) {
            return false;
        }

        // Try template-based email first
        if (AttentionEmailTemplateService::sendResolutionNotification($attention)) {
            return true;
        }

        // Fallback to legacy Mailable
        return $this->sendLegacy($attention, 'resolution', new AttentionResolutionMail($attention));
    }

    /**
     * Send email using legacy Mailable class and log it
     */
    private function sendLegacy(Attention $attention, string $type, object $mailable, ?string $recipient = null): bool
    {
        $recipient = $recipient ?? $attention->customer_email;

        try {
            Mail::to($recipient)->send($mailable);
            $this->logEmail($attention, $type, $recipient);

            return true;
        } catch (Throwable $e) {
            Log::error("Failed to send {$type} email (legacy)", [
                'radicado' => $attention->radicado,
                'email' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Log sent email in database
     */
    private function logEmail(Attention $attention, string $type, string $recipient): void
    {
        try {
            $attention->mails()->create([
                'email_type' => $type,
                'recipient_email' => $recipient,
                'subject' => $this->getEmailSubject($type, $attention),
                'body_html' => '',
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log email', [
                'radicado' => $attention->radicado,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get email subject based on type
     */
    private function getEmailSubject(string $type, Attention $attention): string
    {
        return match ($type) {
            'confirmation' => "Confirmacion de radicado {$attention->radicado}",
            'assigned' => "Nueva asignacion: {$attention->radicado}",
            'in_process' => "PQRSF En Proceso - Radicado: {$attention->radicado}",
            'resolution' => "Respuesta a su solicitud {$attention->radicado}",
            default => "Notificacion {$attention->radicado}",
        };
    }
}
