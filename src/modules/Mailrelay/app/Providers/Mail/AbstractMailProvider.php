<?php

namespace Modules\Mailrelay\Providers\Mail;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Contracts\MailProviderInterface;

/**
 * Clase base para todos los providers de email
 * Implementa funcionalidad común y utilidades compartidas
 */
abstract class AbstractMailProvider implements MailProviderInterface
{
    protected Client $client;

    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'verify' => $config['verify_ssl'] ?? true,
            'http_errors' => false, // Manejamos errores manualmente
        ]);
    }

    /**
     * Extraer texto plano del HTML (para providers que lo requieren)
     */
    protected function htmlToPlainText(string $html): string
    {
        // Remover script y style tags
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $text);

        // Convertir line breaks
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<p[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/p>/i', "\n", $text);

        // Remover HTML tags
        $text = strip_tags($text);

        // Limpiar espacios
        $text = html_entity_decode($text);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $text;
    }

    /**
     * Validar email
     */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Log de errores del provider
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('mailrelay')->error("[{$this->getName()}] {$message}", array_merge($context, [
            'provider' => $this->getName(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log de información
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('mailrelay')->info("[{$this->getName()}] {$message}", array_merge($context, [
            'provider' => $this->getName(),
        ]));
    }

    /**
     * Obtener configuración específica con fallback
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function testConnection(): bool
    {
        return true;
    }

    /**
     * Preparar opciones de envío comunes
     */
    protected function prepareOptions(array $options): array
    {
        return [
            'from_email' => $options['from_email'] ?? $this->getConfig('from_email'),
            'from_name' => $options['from_name'] ?? $this->getConfig('from_name'),
            'reply_to' => $options['reply_to'] ?? $this->getConfig('reply_to'),
            'track_opens' => $options['track_opens'] ?? true,
            'track_clicks' => $options['track_clicks'] ?? true,
        ];
    }

    /**
     * Construir respuesta estándar de error
     */
    protected function errorResponse(string $message, ?\Throwable $exception = null): array
    {
        $error = [
            'success' => false,
            'message_id' => null,
            'error' => $message,
        ];

        if ($exception) {
            $error['exception'] = get_class($exception);
            $error['exception_message'] = $exception->getMessage();
        }

        return $error;
    }

    /**
     * Construir respuesta estándar de éxito
     */
    protected function successResponse(?string $messageId = null, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'message_id' => $messageId,
            'error' => null,
        ], $extra);
    }
}
