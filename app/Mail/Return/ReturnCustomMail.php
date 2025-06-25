<?php

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
use Illuminate\Support\HtmlString;

class ReturnCustomMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ReturnRequest $return;
    public ReturnCommunication $communication;
    public array $customData;
    public bool $useTemplate;

    /**
     * Create a new message instance.
     */
    public function __construct(ReturnRequest $return, ReturnCommunication $communication, array $customData)
    {
        $this->return = $return;
        $this->communication = $communication;
        $this->customData = $customData;
        $this->useTemplate = $customData['use_template'] ?? true;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customData['subject'] ?? "Información sobre su devolución #{$this->return->number}",
            from: $this->getFromAddress(),
            replyTo: $this->getReplyTo(),
            cc: $this->getCcRecipients(),
            bcc: $this->getBccRecipients(),
            tags: ['return-custom', $this->customData['tag'] ?? 'general'],
            metadata: [
                'return_id' => $this->return->id,
                'communication_id' => $this->communication->id,
                'custom' => true,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        if ($this->useTemplate) {
            return new Content(
                view: 'emails.returns.custom',
                with: $this->prepareTemplateData()
            );
        }

        // Para contenido HTML directo
        return new Content(
            html: $this->prepareHtmlContent(),
            text: $this->prepareTextContent()
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        // Archivos adjuntos personalizados
        if (!empty($this->customData['attachments'])) {
            foreach ($this->customData['attachments'] as $attachment) {
                if (is_string($attachment) && file_exists($attachment)) {
                    $attachments[] = Attachment::fromPath($attachment);
                } elseif (is_array($attachment)) {
                    $attachments[] = Attachment::fromPath($attachment['path'])
                        ->as($attachment['name'] ?? basename($attachment['path']))
                        ->withMime($attachment['mime'] ?? 'application/octet-stream');
                }
            }
        }

        // Incluir documentos de la devolución si se solicita
        if ($this->customData['include_return_documents'] ?? false) {
            $attachments = array_merge($attachments, $this->getReturnDocuments());
        }

        return $attachments;
    }

    /**
     * Preparar datos para la plantilla
     */
    private function prepareTemplateData(): array
    {
        return [
            'return' => $this->return,
            'communication' => $this->communication,
            'content' => new HtmlString($this->customData['content'] ?? ''),
            'show_header' => $this->customData['show_header'] ?? true,
            'show_footer' => $this->customData['show_footer'] ?? true,
            'custom_css' => $this->customData['custom_css'] ?? null,
            'action_buttons' => $this->prepareActionButtons(),
            'additional_info' => $this->customData['additional_info'] ?? [],
            'signature' => $this->customData['signature'] ?? $this->getDefaultSignature(),
            'tracking_pixel' => $this->generateTrackingPixel(),
        ];
    }

    /**
     * Preparar contenido HTML directo
     */
    private function prepareHtmlContent(): HtmlString
    {
        $content = $this->customData['content'] ?? '';

        // Reemplazar variables en el contenido
        $content = $this->replaceVariables($content);

        // Envolver en layout básico si es necesario
        if ($this->customData['wrap_in_layout'] ?? true) {
            $content = view('emails.layouts.basic', [
                'content' => $content,
                'return' => $this->return
            ])->render();
        }

        return new HtmlString($content);
    }

    /**
     * Preparar contenido de texto plano
     */
    private function prepareTextContent(): string
    {
        if (isset($this->customData['text_content'])) {
            return $this->replaceVariables($this->customData['text_content']);
        }

        // Convertir HTML a texto plano
        $content = strip_tags($this->customData['content'] ?? '');
        return $this->replaceVariables($content);
    }

    /**
     * Reemplazar variables en el contenido
     */
    private function replaceVariables(string $content): string
    {
        $variables = [
            '{{return_number}}' => $this->return->number,
            '{{customer_name}}' => $this->return->customer_name,
            '{{customer_email}}' => $this->return->customer_email,
            '{{return_status}}' => $this->return->status_label,
            '{{return_date}}' => $this->return->created_at->format('d/m/Y'),
            '{{original_amount}}' => number_format($this->return->original_amount, 2) . ' €',
            '{{final_refund}}' => number_format($this->return->final_refund, 2) . ' €',
            '{{portal_url}}' => route('customer.returns.search'),
            '{{return_url}}' => $this->generateReturnUrl(),
            '{{company_name}}' => config('app.name'),
            '{{support_email}}' => config('returns.support_email'),
            '{{support_phone}}' => config('returns.support_phone'),
        ];

        // Variables personalizadas adicionales
        if (!empty($this->customData['variables'])) {
            foreach ($this->customData['variables'] as $key => $value) {
                $variables['{{' . $key . '}}'] = $value;
            }
        }

        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    /**
     * Preparar botones de acción
     */
    private function prepareActionButtons(): array
    {
        $buttons = [];

        if (!empty($this->customData['action_buttons'])) {
            foreach ($this->customData['action_buttons'] as $button) {
                $buttons[] = [
                    'text' => $button['text'] ?? 'Ver Devolución',
                    'url' => $button['url'] ?? $this->generateReturnUrl(),
                    'style' => $button['style'] ?? 'primary', // primary, secondary, danger
                    'icon' => $button['icon'] ?? null
                ];
            }
        } elseif ($this->customData['show_default_button'] ?? true) {
            $buttons[] = [
                'text' => 'Ver Estado de la Devolución',
                'url' => $this->generateReturnUrl(),
                'style' => 'primary'
            ];
        }

        return $buttons;
    }

    /**
     * Obtener dirección de envío
     */
    private function getFromAddress(): array
    {
        if (!empty($this->customData['from'])) {
            return [
                'address' => $this->customData['from']['address'],
                'name' => $this->customData['from']['name'] ?? config('app.name')
            ];
        }

        return [
            'address' => config('returns.notifications.from.address', 'noreply@ejemplo.com'),
            'name' => config('returns.notifications.from.name', config('app.name'))
        ];
    }

    /**
     * Obtener direcciones de respuesta
     */
    private function getReplyTo(): array
    {
        if (!empty($this->customData['reply_to'])) {
            return is_array($this->customData['reply_to'])
                ? $this->customData['reply_to']
                : [$this->customData['reply_to']];
        }

        return [config('returns.support_email', 'soporte@ejemplo.com')];
    }

    /**
     * Obtener destinatarios CC
     */
    private function getCcRecipients(): array
    {
        if (empty($this->customData['cc'])) {
            return [];
        }

        return is_array($this->customData['cc'])
            ? $this->customData['cc']
            : [$this->customData['cc']];
    }

    /**
     * Obtener destinatarios BCC
     */
    private function getBccRecipients(): array
    {
        $bcc = [];

        if (!empty($this->customData['bcc'])) {
            $bcc = is_array($this->customData['bcc'])
                ? $this->customData['bcc']
                : [$this->customData['bcc']];
        }

        // Agregar copia oculta al administrador si está configurado
        if ($this->customData['copy_to_admin'] ?? false) {
            $bcc[] = config('returns.notifications.admin_email');
        }

        return $bcc;
    }

    /**
     * Obtener documentos de la devolución
     */
    private function getReturnDocuments(): array
    {
        $documents = [];

        if ($this->return->label_path) {
            $documents[] = Attachment::fromPath($this->return->label_path)
                ->as("etiqueta_devolucion_{$this->return->number}.pdf")
                ->withMime('application/pdf');
        }

        // Agregar otros documentos según el estado
        if ($this->return->status === 'completed' && $this->return->receipt_path) {
            $documents[] = Attachment::fromPath($this->return->receipt_path)
                ->as("recibo_devolucion_{$this->return->number}.pdf")
                ->withMime('application/pdf');
        }

        return $documents;
    }

    /**
     * Generar URL de la devolución con token
     */
    private function generateReturnUrl(): string
    {
        $token = \Str::random(32);

        cache()->put(
            "return_access_{$this->return->id}_{$token}",
            true,
            now()->addHours(72) // 72 horas para emails personalizados
        );

        return route('customer.returns.show', [
            'return' => $this->return->id,
            'token' => $token
        ]);
    }

    /**
     * Generar pixel de tracking
     */
    private function generateTrackingPixel(): string
    {
        $trackingId = $this->communication->metadata['tracking_id'] ?? null;

        if (!$trackingId || !($this->customData['enable_tracking'] ?? true)) {
            return '';
        }

        return route('email.track', ['id' => $trackingId]);
    }

    /**
     * Obtener firma por defecto
     */
    private function getDefaultSignature(): string
    {
        return "Atentamente,<br>" .
            "El equipo de " . config('app.name') . "<br>" .
            config('returns.support_email') . " | " . config('returns.support_phone');
    }

    /**
     * Get the message headers.
     */
    public function headers(): array
    {
        $headers = [
            'X-Return-ID' => $this->return->id,
            'X-Communication-ID' => $this->communication->id,
            'X-Custom-Mail' => 'true',
        ];

        // Headers personalizados adicionales
        if (!empty($this->customData['headers'])) {
            foreach ($this->customData['headers'] as $key => $value) {
                $headers[$key] = $value;
            }
        }

        // Prioridad del mensaje
        if (isset($this->customData['priority'])) {
            $headers['X-Priority'] = $this->customData['priority']; // 1 (alta) a 5 (baja)
        }

        return $headers;
    }

}
