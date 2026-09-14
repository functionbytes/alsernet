<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Exceptions\WhatsAppTemplateException;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;

/**
 * Registra una plantilla nueva directamente en Meta (WhatsApp Business Manager)
 * vía Graph API, sin que el agente tenga que entrar a Business Manager.
 *
 * Meta siempre la deja en revisión ('PENDING') — la aprobación no se puede
 * saltar ni acelerar desde aquí. SyncWhatsAppTemplatesJob es quien más tarde
 * trae el estado real (approved/rejected) una vez Meta la revisa.
 */
class WhatsAppTemplateCreationService
{
    /**
     * @param  array<int, string>  $bodyExamples  valores de muestra para cada {{n}} del body, en orden
     */
    public function create(
        string $name,
        string $language,
        string $category,
        string $body,
        array $bodyExamples = [],
    ): WhatsAppTemplate {
        $accessToken = config('helpdesk.integrations.whatsapp.access_token');
        $businessAccountId = config('helpdesk.integrations.whatsapp.business_account_id');
        $apiVersion = config('helpdesk.integrations.whatsapp.api_version', 'v19.0');

        if (! filled($accessToken) || ! filled($businessAccountId)) {
            throw new WhatsAppTemplateException('WhatsApp no está configurado (falta access token o business account id).');
        }

        $bodyComponent = ['type' => 'BODY', 'text' => $body];
        if (! empty($bodyExamples)) {
            // Meta exige un ejemplo por cada {{n}} del body cuando la plantilla
            // tiene variables — sin esto rechaza la creación.
            $bodyComponent['example'] = ['body_text' => [array_values($bodyExamples)]];
        }

        $payload = [
            'name' => $name,
            'language' => $language,
            'category' => strtoupper($category),
            'components' => [$bodyComponent],
        ];

        $url = "https://graph.facebook.com/{$apiVersion}/{$businessAccountId}/message_templates";

        $response = Http::withToken($accessToken)
            ->timeout(20)
            ->retry(2, 500, throw: false)
            ->post($url, $payload);

        if ($response->failed()) {
            // error_user_msg es el motivo en lenguaje llano que Meta muestra en su
            // propio Business Manager (ej. "Las variables no pueden estar al
            // principio ni al final") — mucho más accionable para el agente que
            // el error.message genérico ("Invalid parameter").
            $error = $response->json('error.error_user_msg') ?? $response->json('error.message', 'Unknown error');
            $code = $response->json('error.code', $response->status());

            Log::warning('WhatsAppTemplateCreationService: Meta rejected template creation', [
                'name' => $name,
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new WhatsAppTemplateException("WhatsApp API error ({$code}): {$error}", [
                'name' => $name,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }

        Log::info('WhatsAppTemplateCreationService: template submitted to Meta', [
            'name' => $name,
            'meta_id' => $response->json('id'),
        ]);

        return WhatsAppTemplate::query()->create([
            'external_id' => $name,
            'display_name' => $name,
            'language' => $language,
            'category' => strtolower($category),
            'status' => strtolower((string) $response->json('status', 'pending')),
            'body_template' => $body,
        ]);
    }
}
