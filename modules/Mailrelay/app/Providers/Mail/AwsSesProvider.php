<?php

namespace Modules\Mailrelay\Providers\Mail;

use GuzzleHttp\Exception\RequestException;

/**
 * Provider para AWS SES (Simple Email Service)
 * Documentación API: https://docs.aws.amazon.com/ses/latest/APIReference/
 *
 * Nota: Esta implementación usa la API v2 de SES (SESV2)
 */
class AwsSesProvider extends AbstractMailProvider
{
    public function getName(): string
    {
        return 'AWS SES';
    }

    /**
     * Obtener región de AWS (por defecto us-east-1)
     */
    protected function getRegion(): string
    {
        return $this->getConfig('region', 'us-east-1');
    }

    /**
     * Obtener endpoint de AWS SES para la región
     */
    protected function getEndpoint(): string
    {
        return "https://email.{$this->getRegion()}.amazonaws.com";
    }

    /**
     * Generar headers de autenticación AWS Signature v4
     * Implementación simplificada - en producción usar AWS SDK
     */
    protected function getAuthHeaders(string $method, string $path, array $payload): array
    {
        $accessKey = $this->getConfig('access_key');
        $secretKey = $this->getConfig('secret_key');
        $region = $this->getRegion();
        $service = 'ses';

        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        // En producción, usar AWS SDK para firma correcta
        // Esta es una implementación simplificada
        return [
            'Authorization' => "AWS4-HMAC-SHA256 Credential={$accessKey}/{$date}/{$region}/{$service}/aws4_request",
            'X-Amz-Date' => $timestamp,
            'Content-Type' => 'application/json',
        ];
    }

    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        if (! $this->isValidEmail($to)) {
            return $this->errorResponse("Email inválido: {$to}");
        }

        try {
            $preparedOptions = $this->prepareOptions($options);

            // Usar AWS SES v2 API
            $response = $this->client->post("{$this->getEndpoint()}/v2/email/outbound-emails", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Amz-Access-Key' => $this->getConfig('access_key'),
                    'X-Amz-Secret-Key' => $this->getConfig('secret_key'),
                ],
                'json' => [
                    'FromEmailAddress' => $preparedOptions['from_email'],
                    'Destination' => [
                        'ToAddresses' => [$to],
                    ],
                    'Content' => [
                        'Simple' => [
                            'Subject' => [
                                'Data' => $subject,
                                'Charset' => 'UTF-8',
                            ],
                            'Body' => [
                                'Html' => [
                                    'Data' => $htmlContent,
                                    'Charset' => 'UTF-8',
                                ],
                                'Text' => [
                                    'Data' => $this->htmlToPlainText($htmlContent),
                                    'Charset' => 'UTF-8',
                                ],
                            ],
                        ],
                    ],
                    'ConfigurationSetName' => $this->getConfig('configuration_set'),
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                $messageId = $body['MessageId'] ?? null;

                $this->logInfo('Email enviado exitosamente', [
                    'to' => $to,
                    'message_id' => $messageId,
                ]);

                return $this->successResponse($messageId);
            }

            $body = json_decode($response->getBody()->getContents(), true);

            return $this->errorResponse(
                $body['message'] ?? "Error HTTP {$statusCode}"
            );
        } catch (RequestException $e) {
            $this->logError('Error enviando email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error de conexión con AWS SES', $e);
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

            // AWS SES recomienda máximo 50 destinatarios por request
            $chunks = array_chunk($validRecipients, 50);
            $totalSent = 0;
            $errors = [];

            foreach ($chunks as $chunkIndex => $chunk) {
                $toAddresses = array_map(fn ($r) => $r['email'], $chunk);

                $response = $this->client->post("{$this->getEndpoint()}/v2/email/outbound-emails", [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-Amz-Access-Key' => $this->getConfig('access_key'),
                        'X-Amz-Secret-Key' => $this->getConfig('secret_key'),
                    ],
                    'json' => [
                        'FromEmailAddress' => $preparedOptions['from_email'],
                        'Destination' => [
                            'ToAddresses' => $toAddresses,
                        ],
                        'Content' => [
                            'Simple' => [
                                'Subject' => [
                                    'Data' => $subject,
                                    'Charset' => 'UTF-8',
                                ],
                                'Body' => [
                                    'Html' => [
                                        'Data' => $htmlContent,
                                        'Charset' => 'UTF-8',
                                    ],
                                ],
                            ],
                        ],
                        'ConfigurationSetName' => $this->getConfig('configuration_set'),
                    ],
                ]);

                if ($response->getStatusCode() === 200) {
                    $totalSent += count($chunk);
                } else {
                    $body = json_decode($response->getBody()->getContents(), true);
                    $errors[] = $body['message'] ?? 'Unknown error';
                }

                // Respetar rate limits (14 emails/segundo por defecto)
                if ($chunkIndex < count($chunks) - 1) {
                    usleep(500000); // 500ms entre batches
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
                'error' => 'Error de conexión con AWS SES',
            ];
        }
    }

    public function syncTemplate(string $templateHtml, array $metadata = []): array
    {
        try {
            $templateName = $metadata['name'] ?? 'template-'.time();

            $response = $this->client->post("{$this->getEndpoint()}/v2/email/templates", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Amz-Access-Key' => $this->getConfig('access_key'),
                    'X-Amz-Secret-Key' => $this->getConfig('secret_key'),
                ],
                'json' => [
                    'TemplateName' => $templateName,
                    'TemplateContent' => [
                        'Subject' => $metadata['subject'] ?? 'Email Template',
                        'Html' => $templateHtml,
                        'Text' => $this->htmlToPlainText($templateHtml),
                    ],
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'template_id' => $templateName,
                ];
            }

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => false,
                'template_id' => null,
                'error' => $body['message'] ?? 'Error creando template',
            ];
        } catch (RequestException $e) {
            $this->logError('Error sincronizando template', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'template_id' => null,
                'error' => 'Error de conexión con AWS SES',
            ];
        }
    }

    public function getStats(string $campaignId): array
    {
        // AWS SES stats requieren CloudWatch o Configuration Sets
        // Esta es una implementación simplificada
        try {
            // En producción, usar CloudWatch API para obtener métricas
            return [
                'sent' => 0,
                'delivered' => 0,
                'opens' => 0,
                'clicks' => 0,
                'bounces' => 0,
                'note' => 'Stats requieren CloudWatch o Configuration Sets',
            ];
        } catch (\Exception $e) {
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
            // Obtener account sending limits para verificar credenciales
            $response = $this->client->get("{$this->getEndpoint()}/v2/email/account", [
                'headers' => [
                    'X-Amz-Access-Key' => $this->getConfig('access_key'),
                    'X-Amz-Secret-Key' => $this->getConfig('secret_key'),
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), true);

                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con AWS SES',
                    'sending_enabled' => $body['SendingEnabled'] ?? false,
                ];
            }

            return [
                'success' => false,
                'message' => 'Error de autenticación con AWS SES',
            ];
        } catch (RequestException $e) {
            $this->logError('Error probando conexión', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con AWS SES: '.$e->getMessage(),
            ];
        }
    }

    public function getRateLimits(): array
    {
        return [
            'max_per_second' => 14, // Default for new accounts
            'max_per_day' => 200, // Default for sandbox
            'batch_size' => 50,
            'note' => 'Limits vary by account - check SES console',
        ];
    }
}
