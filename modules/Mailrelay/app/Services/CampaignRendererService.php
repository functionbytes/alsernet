<?php

namespace Modules\Mailrelay\Services;

use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Mailer\Services\MailerVariableReplacementService;
use Modules\Mailrelay\Contracts\CampaignRendererInterface;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Exceptions\CampaignRenderException;

/**
 * Servicio para renderizar campaigns usando templates de Mailer
 * Integra el sistema de templates de Mailer con las campaigns de Mailrelay
 */
class CampaignRendererService implements CampaignRendererInterface
{
    /**
     * Renderizar campaign usando MailerTemplate
     *
     * @param  Campaign  $campaign  Campaña a renderizar
     * @param  array  $variables  Variables adicionales para el template
     * @return string HTML renderizado
     */
    public function render(Campaign $campaign, array $variables = []): string
    {
        // Si la campaign tiene html_content directamente (sin template), usarlo
        if ($campaign->html_content && ! $campaign->mailer_template_id) {
            return $this->replaceVariables($campaign->html_content, $variables);
        }

        // Si usa MailerTemplate, renderizarlo
        if ($campaign->mailer_template_id) {
            return $this->renderWithMailerTemplate($campaign, $variables);
        }

        throw CampaignRenderException::noHtmlContent($campaign->id);
    }

    /**
     * Renderizar usando MailerTemplate del módulo Mailer
     */
    private function renderWithMailerTemplate(Campaign $campaign, array $variables = []): string
    {
        $template = MailerTemplate::find($campaign->mailer_template_id);

        if (! $template) {
            throw CampaignRenderException::templateNotFound($campaign->mailer_template_id);
        }

        // Obtener idioma (default a 1 si no está especificado)
        $langId = $campaign->lang_id ?? 1;

        // Merge de variables del template + variables custom de la campaign + variables extra
        $allVariables = array_merge(
            $this->getCampaignVariables($campaign),
            $campaign->template_variables ?? [],
            $variables
        );

        // Usar el MailerTemplateRendererService para renderizar
        $html = MailerTemplateRendererService::renderEmailTemplate(
            $template,
            $allVariables,
            $langId
        );

        return $html;
    }

    /**
     * Renderizar preview de la campaña
     *
     * @param  string  $subscriberEmail  Email de ejemplo para preview
     * @return string HTML renderizado con variables de ejemplo
     */
    public function renderPreview(Campaign $campaign, string $subscriberEmail = 'preview@example.com'): string
    {
        // Variables de ejemplo para preview
        $previewVariables = $this->getPreviewVariables($campaign, $subscriberEmail);

        return $this->render($campaign, $previewVariables);
    }

    /**
     * Obtener variables disponibles para la campaña
     *
     * @return array Variables disponibles
     */
    public function getAvailableVariables(Campaign $campaign): array
    {
        $variables = [];

        // Variables base de campaigns
        $variables = array_merge($variables, $this->getCampaignVariables($campaign));

        // Si hay template de Mailer, obtener sus variables
        if ($campaign->mailer_template_id) {
            $template = MailerTemplate::find($campaign->mailer_template_id);
            if ($template) {
                $templateVars = $template->getAvailableVariables();
                foreach ($templateVars as $var) {
                    $variables[$var['name']] = $var;
                }
            }
        }

        return $variables;
    }

    /**
     * Obtener variables específicas de la campaign
     */
    private function getCampaignVariables(Campaign $campaign): array
    {
        return [
            'CAMPAIGN_NAME' => $campaign->name ?? '',
            'CAMPAIGN_SUBJECT' => $campaign->subject ?? '',
            'CAMPAIGN_UID' => $campaign->id ?? '',
            'SENDER_EMAIL' => $campaign->mailProvider->credentials['from_email'] ?? config('mail.from.address'),
            'SENDER_NAME' => $campaign->mailProvider->credentials['from_name'] ?? config('mail.from.name'),
            'CURRENT_YEAR' => date('Y'),
            'CURRENT_DATE' => date('d/m/Y'),
            'CURRENT_DATETIME' => date('d/m/Y H:i'),
        ];
    }

    /**
     * Obtener variables de ejemplo para preview
     */
    private function getPreviewVariables(Campaign $campaign, string $subscriberEmail): array
    {
        $variables = $this->getCampaignVariables($campaign);

        // Agregar variables de suscriptor de ejemplo
        $variables = array_merge($variables, [
            'SUBSCRIBER_EMAIL' => $subscriberEmail,
            'SUBSCRIBER_UID' => 'preview-uid-123',
            'SUBSCRIBER_FIRSTNAME' => 'Juan',
            'SUBSCRIBER_LASTNAME' => 'García',
            'UNSUBSCRIBE_URL' => url('/unsubscribe/preview-uid-123'),
            'WEB_VIEW_URL' => url('/campaigns/'.$campaign->id.'/view'),
            'UPDATE_PROFILE_URL' => url('/profile/preview-uid-123'),
        ]);

        // Si el campaign tiene template, usar sus variables de preview
        if ($campaign->mailer_template_id) {
            $template = MailerTemplate::find($campaign->mailer_template_id);
            if ($template) {
                $previewVars = MailerVariableReplacementService::getPreviewVariablesForTemplate(
                    $template,
                    $campaign->lang_id ?? 1
                );
                $variables = array_merge($variables, $previewVars);
            }
        }

        return $variables;
    }

    /**
     * Reemplazar variables en HTML
     */
    private function replaceVariables(string $html, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = str_starts_with($key, '{') ? $key : '{'.$key.'}';

            if (! is_array($value) && ! is_object($value)) {
                $html = str_replace($placeholder, (string) $value, $html);
            }
        }

        return $html;
    }

    /**
     * Personalizar HTML para un suscriptor específico
     *
     * @param  array  $subscriber  ['email' => '', 'name' => '', 'variables' => []]
     * @return string HTML personalizado
     */
    public function renderForSubscriber(Campaign $campaign, array $subscriber): string
    {
        // Variables base del suscriptor
        $subscriberVars = [
            'SUBSCRIBER_EMAIL' => $subscriber['email'] ?? '',
            'SUBSCRIBER_FIRSTNAME' => $subscriber['first_name'] ?? $subscriber['name'] ?? '',
            'SUBSCRIBER_LASTNAME' => $subscriber['last_name'] ?? '',
            'SUBSCRIBER_UID' => $subscriber['uid'] ?? $subscriber['id'] ?? '',
        ];

        // Merge con variables custom del suscriptor
        if (! empty($subscriber['variables'])) {
            $subscriberVars = array_merge($subscriberVars, $subscriber['variables']);
        }

        // URLs dinámicas
        $subscriberVars['UNSUBSCRIBE_URL'] = url('/unsubscribe/'.$subscriberVars['SUBSCRIBER_UID']);
        $subscriberVars['WEB_VIEW_URL'] = url('/campaigns/'.$campaign->id.'/view/'.$subscriberVars['SUBSCRIBER_UID']);
        $subscriberVars['UPDATE_PROFILE_URL'] = url('/profile/'.$subscriberVars['SUBSCRIBER_UID']);

        return $this->render($campaign, $subscriberVars);
    }

    /**
     * Validar que el campaign está listo para enviarse
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(Campaign $campaign): array
    {
        $errors = [];

        // Validar que tiene subject
        if (empty($campaign->subject)) {
            $errors[] = 'El campaign debe tener un asunto';
        }

        // Validar que tiene contenido o template
        if (empty($campaign->html_content) && empty($campaign->mailer_template_id)) {
            $errors[] = 'El campaign debe tener contenido HTML o un template asignado';
        }

        // Validar que el template existe
        if ($campaign->mailer_template_id) {
            $template = MailerTemplate::find($campaign->mailer_template_id);
            if (! $template) {
                $errors[] = "Template ID {$campaign->mailer_template_id} no encontrado";
            } elseif (! $template->is_enabled) {
                $errors[] = "Template '{$template->name}' está deshabilitado";
            }
        }

        // Validar que tiene provider
        if (empty($campaign->mail_provider_id)) {
            $errors[] = 'El campaign debe tener un provider de email asignado';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
