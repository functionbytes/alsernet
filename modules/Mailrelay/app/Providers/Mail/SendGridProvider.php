<?php

namespace Modules\Mailrelay\Providers\Mail;

use GuzzleHttp\Exception\RequestException;

/**
 * Provider para SendGrid
 * Documentación API: https://docs.sendgrid.com/api-reference/mail-send/mail-send
 */
class SendGridProvider extends AbstractMailProvider
{
    public function getName(): string
    {
        return 'SendGrid';
    }

    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        if (! $this->isValidEmail($to)) {
            return $this->errorResponse("Email inválido: {$to}");
        }

        try {
            $preparedOptions = $this->prepareOptions($options);

            $response = $this->client->post('https://api.sendgrid.com/v3/mail/send', [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'personalizations' => [
                        [
                            'to' => [
                                ['email' => $to],
                            ],
                        ],
                    ],
                    'from' => [
                        'email' => $preparedOptions['from_email'],
                        'name' => $preparedOptions['from_name'],
                    ],
                    'reply_to' => [
                        'email' => $preparedOptions['reply_to'],
                    ],
                    'subject' => $subject,
                    'content' => [
                        [
                            'type' => 'text/html',
                            'value' => $htmlContent,
                        ],
                        [
                            'type' => 'text/plain',
                            'value' => $this->htmlToPlainText($htmlContent),
                        ],
                    ],
                    'tracking_settings' => [
                        'click_tracking' => [
                            'enable' => $preparedOptions['track_clicks'],
                        ],
                        'open_tracking' => [
                            'enable' => $preparedOptions['track_opens'],
                        ],
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 202) {
                $messageId = $response->getHeader('X-Message-Id')[0] ?? null;

                $this->logInfo('Email enviado exitosamente', [
                    'to' => $to,
                    'message_id' => $messageId,
                ]);

                return $this->successResponse($messageId);
            }

            $body = json_decode($response->getBody()->getContents(), true);

            return $this->errorResponse(
                $body['errors'][0]['message'] ?? "Error HTTP {$statusCode}"
            );
        } catch (RequestException $e) {
            $this->logError('Error enviando email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error de conexión con SendGrid', $e);
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

            // SendGrid permite hasta 1000 destinatarios por request
            $chunks = array_chunk($validRecipients, 1000);
            $totalSent = 0;
            $errors = [];

            foreach ($chunks as $chunk) {
                $personalizations = array_map(function ($recipient) {
                    return [
                        'to' => [
                            [
                                'email' => $recipient['email'],
                                'name' => $recipient['name'] ?? null,
                            ],
                        ],
                        'custom_args' => $recipient['variables'] ?? [],
                    ];
                }, $chunk);

                $response = $this->client->post('https://api.sendgrid.com/v3/mail/send', [
                    'headers' => [
                        'Authorization' => "Bearer {$this->getConfig('api_key')}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'personalizations' => $personalizations,
                        'from' => [
                            'email' => $preparedOptions['from_email'],
                            'name' => $preparedOptions['from_name'],
                        ],
                        'reply_to' => [
                            'email' => $preparedOptions['reply_to'],
                        ],
                        'subject' => $subject,
                        'content' => [
                            [
                                'type' => 'text/html',
                                'value' => $htmlContent,
                            ],
                        ],
                        'tracking_settings' => [
                            'click_tracking' => ['enable' => $preparedOptions['track_clicks']],
                            'open_tracking' => ['enable' => $preparedOptions['track_opens']],
                        ],
                    ],
                ]);

                if ($response->getStatusCode() === 202) {
                    $totalSent += count($chunk);
                } else {
                    $body = json_decode($response->getBody()->getContents(), true);
                    $errors[] = $body['errors'][0]['message'] ?? 'Unknown error';
                }

                // Respetar rate limits
                if (count($chunks) > 1) {
                    usleep(100000); // 100ms entre requests
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
                'error' => 'Error de conexión con SendGrid',
            ];
        }
    }

    public function syncTemplate(string $templateHtml, array $metadata = []): array
    {
        try {
            // SendGrid usa templates dinámicas
            $response = $this->client->post('https://api.sendgrid.com/v3/templates', [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'name' => $metadata['name'] ?? 'Template '.date('Y-m-d H:i:s'),
                    'generation' => 'dynamic',
                ],
            ]);

            if ($response->getStatusCode() === 201) {
                $body = json_decode($response->getBody()->getContents(), true);
                $templateId = $body['id'];

                // Crear versión del template
                $versionResponse = $this->client->post("https://api.sendgrid.com/v3/templates/{$templateId}/versions", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->getConfig('api_key')}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'name' => 'v1',
                        'subject' => $metadata['subject'] ?? 'Template',
                        'html_content' => $templateHtml,
                        'active' => 1,
                    ],
                ]);

                if ($versionResponse->getStatusCode() === 201) {
                    return [
                        'success' => true,
                        'template_id' => $templateId,
                    ];
                }
            }

            return [
                'success' => false,
                'template_id' => null,
                'error' => 'Error creando template',
            ];
        } catch (RequestException $e) {
            $this->logError('Error sincronizando template', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'template_id' => null,
                'error' => 'Error de conexión con SendGrid',
            ];
        }
    }

    public function getStats(string $campaignId): array
    {
        try {
            // SendGrid stats requiere fecha
            $startDate = now()->subDays(30)->format('Y-m-d');

            $response = $this->client->get('https://api.sendgrid.com/v3/stats', [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                ],
                'query' => [
                    'start_date' => $startDate,
                    'aggregated_by' => 'day',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                $stats = [
                    'sent' => 0,
                    'delivered' => 0,
                    'opens' => 0,
                    'clicks' => 0,
                    'bounces' => 0,
                ];

                foreach ($data as $day) {
                    foreach ($day['stats'] as $stat) {
                        $stats['sent'] += $stat['metrics']['requests'] ?? 0;
                        $stats['delivered'] += $stat['metrics']['delivered'] ?? 0;
                        $stats['opens'] += $stat['metrics']['opens'] ?? 0;
                        $stats['clicks'] += $stat['metrics']['clicks'] ?? 0;
                        $stats['bounces'] += $stat['metrics']['bounces'] ?? 0;
                    }
                }

                return $stats;
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
            // Verificar API key obteniendo stats
            $response = $this->client->get('https://api.sendgrid.com/v3/stats', [
                'headers' => [
                    'Authorization' => "Bearer {$this->getConfig('api_key')}",
                ],
                'query' => [
                    'start_date' => now()->format('Y-m-d'),
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con SendGrid',
                ];
            }

            return [
                'success' => false,
                'message' => 'Error de autenticación con SendGrid',
            ];
        } catch (RequestException $e) {
            $this->logError('Error probando conexión', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con SendGrid: '.$e->getMessage(),
            ];
        }
    }

    public function getRateLimits(): array
    {
        return [
            'max_per_second' => 100,
            'max_per_day' => $this->getConfig('daily_limit', 100000),
            'batch_size' => 1000,
        ];
    }
}
