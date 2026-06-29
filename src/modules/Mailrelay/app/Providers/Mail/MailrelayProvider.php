<?php

namespace Modules\Mailrelay\Providers\Mail;

use GuzzleHttp\Exception\RequestException;

/**
 * Provider para Mailrelay
 * Documentación API: https://mailrelay.com/api
 */
class MailrelayProvider extends AbstractMailProvider
{
    public function getName(): string
    {
        return 'Mailrelay';
    }

    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        if (! $this->isValidEmail($to)) {
            return $this->errorResponse("Email inválido: {$to}");
        }

        try {
            $preparedOptions = $this->prepareOptions($options);

            $response = $this->client->post("{$this->getConfig('api_url')}/emails/send", [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to' => $to,
                    'subject' => $subject,
                    'html_body' => $htmlContent,
                    'text_body' => $this->htmlToPlainText($htmlContent),
                    'from_email' => $preparedOptions['from_email'],
                    'from_name' => $preparedOptions['from_name'],
                    'reply_to' => $preparedOptions['reply_to'],
                    'track_opens' => $preparedOptions['track_opens'],
                    'track_clicks' => $preparedOptions['track_clicks'],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logInfo('Email enviado exitosamente', [
                    'to' => $to,
                    'message_id' => $body['message_id'] ?? null,
                ]);

                return $this->successResponse($body['message_id'] ?? null);
            }

            return $this->errorResponse(
                $body['error'] ?? "Error HTTP {$statusCode}"
            );
        } catch (RequestException $e) {
            $this->logError('Error enviando email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error de conexión con Mailrelay', $e);
        }
    }

    public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array
    {
        try {
            $preparedOptions = $this->prepareOptions($options);

            // Validar emails
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

            // Preparar lista de destinatarios
            $recipientEmails = array_map(function ($recipient) {
                return [
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? null,
                    'variables' => $recipient['variables'] ?? [],
                ];
            }, $validRecipients);

            $response = $this->client->post("{$this->getConfig('api_url')}/campaigns/send", [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'subject' => $subject,
                    'html_body' => $htmlContent,
                    'text_body' => $this->htmlToPlainText($htmlContent),
                    'recipients' => $recipientEmails,
                    'from_email' => $preparedOptions['from_email'],
                    'from_name' => $preparedOptions['from_name'],
                    'reply_to' => $preparedOptions['reply_to'],
                    'track_opens' => $preparedOptions['track_opens'],
                    'track_clicks' => $preparedOptions['track_clicks'],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logInfo('Campaña enviada exitosamente', [
                    'campaign_id' => $body['campaign_id'] ?? null,
                    'recipients_count' => count($validRecipients),
                ]);

                return [
                    'success' => true,
                    'campaign_id' => $body['campaign_id'] ?? null,
                    'sent_count' => $body['sent_count'] ?? count($validRecipients),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'campaign_id' => null,
                'sent_count' => 0,
                'error' => $body['error'] ?? "Error HTTP {$statusCode}",
            ];
        } catch (RequestException $e) {
            $this->logError('Error enviando campaña bulk', [
                'recipients_count' => count($recipients),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'campaign_id' => null,
                'sent_count' => 0,
                'error' => 'Error de conexión con Mailrelay: '.$e->getMessage(),
            ];
        }
    }

    public function syncTemplate(string $templateName, string $htmlContent, string $subject): ?string
    {
        try {
            $response = $this->client->post("{$this->getConfig('api_url')}/templates", [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'name' => $templateName,
                    'html_content' => $htmlContent,
                    'subject' => $subject,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logInfo('Template sincronizado', [
                    'template_name' => $templateName,
                    'template_id' => $body['template_id'] ?? null,
                ]);

                return $body['template_id'] ?? null;
            }

            $this->logError('Error sincronizando template', [
                'template_name' => $templateName,
                'error' => $body['error'] ?? "HTTP {$statusCode}",
            ]);

            return null;
        } catch (RequestException $e) {
            $this->logError('Error sincronizando template', [
                'template_name' => $templateName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getStats(string $campaignId): array
    {
        try {
            $response = $this->client->get("{$this->getConfig('api_url')}/campaigns/{$campaignId}/stats", [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                return $body;
            }

            return [
                'error' => $body['error'] ?? "Error HTTP {$statusCode}",
            ];
        } catch (RequestException $e) {
            return [
                'error' => 'Error obteniendo estadísticas: '.$e->getMessage(),
            ];
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->client->get("{$this->getConfig('api_url')}/ping", [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            $this->logError('Test de conexión falló', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getRateLimits(): array
    {
        return [
            'emails_per_hour' => $this->getConfig('rate_limit_hour', 10000),
            'emails_per_day' => $this->getConfig('rate_limit_day', 100000),
            'batch_size' => $this->getConfig('batch_size', 1000),
        ];
    }
}
