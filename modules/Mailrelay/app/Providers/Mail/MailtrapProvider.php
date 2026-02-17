<?php

namespace Modules\Mailrelay\Providers\Mail;

use GuzzleHttp\Exception\RequestException;

/**
 * Provider para Mailtrap
 * Documentación API: https://api-docs.mailtrap.io/
 */
class MailtrapProvider extends AbstractMailProvider
{
    public function getName(): string
    {
        return 'Mailtrap';
    }

    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        if (! $this->isValidEmail($to)) {
            return $this->errorResponse("Email inválido: {$to}");
        }

        try {
            $preparedOptions = $this->prepareOptions($options);

            // Mailtrap API v1 - Sending
            $response = $this->client->post('https://send.api.mailtrap.io/api/send', [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_token')}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'from' => [
                        'email' => $preparedOptions['from_email'],
                        'name' => $preparedOptions['from_name'],
                    ],
                    'to' => [
                        ['email' => $to],
                    ],
                    'subject' => $subject,
                    'html' => $htmlContent,
                    'text' => $this->htmlToPlainText($htmlContent),
                    'category' => $options['category'] ?? 'campaign',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200 && ($body['success'] ?? false)) {
                $messageId = $body['message_ids'][0] ?? null;

                $this->logInfo('Email enviado exitosamente vía Mailtrap', [
                    'to' => $to,
                    'message_id' => $messageId,
                ]);

                return $this->successResponse($messageId);
            }

            return $this->errorResponse(
                $body['errors'][0] ?? "Error HTTP {$statusCode}"
            );
        } catch (RequestException $e) {
            $this->logError('Error enviando email vía Mailtrap', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error de conexión con Mailtrap', $e);
        }
    }

    public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array
    {
        // Mailtrap no tiene API bulk nativa en el plan gratuito
        // Enviamos individualmente con rate limiting
        $sent = 0;
        $errors = [];
        $messageIds = [];

        $preparedOptions = $this->prepareOptions($options);

        // Validar destinatarios
        $validRecipients = array_filter($recipients, function ($recipient) {
            return $this->isValidEmail($recipient['email'] ?? '');
        });

        if (empty($validRecipients)) {
            return [
                'success' => false,
                'campaign_id' => null,
                'sent_count' => 0,
                'error' => 'No hay destinatarios válidos',
            ];
        }

        $this->logInfo('Iniciando envío bulk vía Mailtrap', [
            'recipients_count' => count($validRecipients),
        ]);

        foreach ($validRecipients as $index => $recipient) {
            // Preparar variables personalizadas si existen
            $personalizedHtml = $htmlContent;
            if (! empty($recipient['variables'])) {
                foreach ($recipient['variables'] as $key => $value) {
                    $placeholder = '{'.$key.'}';
                    $personalizedHtml = str_replace($placeholder, $value, $personalizedHtml);
                }
            }

            $result = $this->send(
                $recipient['email'],
                $subject,
                $personalizedHtml,
                array_merge($options, $preparedOptions)
            );

            if ($result['success']) {
                $sent++;
                if ($result['message_id']) {
                    $messageIds[] = $result['message_id'];
                }
            } else {
                $errors[] = [
                    'email' => $recipient['email'],
                    'error' => $result['error'],
                ];
            }

            // Rate limiting: Mailtrap permite ~100 emails/segundo en producción
            // Pero para ser conservadores, esperamos 10ms entre emails
            if ($index < count($validRecipients) - 1) {
                usleep(10000); // 10ms
            }
        }

        $this->logInfo('Envío bulk completado vía Mailtrap', [
            'sent' => $sent,
            'failed' => count($errors),
            'total' => count($validRecipients),
        ]);

        return [
            'success' => $sent > 0,
            'campaign_id' => implode(',', $messageIds), // Mailtrap no tiene campaign_id, usamos message_ids concatenados
            'sent_count' => $sent,
            'error' => empty($errors) ? null : 'Algunos emails fallaron',
            'errors' => $errors,
        ];
    }

    public function syncTemplate(string $templateName, string $htmlContent, string $subject): ?string
    {
        // Mailtrap soporta templates pero solo a través de su UI
        // No hay endpoint API para crear templates programáticamente en la versión actual
        // Retornamos null indicando que los templates se manejan localmente

        $this->logInfo('Mailtrap no soporta sync de templates vía API', [
            'template_name' => $templateName,
        ]);

        return null;
    }

    public function getStats(string $campaignId): array
    {
        // Mailtrap proporciona estadísticas vía webhooks en tiempo real
        // No hay endpoint directo para consultar stats de una campaña específica
        // Se debe configurar webhooks para recibir eventos (opens, clicks, bounces)

        return [
            'note' => 'Mailtrap proporciona estadísticas vía webhooks. Configure webhooks en https://mailtrap.io',
            'campaign_id' => $campaignId,
            'webhooks_url' => 'https://mailtrap.io/inboxes/webhooks',
        ];
    }

    public function testConnection(): bool
    {
        try {
            // Test usando API de account info
            $response = $this->client->get('https://mailtrap.io/api/v1/inboxes', [
                'headers' => [
                    'Api-Token' => $this->getConfig('api_token'),
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $this->logInfo('Test de conexión exitoso');

                return true;
            }

            $this->logError('Test de conexión falló', [
                'status_code' => $statusCode,
            ]);

            return false;
        } catch (RequestException $e) {
            $this->logError('Test de conexión falló con excepción', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getRateLimits(): array
    {
        // Límites de Mailtrap según su documentación
        return [
            'emails_per_second' => 100, // En producción
            'emails_per_hour' => 10000,
            'emails_per_day' => 100000, // Varía según plan
            'batch_size' => 1, // No soporta batch nativo
        ];
    }
}
