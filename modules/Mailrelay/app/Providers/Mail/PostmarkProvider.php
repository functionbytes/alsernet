<?php

namespace Modules\Mailrelay\Providers\Mail;

use GuzzleHttp\Exception\RequestException;

/**
 * Provider para Postmark
 * Documentación API: https://postmarkapp.com/developer/api/overview
 */
class PostmarkProvider extends AbstractMailProvider
{
    public function getName(): string
    {
        return 'Postmark';
    }

    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        if (! $this->isValidEmail($to)) {
            return $this->errorResponse("Email inválido: {$to}");
        }

        try {
            $preparedOptions = $this->prepareOptions($options);

            $response = $this->client->post('https://api.postmarkapp.com/email', [
                'headers' => [
                    'X-Postmark-Server-Token' => $this->getConfig('server_token'),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'From' => "{$preparedOptions['from_name']} <{$preparedOptions['from_email']}>",
                    'To' => $to,
                    'Subject' => $subject,
                    'HtmlBody' => $htmlContent,
                    'TextBody' => $this->htmlToPlainText($htmlContent),
                    'ReplyTo' => $preparedOptions['reply_to'],
                    'TrackOpens' => $preparedOptions['track_opens'],
                    'TrackLinks' => $preparedOptions['track_clicks'] ? 'HtmlAndText' : 'None',
                    'MessageStream' => $this->getConfig('message_stream', 'outbound'),
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                $messageId = $body['MessageID'] ?? null;

                $this->logInfo('Email enviado exitosamente', [
                    'to' => $to,
                    'message_id' => $messageId,
                ]);

                return $this->successResponse($messageId);
            }

            return $this->errorResponse(
                $body['Message'] ?? "Error HTTP {$statusCode}"
            );
        } catch (RequestException $e) {
            $this->logError('Error enviando email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error de conexión con Postmark', $e);
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

            // Postmark permite batch de hasta 500 emails
            $chunks = array_chunk($validRecipients, 500);
            $totalSent = 0;
            $errors = [];

            foreach ($chunks as $chunk) {
                $messages = array_map(function ($recipient) use ($subject, $htmlContent, $preparedOptions) {
                    return [
                        'From' => "{$preparedOptions['from_name']} <{$preparedOptions['from_email']}>",
                        'To' => $recipient['email'],
                        'Subject' => $subject,
                        'HtmlBody' => $htmlContent,
                        'TextBody' => $this->htmlToPlainText($htmlContent),
                        'ReplyTo' => $preparedOptions['reply_to'],
                        'TrackOpens' => $preparedOptions['track_opens'],
                        'TrackLinks' => $preparedOptions['track_clicks'] ? 'HtmlAndText' : 'None',
                        'MessageStream' => $this->getConfig('message_stream', 'outbound'),
                        'Metadata' => $recipient['variables'] ?? [],
                    ];
                }, $chunk);

                $response = $this->client->post('https://api.postmarkapp.com/email/batch', [
                    'headers' => [
                        'X-Postmark-Server-Token' => $this->getConfig('server_token'),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $messages,
                ]);

                if ($response->getStatusCode() === 200) {
                    $body = json_decode($response->getBody()->getContents(), true);

                    // Contar exitosos
                    foreach ($body as $result) {
                        if ($result['ErrorCode'] === 0) {
                            $totalSent++;
                        } else {
                            $errors[] = $result['Message'] ?? 'Unknown error';
                        }
                    }
                } else {
                    $body = json_decode($response->getBody()->getContents(), true);
                    $errors[] = $body['Message'] ?? 'Unknown error';
                }

                // Respetar rate limits
                if (count($chunks) > 1) {
                    usleep(100000); // 100ms entre batches
                }
            }

            $this->logInfo('Envío masivo completado', [
                'total_sent' => $totalSent,
                'total_recipients' => count($validRecipients),
            ]);

            return [
                'success' => $totalSent > 0,
                'campaign_id' => null,
                'sent_count' => $totalSent,
                'errors' => $errors,
            ];
        } catch (RequestException $e) {
            $this->logError('Error en envío masivo', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'campaign_id' => null,
                'sent_count' => 0,
                'error' => 'Error de conexión con Postmark',
            ];
        }
    }

    public function syncTemplate(string $templateHtml, array $metadata = []): array
    {
        try {
            $templateName = $metadata['name'] ?? 'template-'.time();
            $templateAlias = $metadata['alias'] ?? str_slug($templateName);

            // Crear template en Postmark
            $response = $this->client->post('https://api.postmarkapp.com/templates', [
                'headers' => [
                    'X-Postmark-Server-Token' => $this->getConfig('server_token'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'Name' => $templateName,
                    'Alias' => $templateAlias,
                    'Subject' => $metadata['subject'] ?? 'Template',
                    'HtmlBody' => $templateHtml,
                    'TextBody' => $this->htmlToPlainText($templateHtml),
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), true);

                return [
                    'success' => true,
                    'template_id' => $body['TemplateId'] ?? null,
                    'template_alias' => $body['Alias'] ?? null,
                ];
            }

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => false,
                'template_id' => null,
                'error' => $body['Message'] ?? 'Error creando template',
            ];
        } catch (RequestException $e) {
            $this->logError('Error sincronizando template', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'template_id' => null,
                'error' => 'Error de conexión con Postmark',
            ];
        }
    }

    public function getStats(string $campaignId): array
    {
        try {
            // Postmark stats requiere message stream
            $messageStream = $this->getConfig('message_stream', 'outbound');

            $response = $this->client->get('https://api.postmarkapp.com/stats/outbound', [
                'headers' => [
                    'X-Postmark-Server-Token' => $this->getConfig('server_token'),
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                return [
                    'sent' => $data['Sent'] ?? 0,
                    'delivered' => ($data['Sent'] ?? 0) - ($data['Bounced'] ?? 0),
                    'opens' => $data['Opens'] ?? 0,
                    'clicks' => $data['TotalClicks'] ?? 0,
                    'bounces' => $data['Bounced'] ?? 0,
                    'spam_complaints' => $data['SpamComplaints'] ?? 0,
                ];
            }

            return [
                'sent' => 0,
                'delivered' => 0,
                'opens' => 0,
                'clicks' => 0,
                'bounces' => 0,
            ];
        } catch (RequestException $e) {
            $this->logError('Error obteniendo estadísticas', [
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => 0,
                'delivered' => 0,
                'opens' => 0,
                'clicks' => 0,
                'bounces' => 0,
            ];
        }
    }

    public function testConnection(): array
    {
        try {
            // Verificar server token obteniendo server info
            $response = $this->client->get('https://api.postmarkapp.com/server', [
                'headers' => [
                    'X-Postmark-Server-Token' => $this->getConfig('server_token'),
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), true);

                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con Postmark',
                    'server_name' => $body['Name'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Error de autenticación con Postmark',
            ];
        } catch (RequestException $e) {
            $this->logError('Error probando conexión', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con Postmark: '.$e->getMessage(),
            ];
        }
    }

    public function getRateLimits(): array
    {
        return [
            'max_per_second' => 10, // Default for new accounts
            'max_per_day' => 10000, // Depends on plan
            'batch_size' => 500,
        ];
    }
}
