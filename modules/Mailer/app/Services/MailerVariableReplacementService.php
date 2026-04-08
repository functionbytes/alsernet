<?php

namespace Modules\Mailer\Services;

use Modules\Mailer\Models\MailerTemplate;

/**
 * Service para el reemplazo de variables en plantillas y componentes de email
 * Centraliza la lógica de obtención y sustitución de variables
 */
class MailerVariableReplacementService
{
    /**
     * Obtener variables de preview para una plantilla de email
     *
     * @param  MailerTemplate  $template  Template para obtener el módulo
     * @param  int  $langId  ID del idioma para obtener valores traducidos
     * @return array Variables de preview con valores de ejemplo
     */
    public static function getPreviewVariablesForTemplate(MailerTemplate $template, int $langId = 1): array
    {
        // Obtener valores reales traducidos desde la base de datos
        $realValues = MailerVariableValueService::getTranslatedValues($langId, $template->module);

        // Variables base disponibles en todos los templates
        $baseVariables = self::getBaseVariables($realValues);

        // Agregar variables específicas según el módulo
        if ($template->module === 'documents') {
            $baseVariables = array_merge($baseVariables, self::getDocumentModuleVariables());
        }

        return $baseVariables;
    }

    /**
     * Obtener variables base comunes a todos los templates
     */
    private static function getBaseVariables(array $realValues): array
    {
        return [
            // Sistema - usar valores reales de BD, con fallback a config
            'COMPANY_NAME' => $realValues['COMPANY_NAME'] ?? config('app.name', 'Alsernet'),
            'SITE_NAME' => $realValues['SITE_NAME'] ?? config('app.name', 'Alsernet'),
            'SITE_URL' => $realValues['SITE_URL'] ?? config('app.url', 'https://example.com'),
            'SUPPORT_EMAIL' => $realValues['SUPPORT_EMAIL'] ?? config('mail.support.address', 'soporte@example.com'),
            'SUPPORT_PHONE' => $realValues['SUPPORT_PHONE'] ?? '+34 900 000 000',
            'CONTACT_EMAIL' => $realValues['CONTACT_EMAIL'] ?? config('mail.from.address', 'info@example.com'),
            'CURRENT_YEAR' => date('Y'),
            'CURRENT_DATE' => date('d/m/Y'),
            'CURRENT_DATETIME' => date('d/m/Y H:i'),

            // Cliente (ejemplos)
            'CUSTOMER_NAME' => 'Juan García Pérez',
            'CUSTOMER_FIRSTNAME' => 'Juan',
            'CUSTOMER_LASTNAME' => 'García Pérez',
            'CUSTOMER_EMAIL' => 'juan.garcia@example.com',

            // Pedido (ejemplos)
            'ORDER_ID' => '12345',
            'ORDER_REFERENCE' => 'REF-2024-001',
            'ORDER_DATE' => date('d/m/Y'),

            // Documento (ejemplos)
            'DOCUMENT_TYPE' => 'DNI',
            'DOCUMENT_TYPE_LABEL' => 'Documento de Identidad',
            'DOCUMENT_UID' => 'DOC-ABC123',
            'UPLOAD_LINK' => config('app.url').'/upload/DOC-ABC123',
            'UPLOAD_URL' => config('app.url').'/upload/DOC-ABC123',
            'EXPIRATION_DATE' => date('d/m/Y', strtotime('+3 days')),
            'DEADLINE' => date('d/m/Y', strtotime('+3 days')),

            // Contenido personalizado (para plantillas custom)
            'custom_content' => '<p>Este es un contenido de ejemplo para el correo personalizado.</p>',
            'CUSTOM_CONTENT' => '<p>Este es un contenido de ejemplo para el correo personalizado.</p>',
        ];
    }

    /**
     * Obtener variables específicas del módulo documents
     */
    private static function getDocumentModuleVariables(): array
    {
        $documentList = '<ul style="margin:10px 0;padding-left:20px"><li style="margin:5px 0">DNI o Pasaporte</li><li style="margin:5px 0">Comprobante de domicilio</li><li style="margin:5px 0">Justificante de ingresos</li></ul>';
        $notesText = 'La foto del DNI esta borrosa. Por favor, asegurese de que todos los datos sean legibles.';

        return [
            'MISSING_DOCUMENTS' => $documentList,
            'MISSING_DOCUMENTS_LIST' => $documentList,
            'REQUIRED_DOCUMENTS_LIST' => $documentList,
            'DOCUMENT_INSTRUCTIONS' => 'Por favor, cargue los documentos solicitados en formato PDF o imagen.',
            'NOTES_SECTION' => '<div style="background-color:#f3f4f6;padding:15px;border-radius:6px;margin:20px 0;border-left:4px solid #ff0049"><p style="margin:0;font-weight:bold;color:#374151">Nota adicional:</p><p style="margin-top:10px;font-style:italic;color:#555">"'.$notesText.'"</p></div>',
            'NOTES' => $notesText,
            'REQUEST_REASON' => $notesText,
            'DAYS_SINCE_REQUEST' => '5',
            'REMINDER_MESSAGE' => 'Han pasado <strong>5 dias</strong> desde que solicitamos su documentacion y aun no hemos recibido respuesta.',
        ];
    }

    /**
     * Reemplazar variables en una cadena de contenido
     * Delega al servicio centralizado MailerTemplateRendererService
     */
    public static function replaceVariables(string $content, array $variables = []): string
    {
        return MailerTemplateRendererService::replaceVariables($content, $variables);
    }

    /**
     * Obtener variables no reemplazadas en un contenido
     *
     * @param  string  $content  Contenido con placeholders {VARIABLE_NAME}
     * @return array Array de variables no encontradas
     */
    public static function getUnreplacedVariables(string $content): array
    {
        $matches = [];
        preg_match_all('/\{([A-Z_]+)\}/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }
}
