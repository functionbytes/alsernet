<?php

namespace Modules\Mailrelay\Contracts;

/**
 * Interface para providers de email marketing
 * Todos los providers (Mailrelay, Mailtrap, SendGrid, etc.) deben implementar esta interface
 */
interface MailProviderInterface
{
    /**
     * Enviar email individual
     *
     * @param  string  $to  Email destinatario
     * @param  string  $subject  Asunto del email
     * @param  string  $htmlContent  Contenido HTML renderizado
     * @param  array  $options  Opciones adicionales (reply_to, from_name, etc.)
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function send(string $to, string $subject, string $htmlContent, array $options = []): array;

    /**
     * Enviar campaña a múltiples destinatarios (batch)
     *
     * @param  array  $recipients  [['email' => '...', 'name' => '...', 'variables' => [...]]]
     * @param  string  $htmlContent  Template renderizado
     * @return array ['success' => bool, 'campaign_id' => string|null, 'sent_count' => int]
     */
    public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array;

    /**
     * Crear/actualizar template en el provider (si lo soporta)
     *
     * @param  string  $templateName  Nombre del template
     * @param  string  $htmlContent  Contenido HTML
     * @param  string  $subject  Asunto
     * @return string|null ID del template en el provider
     */
    public function syncTemplate(string $templateName, string $htmlContent, string $subject): ?string;

    /**
     * Obtener estadísticas de campaña
     *
     * @param  string  $campaignId  ID de la campaña en el provider
     * @return array Estadísticas (sent, opened, clicked, bounced, etc.)
     */
    public function getStats(string $campaignId): array;

    /**
     * Verificar credenciales del provider
     *
     * @return bool True si la conexión es exitosa
     */
    public function testConnection(): bool;

    /**
     * Obtener nombre del provider
     *
     * @return string Nombre del provider (Mailrelay, Mailtrap, etc.)
     */
    public function getName(): string;

    /**
     * Obtener límites de rate (emails/hora, emails/día)
     *
     * @return array ['emails_per_hour' => int, 'emails_per_day' => int]
     */
    public function getRateLimits(): array;
}
