<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Throwable;

/**
 * Modal 31 "Notificaciones": el mockup mostraba casillas de Slack/Teams que
 * no hacían nada — "este proyecto no tiene esas integraciones" (comentario
 * ya en openNotificationsModal()). Esta es la integración real, mínima y
 * honesta: un webhook entrante por plataforma (sin OAuth ni bot propio, ni
 * preferencia por agente — Slack/Teams son canales de EQUIPO, no un buzón
 * personal). OFF por defecto: sin URL configurada, notify() no hace nada.
 *
 * La URL se guarda cifrada (Setting::setEncrypted(), mismo trato que un
 * secreto SMTP/API de terceros) y se valida con OutboundUrlGuard al
 * guardarla — apunta a webhooks de Slack/Teams, nunca a un host interno.
 */
class TeamChannelNotifier
{
    private const SLACK_KEY = 'tickets.slack_webhook_url';

    private const TEAMS_KEY = 'tickets.teams_webhook_url';

    public function slackConfigured(): bool
    {
        return $this->webhookUrl(self::SLACK_KEY) !== null;
    }

    public function teamsConfigured(): bool
    {
        return $this->webhookUrl(self::TEAMS_KEY) !== null;
    }

    /**
     * Manda $text a los webhooks configurados. Un fallo en uno no bloquea al
     * otro, y ninguno de los dos bloquea al flujo real (breach de SLA, etc.)
     * que disparó el aviso — se registra y se traga.
     *
     * @return array{slack: bool|null, teams: bool|null} true = enviado, false = falló, null = no configurado
     */
    public function notify(string $text): array
    {
        return [
            'slack' => $this->send(self::SLACK_KEY, ['text' => $text]),
            'teams' => $this->send(self::TEAMS_KEY, [
                '@type' => 'MessageCard',
                '@context' => 'http://schema.org/extensions',
                'text' => $text,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $settingKey, array $payload): ?bool
    {
        $url = $this->webhookUrl($settingKey);

        if (! $url) {
            return null;
        }

        try {
            $response = Http::timeout(5)->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('TeamChannelNotifier: webhook respondió con error', [
                    'setting' => $settingKey,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('TeamChannelNotifier: no se pudo avisar al webhook', [
                'setting' => $settingKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function webhookUrl(string $settingKey): ?string
    {
        $value = Setting::getDecrypted($settingKey, '');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
