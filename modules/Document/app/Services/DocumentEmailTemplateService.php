<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentLang;
use Modules\Document\Entities\DocumentMail;
use Modules\Document\Mail\DocumentCustomMail;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Mailer\Services\MailerVariableValueService;

class DocumentEmailTemplateService
{
    public static function sendInitialRequest(Document $document, ?int $adminId = null): bool
    {
        try {

            $template = self::resolveTemplate('documents.mail_template_initial_request_id', 'document_initial_request');

            if (! $template) {
                return false;
            }

            $recipient = $document->customer_email;
            if (! $recipient) {
                return false;
            }

            // Get lang_id from document (defaults to 1 if not set)
            $langId = $document->lang_id ?? 1;

            // Obtener los documentos requeridos para este tipo de documento con traducción
            $requiredDocs = $document->getRequiredDocumentsWithLabels();
            $variables = self::prepareDocumentVariables($document, $requiredDocs);

            // Get translation for the template using document's language
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new DocumentCustomMail($document, $subject, $content));

            // Log the email
            self::logEmail($document, 'request', $subject, $content, $template, [], true, null, $adminId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending initial request email', [
                'document_uid' => $document->uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function sendReminder(Document $document, ?int $adminId = null): bool
    {
        try {

            $template = self::resolveTemplate('documents.mail_template_reminder_id', 'document_reminder');

            if (! $template) {
                return false;
            }

            $recipient = $document->customer_email;
            if (! $recipient) {
                return false;
            }

            // Get lang_id from document (defaults to 1 if not set)
            $langId = $document->lang_id ?? 1;

            // Obtener los documentos requeridos para este tipo de documento con traducción
            $requiredDocs = $document->getRequiredDocumentsWithLabels();

            $variables = self::prepareDocumentVariables($document, $requiredDocs);

            // Calcular días desde la solicitud inicial
            $daysSinceRequest = $document->created_at
                ? now()->diffInDays($document->created_at)
                : 0;

            $variables['DAYS_SINCE_REQUEST'] = $daysSinceRequest;

            $variables['REMINDER_MESSAGE'] = sprintf(
                'Han pasado <strong>%d día%s</strong> desde que solicitamos su documentación y aún no hemos recibido respuesta. Le recordamos que es importante que nos envíe los documentos lo antes posible para poder continuar con el procesamiento de su pedido.',
                $daysSinceRequest,
                $daysSinceRequest === 1 ? '' : 's'
            );

            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new DocumentCustomMail($document, $subject, $content));

            self::logEmail($document, 'reminder', $subject, $content, $template, [
                'days_since_request' => $daysSinceRequest,
            ], true, null, $adminId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending reminder email', [
                'document_uid' => $document->uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function sendMissingDocuments(Document $document, array $missingDocs = [], ?string $notes = null, ?int $adminId = null): bool
    {
        try {

            $template = self::resolveTemplate('documents.mail_template_missing_docs_id', 'document_missing_documents', ['document_missing']);

            if (! $template) {
                return false;
            }

            $recipient = $document->customer_email;
            if (! $recipient) {
                return false;
            }

            $variables = self::prepareDocumentVariables($document, $missingDocs, $notes);

            // Get lang_id from document (defaults to 1 if not set)
            $langId = $document->lang_id ?? 1;

            // Get translation for the template using document's language
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new DocumentCustomMail($document, $subject, $content));

            // Log the email
            self::logEmail($document, 'missing', $subject, $content, $template, [
                'missing_docs' => $missingDocs,
                'notes' => $notes,
            ], true, null, $adminId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending missing documents email', [
                'document_uid' => $document->uid,
                'recipient' => $recipient ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public static function sendCustomEmail(Document $document, string $subject, string $content, ?int $adminId = null): bool
    {
        try {

            $recipient = $document->customer_email;

            if (! $recipient) {
                Log::error('Custom email: No recipient found', ['document_uid' => $document->uid]);

                return false;
            }

            $variables = self::prepareDocumentVariables($document);
            $langId = $document->lang_id ?? 1;

            // Procesar contenido del usuario (reemplazar variables que el usuario haya puesto)
            $userContent = MailerTemplateRendererService::replaceVariables($content, $variables);

            // Agregar el contenido del usuario como variable especial
            $variables['custom_content'] = $userContent;
            $variables['CUSTOM_CONTENT'] = $userContent;

            // Procesar asunto (reemplazar variables del usuario)
            $processedSubject = MailerTemplateRendererService::replaceVariables($subject, $variables);

            // Obtener plantilla configurada para correo personalizado
            $template = self::resolveTemplate('documents.mail_template_custom_email_id', 'document_custom_email');

            if (! $template) {
                Log::warning('No custom email template configured, sending plain content');
                $finalContent = $userContent;
            } else {
                // Obtener traducción de la plantilla
                $translation = $template->translate($langId);
                if (! $translation || ! $translation->subject) {
                    Log::error('Custom email template has no translation', [
                        'template_id' => $template->id,
                        'lang_id' => $langId,
                    ]);

                    return false;
                }

                // Usar renderEmailTemplate para aplicar layouts y reemplazar todas las variables
                // Esto es igual que los demás métodos (sendInitialRequest, sendReminder, etc.)
                $finalContent = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);
            }

            Mail::to($recipient)
                ->queue(new DocumentCustomMail($document, $processedSubject, $finalContent));

            // Log the email
            self::logEmail($document, 'custom', $processedSubject, $finalContent, $template ?? null, [
                'original_subject' => $subject,
                'original_content' => $content,
            ], true, null, $adminId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending custom email', [
                'document_uid' => $document->uid,
                'recipient' => $recipient ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Enviar email de confirmación de documentos cargados
     */
    public static function sendUploadConfirmation(Document $document, ?int $adminId = null): bool
    {
        try {
            $template = self::resolveTemplate('documents.mail_template_upload_confirmation_id', 'document_completed', ['document_confirmation']);

            if (! $template) {
                return false;
            }

            $recipient = $document->customer_email;
            if (! $recipient) {
                return false;
            }

            // Get lang_id from document (defaults to 1 if not set)
            $langId = $document->lang_id ?? 1;

            // Obtener los documentos requeridos con traducciones
            $uploadedDocs = $document->getRequiredDocumentsWithLabels();
            $variables = self::prepareDocumentVariables($document, $uploadedDocs);

            // Get translation for the template using document's language
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new DocumentCustomMail($document, $subject, $content));

            // Log the email
            self::logEmail($document, 'upload', $subject, $content, $template, [], true, null, $adminId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending upload confirmation email', [
                'document_uid' => $document->uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enviar email de aprobación
     */
    public static function sendApprovalEmail(Document $document, ?int $adminId = null): bool
    {
        try {
            $template = self::resolveTemplate('documents.mail_template_approval_id', 'document_approval', ['approval_notification']);

            if (! $template) {
                return false;
            }

            $recipient = $document->customer_email;
            if (! $recipient) {
                return false;
            }

            $variables = self::prepareDocumentVariables($document);

            // Get lang_id from document (defaults to 1 if not set)
            $langId = $document->lang_id ?? 1;

            // Get translation for the template using document's language
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new DocumentCustomMail($document, $subject, $content));

            // Log the email
            self::logEmail($document, 'approval', $subject, $content, $template, [], true, null, $adminId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending approval email', [
                'document_uid' => $document->uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enviar email de rechazo con razón personalizada
     */
    public static function sendRejectionEmail(Document $document, ?string $reason = null, array $rejectedDocs = [], ?int $adminId = null): bool
    {
        try {
            $template = self::resolveTemplate('documents.mail_template_rejection_id', 'document_rejection', ['rejection_notification']);

            if (! $template) {
                return false;
            }

            $recipient = $document->customer_email;
            if (! $recipient) {
                return false;
            }

            // Si se proporcionaron documentos rechazados específicos, usarlos
            // Si no, usar todos los documentos requeridos
            if (empty($rejectedDocs)) {
                $documentTypeSlug = $document->documentType?->slug ?? 'general';
                $rejectedDocs = \Modules\Document\Services\DocumentTypeService::getRequiredDocuments($documentTypeSlug);
            }

            $variables = self::prepareDocumentVariables($document, $rejectedDocs, $reason);

            // Agregar variable específica para rechazo
            $variables['REJECTION_REASON'] = $reason ?? '';

            // Get lang_id from document (defaults to 1 if not set)
            $langId = $document->lang_id ?? 1;

            // Get translation for the template using document's language
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new DocumentCustomMail($document, $subject, $content));

            // Log the email
            self::logEmail($document, 'rejection', $subject, $content, $template, [
                'reason' => $reason,
                'rejected_docs' => $rejectedDocs,
            ], true, null, $adminId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending rejection email', [
                'document_uid' => $document->uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Construir URL de carga de documentos con prefijo de idioma
     * - Español (es): sin prefijo -> /solicitud-documentos
     * - Otros idiomas: con prefijo -> /pt/solicitud-documentos, /fr/solicitud-documentos
     */
    private static function buildUploadUrl(Document $document): ?string
    {
        $uploadPortalTemplate = Setting::get('documents.upload_portal_url');

        if (! $uploadPortalTemplate) {
            return null;
        }

        // Obtener el código de idioma (iso_code)
        $locale = $document->lang->iso_code ?? 'es';

        // Parsear la URL template para separar dominio y ruta
        $urlParts = parse_url(rtrim($uploadPortalTemplate));

        if (! $urlParts || ! isset($urlParts['scheme']) || ! isset($urlParts['host'])) {
            // Si no se puede parsear, usar el método antiguo como fallback
            return str_replace('{uid}', $document->uid, rtrim($uploadPortalTemplate));
        }

        // Construir URL base con esquema y host
        $baseUrl = $urlParts['scheme'].'://'.$urlParts['host'];

        // Agregar puerto si existe
        if (isset($urlParts['port'])) {
            $baseUrl .= ':'.$urlParts['port'];
        }

        // Obtener la ruta (path)
        $path = $urlParts['path'] ?? '/solicitud-documentos';

        // Si el idioma NO es español, agregar prefijo de idioma
        if ($locale !== 'es') {
            // Insertar el código de idioma después del dominio
            $path = '/'.$locale.$path;
        }

        // Construir la URL completa
        $fullUrl = $baseUrl.$path;

        // Agregar query string si existe en el template
        if (isset($urlParts['query'])) {
            $fullUrl .= '?'.$urlParts['query'];
        }

        // Reemplazar el placeholder {uid} con el UID real del documento
        return str_replace('{uid}', $document->uid, $fullUrl);
    }

    /**
     * Preparar variables para el documento
     */
    private static function prepareDocumentVariables(
        Document $document,
        array $missingDocs = [],
        ?string $notes = null
    ): array {
        // Obtener el código de idioma del documento (usa lang_id para evitar cargar la relación)
        $locale = $document->lang->iso_code ?? 'es';
        // Preparar nombre del cliente con fallback traducido
        $customerName = trim(sprintf(
            '%s %s',
            $document->customer_firstname ?? '',
            $document->customer_lastname ?? ''
        ));

        if (empty($customerName)) {
            $customerName = __('documents.labels.customer', [], $locale);
        }

        // Calcular fecha de vencimiento
        $uploadDeadline = $document->created_at
            ? $document->created_at->addDays(3)->format('d/m/Y')
            : null;

        // Generar URL de carga con prefijo de idioma
        $uploadUrl = self::buildUploadUrl($document);

        // Traducir el tipo de documento
        $documentType = $document->documentType?->slug ?? 'general';
        $documentTypeLabel = self::translateDocumentType($documentType, $locale);
        $documentInstructions = __("documents.types.{$documentType}.instructions", [], $locale);

        // Obtener lang_id del documento (defaults to 1 if not set)
        $langId = $document->lang_id ?? 1;

        // Variables base del sistema (siempre disponibles)
        $variables = self::getSystemVariables($locale, $langId);

        // Variables específicas del documento
        $variables = array_merge($variables, [
            // Información del cliente
            'CUSTOMER_NAME' => $customerName,
            'CUSTOMER_FIRSTNAME' => $document->customer_firstname ?? '',
            'CUSTOMER_LASTNAME' => $document->customer_lastname ?? '',
            'CUSTOMER_EMAIL' => $document->customer_email ?? '',

            // Información del pedido
            'ORDER_ID' => $document->order_id ?? '',
            'ORDER_REFERENCE' => $document->order_reference ?? '',

            // Información del documento
            'DOCUMENT_TYPE' => $documentType,
            'DOCUMENT_TYPE_LABEL' => $documentTypeLabel,
            'DOCUMENT_INSTRUCTIONS' => $documentInstructions,
            'DOCUMENT_UID' => $document->uid ?? '',

            // Enlaces y fechas
            'UPLOAD_LINK' => $uploadUrl ?? '',
            'UPLOAD_URL' => $uploadUrl ?? '',
            'EXPIRATION_DATE' => $uploadDeadline ?? '',
            'DEADLINE' => $uploadDeadline ?? '',
        ]);

        // Agregar variables de documentos faltantes si aplica
        if (! empty($missingDocs)) {
            $formattedDocs = self::formatMissingDocuments($missingDocs, $locale);
            $variables['MISSING_DOCUMENTS'] = $formattedDocs;
            $variables['MISSING_DOCUMENTS_LIST'] = $formattedDocs;
            $variables['REQUIRED_DOCUMENTS_LIST'] = $formattedDocs;
            $variables['REQUEST_REASON'] = $notes ?? '';
            $variables['NOTES'] = $notes ?? '';

            // Pasar NOTES_CONTENT (escapado para HTML) para uso en plantillas Twig
            $variables['NOTES_CONTENT'] = ! empty($notes) ? htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') : '';
        } else {
            // Si no hay documentos faltantes, inicializar variables de notas como vacías
            $variables['REQUEST_REASON'] = '';
            $variables['NOTES'] = '';
            $variables['NOTES_CONTENT'] = '';
        }

        return $variables;
    }

    /**
     * Obtener variables del sistema (siempre disponibles)
     * Retorna TODAS las variables de mailer_variables + mailer_variable_langs
     * más las variables dinámicas calculadas en tiempo de ejecución
     */
    private static function getSystemVariables(string $locale = 'es', int $langId = 1): array
    {
        // Obtener TODAS las variables traducidas desde la base de datos
        $realValues = MailerVariableValueService::getTranslatedValues($langId);

        // Variables dinámicas calculadas en tiempo de ejecución (sobrescriben las de BD si existen)
        $dynamicVariables = [
            // Fechas del sistema (siempre calculadas en tiempo real)
            'CURRENT_YEAR' => date('Y'),
            'CURRENT_DATE' => date('d/m/Y'),
            'CURRENT_DATETIME' => date('d/m/Y H:i'),

            // Idioma actual
            'LANG_CODE' => $locale,
            'LANGUAGE' => $locale,

            // Subject (se rellenará desde la plantilla)
            'EMAIL_SUBJECT' => '',
        ];

        // Combinar: primero las de BD, luego las dinámicas (las dinámicas tienen prioridad)
        return array_merge($realValues, $dynamicVariables);
    }

    /**
     * Traducir tipo de documento con fallback
     */
    private static function translateDocumentType(string $documentType, string $locale = 'es'): string
    {
        $translationKey = "documents.types.{$documentType}.label";
        $translated = __($translationKey, [], $locale);

        // Si no existe traducción, usar el código con formato legible
        if ($translated === $translationKey) {
            return ucfirst(str_replace('_', ' ', $documentType));
        }

        return $translated;
    }

    /**
     * Formatear lista de documentos faltantes con traducciones
     */
    private static function formatMissingDocuments(array $missingDocs, string $locale = 'es'): string
    {
        if (empty($missingDocs)) {
            return '';
        }

        $html = '<ul style="margin: 10px 0; padding-left: 20px;">';

        foreach ($missingDocs as $docCode => $docLabel) {
            // Si $docLabel es una cadena (ya traducida desde DocumentType), usarla directamente
            // Si es un array (estructura antigua), usar el valor 'name'
            if (is_string($docLabel)) {
                $docName = $docLabel;
            } elseif (is_array($docLabel) && isset($docLabel['name'])) {
                $docName = $docLabel['name'];
            } else {
                // Fallback: intentar traducir desde translations
                $translationKey = "documents.requirements.{$docCode}.name";
                $docName = __($translationKey, [], $locale);

                // Si no existe traducción, usar el código como fallback con formato legible
                if ($docName === $translationKey) {
                    $docName = ucwords(str_replace('_', ' ', $docCode));
                }
            }

            $html .= '<li style="margin: 5px 0; color: #333; font-size: 15px;">'.$docName.'</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Resolver plantilla desde configuración o usar fallback por clave
     * Primero intenta obtener ID de plantilla desde Settings, luego busca por clave
     *
     * @param  string  $settingKey  Clave de configuración (ej: documents.email_template_reminder_id)
     * @param  string  $fallbackKey  Clave Por defecto si no hay configuración (ej: document_reminder)
     * @param  array<string>  $alternativeKeys  Claves alternativas si la principal no existe
     */
    private static function resolveTemplate(string $settingKey, string $fallbackKey, array $alternativeKeys = []): ?MailerTemplate
    {
        // Intentar obtener ID de plantilla configurado
        $configuredTemplateId = Setting::get($settingKey);

        if ($configuredTemplateId) {
            // Buscar por ID configurado
            $template = MailerTemplate::find($configuredTemplateId);
            if ($template && $template->is_enabled) {
                return $template;
            }
        }

        // Fallback: buscar por clave principal
        $template = MailerTemplate::where('key', $fallbackKey)
            ->where('is_enabled', true)
            ->first();

        if ($template) {
            return $template;
        }

        // Si no encontró, intentar con claves alternativas
        foreach ($alternativeKeys as $key) {
            $template = MailerTemplate::where('key', $key)
                ->where('is_enabled', true)
                ->first();

            if ($template) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Log email to document_mails table
     */
    private static function logEmail(
        Document $document,
        string $emailType,
        string $subject,
        string $content,
        ?MailerTemplate $template = null,
        array $metadata = [],
        bool $success = true,
        ?string $errorMessage = null,
        ?int $adminId = null
    ): ?DocumentMail {
        try {
            $mail = DocumentMail::logEmail(
                $document,
                $emailType,
                $subject,
                $content,
                null,
                $template?->id,
                $adminId,
                $metadata
            );

            if ($success) {
                $mail->markAsSent();
            } else {
                $mail->markAsFailed($errorMessage ?? 'Unknown error');
            }

            return $mail;
        } catch (\Exception $e) {
            Log::error('Failed to log document email', [
                'document_uid' => $document->uid,
                'email_type' => $emailType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtiene el código de idioma dinámicamente desde la tabla langs
     * Usa caché estático para mejorar rendimiento
     */
    private static function getLanguageCode(int $langId): string
    {
        static $langCache = [];

        // Si ya está en caché, retornarlo
        if (isset($langCache[$langId])) {
            return $langCache[$langId];
        }

        // Buscar dinámicamente en la tabla langs
        $lang = DocumentLang::find($langId);

        if ($lang && ! empty($lang->iso_code)) {
            $langCache[$langId] = $lang->iso_code;

            return $lang->iso_code;
        }

        // Fallback a español si no se encuentra
        $langCache[$langId] = 'es';

        return 'es';
    }
}
