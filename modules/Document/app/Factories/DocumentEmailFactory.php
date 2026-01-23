<?php

namespace Modules\Document\Factories;

use Illuminate\Support\Facades\Mail;
use Modules\Document\Entities\Document;
use Modules\Document\Mail\DocumentCustomMail;
use Modules\Mailer\Models\MailerTemplate;

/**
 * @deprecated This class is deprecated and will be removed in a future version.
 * Use DocumentEmailService instead, which provides:
 * - Email validation before queueing
 * - Rate limiting (60s per email type)
 * - AdminId tracking
 * - Consolidated logging with DocumentActionService
 *
 * Migration path:
 * - Replace DocumentEmailFactory::sendByTemplateKey() with DocumentEmailService::send*() methods
 * - Replace DocumentEmailFactory::sendCustom() with DocumentEmailService::sendCustomEmail()
 */
class DocumentEmailFactory
{
    public static function sendByTemplateKey(
        Document $document,
        string $templateKey,
        array $additionalVariables = []
    ): bool {
        $template = MailerTemplate::where('key', $templateKey)
            ->where('is_enabled', true)
            ->where('module', 'documents')
            ->first();

        if (! $template) {
            \Log::warning("DocumentEmailFactory: Template '$templateKey' no encontrado");

            return false;
        }

        return self::sendByTemplate($document, $template, $additionalVariables);
    }

    public static function sendByTemplate(
        Document $document,
        MailerTemplate $template,
        array $additionalVariables = []
    ): bool {
        try {
            $recipient = $document->customer_email;

            if (! $recipient) {
                \Log::warning("DocumentEmailFactory: No hay email de destinatario para documento {$document->uid}");

                return false;
            }

            // Crear Mailable
            $mail = new DocumentCustomMail($document, null, null, $template);

            // Agregar variables adicionales
            if (! empty($additionalVariables)) {
                $mail->setVariables($additionalVariables);
            }

            // Enviar (cambiar a queue para no bloquear)
            Mail::to($recipient)->queue($mail);

            \Log::info("DocumentEmailFactory: Email enviado exitosamente. Template: {$template->key}, Recipient: {$recipient}");

            return true;
        } catch (\Exception $e) {
            \Log::error("DocumentEmailFactory: Error al enviar email. {$e->getMessage()}");

            return false;
        }
    }

    public static function sendCustom(Document $document, string $subject, string $content): bool
    {
        try {
            $recipient = $document->customer_email;

            if (! $recipient) {
                \Log::warning("DocumentEmailFactory: No hay email de destinatario para documento {$document->uid}");

                return false;
            }

            Mail::to($recipient)->queue(new DocumentCustomMail($document, $subject, $content));

            return true;
        } catch (\Exception $e) {
            \Log::error("DocumentEmailFactory: Error al enviar email personalizado. {$e->getMessage()}");

            return false;
        }
    }

    public static function sendUploadedNotification(Document $document): bool
    {
        return self::sendByTemplateKey($document, 'document_uploaded');
    }

    public static function sendReminder(Document $document): bool
    {
        return self::sendByTemplateKey($document, 'document_reminder');
    }

    public static function sendMissingNotification(Document $document, string $missingDocuments = ''): bool
    {
        return self::sendByTemplateKey($document, 'document_missing', [
            'MISSING_DOCUMENTS' => $missingDocuments ?: 'Documentos no especificados',
        ]);
    }

    public static function sendApprovedNotification(Document $document): bool
    {
        return self::sendByTemplateKey($document, 'document_approved');
    }

    public static function sendTestEmail(MailTemplate $template, string $testEmail): bool
    {
        try {
            $mail = new DocumentCustomMail(
                new Document([
                    'customer_firstname' => 'Juan',
                    'customer_lastname' => 'Pérez',
                    'customer_email' => $testEmail,
                    'order_id' => '12345',
                    'document_type' => 'Cédula',
                ]),
                null,
                null,
                $template
            );

            Mail::to($testEmail)->queue($mail);

            return true;
        } catch (\Exception $e) {
            \Log::error("DocumentEmailFactory: Error al enviar email de prueba. {$e->getMessage()}");

            return false;
        }
    }

    public static function getTemplate(string $templateKey): ?MailerTemplate
    {
        return MailerTemplate::where('key', $templateKey)
            ->where('is_enabled', true)
            ->where('module', 'documents')
            ->first();
    }

    public static function getAvailableTemplates()
    {
        return MailerTemplate::where('is_enabled', true)
            ->where('module', 'documents')
            ->orderBy('name')
            ->get();
    }

    public static function getTemplatesWithStats(): array
    {
        return self::getAvailableTemplates()
            ->map(function (MailerTemplate $template) {
                return [
                    'key' => $template->key,
                    'name' => $template->name,
                    'subject' => $template->subject,
                    'module' => $template->module,
                    'is_complete' => $template->isComplete(),
                ];
            })
            ->toArray();
    }
}
