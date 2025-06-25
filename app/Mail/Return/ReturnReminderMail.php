<?php

// 1. app/Mail/ReturnReminderMail.php

namespace App\Mail\Return;

use App\Models\Return\ReturnCommunication;
use App\Models\Return\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ReturnReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ReturnRequest $return;
    public ReturnCommunication $communication;
    public array $emailData;
    public string $reminderType;

    /**
     * Create a new message instance.
     */
    public function __construct(ReturnRequest $return, ReturnCommunication $communication, array $emailData)
    {
        $this->return = $return;
        $this->communication = $communication;
        $this->emailData = $emailData;
        $this->reminderType = $emailData['reminder_type'] ?? 'general';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->reminderType) {
            'shipping' => "⏰ Recordatorio: Envíe su devolución - #{$this->return->number}",
            'expiring' => "⚠️ Su devolución está por expirar - #{$this->return->number}",
            'action_required' => "📋 Acción requerida en su devolución - #{$this->return->number}",
            default => "🔔 Recordatorio sobre su devolución - #{$this->return->number}"
        };

        return new Envelope(
            subject: $subject,
            replyTo: [
                config('returns.notifications.reply_to', 'soporte@ejemplo.com'),
            ],
            tags: ['return-reminder', $this->reminderType],
            metadata: [
                'return_id' => $this->return->id,
                'communication_id' => $this->communication->id,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = $this->determineTemplate();

        return new Content(
            view: $template,
            with: array_merge($this->emailData, [
                'return' => $this->return,
                'communication' => $this->communication,
                'days_pending' => $this->return->created_at->diffInDays(now()),
                'days_until_expiration' => $this->calculateDaysUntilExpiration(),
                'reminder_type' => $this->reminderType,
                'tracking_pixel' => $this->generateTrackingPixel(),
                'action_url' => $this->generateActionUrl(),
                'unsubscribe_url' => $this->generateUnsubscribeUrl(),
            ])
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        // Adjuntar etiqueta de envío si es recordatorio de envío
        if ($this->reminderType === 'shipping' && $this->return->label_path) {
            $attachments[] = Attachment::fromPath($this->return->label_path)
                ->as("etiqueta_devolucion_{$this->return->number}.pdf")
                ->withMime('application/pdf');
        }

        // Adjuntar instrucciones si es necesario
        if (in_array($this->reminderType, ['shipping', 'action_required'])) {
            $instructionsPath = $this->generateInstructionsPDF();
            if ($instructionsPath) {
                $attachments[] = Attachment::fromPath($instructionsPath)
                    ->as('instrucciones_devolucion.pdf')
                    ->withMime('application/pdf');
            }
        }

        return $attachments;
    }

    /**
     * Determinar la plantilla a usar según el tipo de recordatorio
     */
    private function determineTemplate(): string
    {
        return match($this->reminderType) {
            'shipping' => 'emails.returns.reminders.shipping',
            'expiring' => 'emails.returns.reminders.expiring',
            'action_required' => 'emails.returns.reminders.action-required',
            default => 'emails.returns.reminders.general'
        };
    }

    /**
     * Calcular días hasta la expiración
     */
    private function calculateDaysUntilExpiration(): int
    {
        $expirationDays = config('returns.expiration_days', 30);
        $daysSinceCreated = $this->return->created_at->diffInDays(now());

        return max(0, $expirationDays - $daysSinceCreated);
    }

    /**
     * Generar pixel de tracking
     */
    private function generateTrackingPixel(): string
    {
        $trackingId = $this->communication->metadata['tracking_id'] ?? null;

        if (!$trackingId) {
            return '';
        }

        return route('email.track', ['id' => $trackingId]);
    }

    /**
     * Generar URL de acción principal
     */
    private function generateActionUrl(): string
    {
        $token = $this->generateAccessToken();

        return route('customer.returns.show', [
            'return' => $this->return->id,
            'token' => $token
        ]);
    }

    /**
     * Generar token de acceso temporal
     */
    private function generateAccessToken(): string
    {
        $token = \Str::random(32);

        cache()->put(
            "return_access_{$this->return->id}_{$token}",
            true,
            now()->addHours(48) // 48 horas para recordatorios
        );

        return $token;
    }

    /**
     * Generar URL de desuscripción
     */
    private function generateUnsubscribeUrl(): string
    {
        $token = encrypt([
            'return_id' => $this->return->id,
            'email' => $this->return->customer_email,
            'type' => 'reminders'
        ]);

        return route('unsubscribe', ['token' => $token]);
    }

    /**
     * Generar PDF de instrucciones
     */
    private function generateInstructionsPDF(): ?string
    {
        // Implementar generación de PDF con instrucciones
        // Por ahora retornar null
        return null;
    }

    /**
     * Get the message headers.
     */
    public function headers(): array
    {
        return [
            'X-Return-ID' => $this->return->id,
            'X-Communication-ID' => $this->communication->id,
            'X-Reminder-Type' => $this->reminderType,
            'List-Unsubscribe' => '<' . $this->generateUnsubscribeUrl() . '>',
        ];
    }

}
