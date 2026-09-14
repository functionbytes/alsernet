<?php

namespace Modules\Questions\Support;

use Illuminate\Support\Facades\Log;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

/**
 * Resuelve y renderiza los correos de consultas desde su MailerTemplate, para
 * que el texto se edite en el admin de Mailer y no en un blade del módulo.
 * Devuelve asunto y HTML ya montados; el Mailable solo los transporta.
 */
class QuestionMailRenderer
{
    /**
     * @param  array<string, string|int|null>  $variables  Variables {TAG} de la plantilla.
     * @return array{0: string, 1: string}|null [asunto, html], o null si la plantilla no existe
     *                                          o está desactivada — el correo entonces no se manda.
     */
    public static function render(string $key, array $variables): ?array
    {
        $template = MailerTemplate::query()
            ->where('key', $key)
            ->where('is_enabled', true)
            ->first();

        if (! $template) {
            Log::warning('Questions: plantilla de correo no encontrada o desactivada.', ['key' => $key]);

            return null;
        }

        return [
            MailerTemplateRendererService::replaceVariables((string) $template->subject, $variables),
            MailerTemplateRendererService::renderEmailTemplate($template, $variables),
        ];
    }
}
