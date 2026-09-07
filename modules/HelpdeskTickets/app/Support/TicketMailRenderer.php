<?php

namespace Modules\HelpdeskTickets\Support;

use Illuminate\Support\Facades\Log;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

/**
 * Renderiza los emails de HelpdeskTickets desde su MailerTemplate (BD), de modo
 * que el diseño sea editable desde el admin del módulo Mailer en vez de vivir en
 * un blade propio. Reemplaza el antiguo patrón `Content(view: '...::emails.*')`.
 * Devuelve el asunto y el HTML ya renderizados; el Mailable solo los transporta.
 */
class TicketMailRenderer
{
    /**
     * @param  array<string, string|int|null>  $variables  Variables {TAG} de la plantilla.
     * @return array{0: string, 1: string} [asunto, htmlContent]
     */
    public static function render(string $key, array $variables, string $fallbackSubject): array
    {
        $template = MailerTemplate::query()
            ->where('key', $key)
            ->where('is_enabled', true)
            ->first();

        if (! $template) {
            Log::warning('TicketMailRenderer: plantilla no encontrada, usando fallback', ['key' => $key]);

            return [$fallbackSubject, '<p>'.e($fallbackSubject).'</p>'];
        }

        // COMPANY_NAME lo usa el pie de página estático de helpdesk_tickets_wrapper
        // ({{ header }}/{{ footer }} son los únicos tags que el propio wrapper
        // resuelve solo; este texto es contenido normal del layout y depende de
        // que el caller lo pase como cualquier otra variable). 8 de los 9 callers
        // de este método nunca lo pasaban -> el cliente veía el placeholder
        // literal "{COMPANY_NAME}" en el correo (detectado revisando un envío
        // real en Mailpit, 3-sep-2026). Se centraliza aquí en vez de tocar cada
        // caller; explícito gana si alguno ya lo pasa.
        $variables += ['COMPANY_NAME' => config('app.name', 'Soporte')];

        $subject = MailerTemplateRendererService::replaceVariables(
            $template->subject ?: $fallbackSubject,
            $variables
        );
        $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables);

        return [$subject, $content];
    }
}
