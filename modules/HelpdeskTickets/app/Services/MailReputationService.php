<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketMail;

/**
 * Modal 22 "Reputación y autenticación".
 *
 * Lee los registros DNS reales del dominio desde el que sale el correo
 * (SPF, DKIM y DMARC) y los cruza con las tasas de rebote y supresión que ya
 * están en la base. No hay nada estimado: si un registro no existe, se dice
 * que no existe en vez de rellenarlo con un ejemplo.
 *
 * Las consultas DNS se cachean una hora: son lentas (cada una es una consulta
 * de red) y su respuesta cambia como mucho cuando alguien toca la zona.
 */
class MailReputationService
{
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * Selectores DKIM habituales. No hay forma de enumerar los selectores
     * publicados de un dominio por DNS (no existe un registro índice), así
     * que se prueban los que usan los proveedores más comunes.
     *
     * @var array<int, string>
     */
    private const DKIM_SELECTORS = ['default', 'mail', 'google', 's1', 's2', 'k1', 'selector1', 'selector2', 'dkim'];

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $from = (string) config('mail.from.address');
        $domain = str_contains($from, '@') ? substr(strrchr($from, '@'), 1) : null;

        return [
            'domain' => $domain,
            'from' => $from,
            'auth' => $domain ? $this->authRecords($domain) : null,
            'rates' => $this->rates(),
            'settings' => $this->settings(),
        ];
    }

    /**
     * Estado de los dos interruptores del footer del modal (mockup: "avisar
     * a managers" / "suprimir automáticamente"), ambos OFF por defecto — la
     * evaluación real corre en ticket:check-reputation, programado cada
     * hora, que es quien de verdad activa 'suppressed' al cruzar el umbral.
     *
     * @return array<string, bool>
     */
    private function settings(): array
    {
        return [
            'notify_managers' => filter_var(Setting::get('tickets.reputation_notify_managers', false), FILTER_VALIDATE_BOOLEAN),
            'auto_suppress' => filter_var(Setting::get('tickets.reputation_auto_suppress', false), FILTER_VALIDATE_BOOLEAN),
            'suppressed' => filter_var(Setting::get('tickets.reputation_suppressed', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function authRecords(string $domain): array
    {
        return Cache::remember("helpdesktickets.reputation.{$domain}", self::CACHE_TTL_SECONDS, function () use ($domain) {
            $spf = $this->firstTxtMatching($domain, '/^v=spf1/i');
            $dmarc = $this->firstTxtMatching("_dmarc.{$domain}", '/^v=DMARC1/i');

            $dkimSelectors = [];
            foreach (self::DKIM_SELECTORS as $selector) {
                if ($this->firstTxtMatching("{$selector}._domainkey.{$domain}", '/(^v=DKIM1|p=)/i') !== null) {
                    $dkimSelectors[] = $selector;
                }
            }

            // La política de DMARC ('none' no protege de nada, solo informa)
            // es el dato accionable del registro, así que se extrae aparte.
            $dmarcPolicy = null;
            if ($dmarc !== null && preg_match('/\bp=([a-z]+)/i', $dmarc, $m)) {
                $dmarcPolicy = strtolower($m[1]);
            }

            return [
                'spf' => ['found' => $spf !== null, 'value' => $spf],
                'dkim' => ['found' => $dkimSelectors !== [], 'selectors' => $dkimSelectors],
                'dmarc' => ['found' => $dmarc !== null, 'value' => $dmarc, 'policy' => $dmarcPolicy],
            ];
        });
    }

    /**
     * Primer registro TXT del host que case con el patrón, o null.
     */
    private function firstTxtMatching(string $host, string $pattern): ?string
    {
        // dns_get_record() emite un warning y devuelve false cuando el host no
        // existe o no hay resolutor disponible (contenedores sin red saliente).
        $records = @dns_get_record($host, DNS_TXT);
        if (! is_array($records)) {
            return null;
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? ($record['entries'][0] ?? null);
            if (is_string($txt) && preg_match($pattern, $txt)) {
                return $txt;
            }
        }

        return null;
    }

    /**
     * Tasas reales de los últimos 30 días, sobre helpdesk_ticket_mails.
     *
     * @return array<string, mixed>
     */
    private function rates(): array
    {
        $since = now()->subDays(30);

        $total = TicketMail::query()->outbound()->where('created_at', '>=', $since)->count();
        $bounced = TicketMail::query()->outbound()->where('created_at', '>=', $since)->whereIn('status', ['bounced', 'failed'])->count();

        return [
            'window_days' => 30,
            'sent' => $total,
            'bounced' => $bounced,
            'bounce_rate' => $total > 0 ? round($bounced / $total * 100, 2) : null,
            'suppressed' => TicketEmailBlacklist::query()->where('is_active', true)->count(),
        ];
    }
}
