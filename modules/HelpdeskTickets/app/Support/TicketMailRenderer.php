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

        $subject = MailerTemplateRendererService::replaceVariables(
            $template->subject ?: $fallbackSubject,
            $variables
        );
        $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables);

        return [$subject, $content];
    }
}
