<?php

namespace Modules\Mailrelay\Contracts;

use Modules\Mailrelay\Entities\Campaign;

/**
 * Interface para renderizar campaigns con templates de Mailer
 */
interface CampaignRendererInterface
{
    /**
     * Renderizar campaign usando MailerTemplate
     *
     * @param  Campaign  $campaign  Campaña a renderizar
     * @param  array  $variables  Variables adicionales para el template
     * @return string HTML renderizado
     */
    public function render(Campaign $campaign, array $variables = []): string;

    /**
     * Renderizar preview de la campaña
     *
     * @param  string  $subscriberEmail  Email de ejemplo para preview
     * @return string HTML renderizado con variables de ejemplo
     */
    public function renderPreview(Campaign $campaign, string $subscriberEmail = 'preview@example.com'): string;

    /**
     * Obtener variables disponibles para la campaña
     *
     * @return array Variables disponibles
     */
    public function getAvailableVariables(Campaign $campaign): array;
}
