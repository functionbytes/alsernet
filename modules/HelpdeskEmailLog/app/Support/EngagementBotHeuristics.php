<?php

namespace Modules\HelpdeskEmailLog\Support;

use Illuminate\Support\Carbon;

/**
 * Marca (nunca descarta) una apertura o un clic como "probable bot" —
 * heurística, no certeza: ya existía la nota de honestidad de que Apple Mail
 * Privacy Protection y el proxy de imágenes de Gmail precargan el píxel
 * automáticamente; esto solo hace explícitos ALGUNOS de esos casos en vez de
 * dejar el 100% igual de ambiguo.
 *
 * Dos señales, cualquiera basta:
 *  1. User-Agent de un escáner/proxy que se identifica a sí mismo (lista
 *     corta y conservadora a propósito — "Outlook"/"Microsoft Office" NO
 *     están porque los usa tanto un escáner como un cliente de Outlook real
 *     abriendo el correo de verdad; incluirlos dispararía falsos positivos
 *     masivos sobre aperturas humanas legítimas).
 *  2. El hit llega demasiado rápido tras el envío para que un humano lo haya
 *     leído y actuado — casi siempre el propio servidor de correo destino
 *     precargando imágenes/enlaces en cuanto el mensaje entra a su cola, no
 *     el destinatario.
 */
class EngagementBotHeuristics
{
    /**
     * Substrings en minúscula. Cada uno se anuncia a sí mismo en su propio
     * User-Agent — no son heurísticas de "esto parece raro", son escáneres
     * documentados que hacen esto por diseño (proxy de imágenes, filtro de
     * seguridad corporativo, crawler de vista previa de enlaces).
     *
     * @var list<string>
     */
    private const BOT_USER_AGENT_SUBSTRINGS = [
        'googleimageproxy',   // Proxy de imágenes de Gmail
        'yahoomailproxy',     // Proxy de imágenes de Yahoo Mail
        'mimecast',           // Filtro de seguridad de correo corporativo
        'proofpoint',
        'barracuda',
        'symantec',
        'trendmicro',
        'facebookexternalhit', // Crawler de vista previa de enlaces
        'slackbot',
        'whatsapp',
        'discordbot',
        'curl/', 'wget/', 'python-requests/', 'go-http-client/', // scripts/monitorización genéricos
    ];

    /**
     * Ventana desde el envío dentro de la cual un hit se considera
     * "demasiado rápido para ser humano".
     */
    private const TOO_FAST_SECONDS = 2;

    public static function isLikelyBot(?string $userAgent, ?Carbon $sentAt, ?Carbon $hitAt): bool
    {
        if ($userAgent) {
            $ua = strtolower($userAgent);

            foreach (self::BOT_USER_AGENT_SUBSTRINGS as $needle) {
                if (str_contains($ua, $needle)) {
                    return true;
                }
            }
        }

        if ($sentAt && $hitAt && $hitAt->diffInSeconds($sentAt, true) <= self::TOO_FAST_SECONDS) {
            return true;
        }

        return false;
    }
}
