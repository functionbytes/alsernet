<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Services\DomainAuthenticationChecker;

/**
 * Dashboard de reputación: autenticación de dominio (SPF/DKIM/DMARC, por
 * DNS — sin proveedor conectado) y tasas de rebote/queja calculadas sobre
 * email_logs (ya existente, sin dependencia nueva). Los dominios a vigilar
 * se gestionan en Settings → Actividad de correo (reputation_domains).
 */
class EmailReputationController extends Controller
{
    public function __construct(private readonly DomainAuthenticationChecker $checker)
    {
        $this->middleware('can:helpdeskemaillog.view')->only('index');
        $this->middleware('can:helpdeskemaillog.manage')->only('refresh');
    }

    public function index(): View
    {
        $days = (int) Setting::get('helpdeskemaillog.reputation_window_days', config('helpdeskemaillog.reputation_window_days', 30));
        $domains = $this->configuredDomains();

        $rows = collect($domains)->map(fn (array $d) => [
            'domain' => $d['domain'],
            'auth' => $this->checker->check($d['domain'], $d['dkim_selectors'] ?? []),
            'stats' => EmailLog::reputationStats($days, $d['domain']),
        ]);

        return view('helpdeskemaillog::emails.reputation', [
            'rows' => $rows,
            'overall' => EmailLog::reputationStats($days),
            'days' => $days,
            'suggestions' => $this->suggestedDomains($domains),
            'thresholds' => [
                'bounce_warning' => (float) Setting::get('helpdeskemaillog.bounce_rate_warning_pct', config('helpdeskemaillog.bounce_rate_warning_pct')),
                'bounce_critical' => (float) Setting::get('helpdeskemaillog.bounce_rate_critical_pct', config('helpdeskemaillog.bounce_rate_critical_pct')),
                'complaint_warning' => (float) Setting::get('helpdeskemaillog.complaint_rate_warning_pct', config('helpdeskemaillog.complaint_rate_warning_pct')),
                'complaint_critical' => (float) Setting::get('helpdeskemaillog.complaint_rate_critical_pct', config('helpdeskemaillog.complaint_rate_critical_pct')),
            ],
        ]);
    }

    /**
     * Fuerza una nueva consulta DNS de un dominio (ignora la caché de
     * reputation_dns_cache_hours).
     */
    public function refresh(Request $request): RedirectResponse
    {
        $domain = trim((string) $request->input('domain'));

        if ($domain !== '') {
            Cache::forget("helpdeskemaillog:domain-auth:{$domain}");
        }

        return back()->with('success', __('helpdeskemaillog::emaillog.reputation.refreshed', ['domain' => $domain]));
    }

    /**
     * @return list<array{domain: string, dkim_selectors: list<string>}>
     */
    public static function configuredDomains(): array
    {
        $raw = Setting::get('helpdeskemaillog.reputation_domains', '[]');
        $data = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($data) ? $data : [];
    }

    /**
     * Dominios vistos en from_address en los últimos 30 días que aún no
     * están en la lista vigilada, junto con su volumen de envíos — solo una
     * sugerencia para el admin al configurar, nunca se vigilan
     * automáticamente (mezclaría dominios de prueba/spam con los reales sin
     * que nadie lo haya decidido). Ordenados por volumen desc: con cientos
     * de dominios detectados, la vista solo destaca los más relevantes
     * primero (ver emails/reputation.blade.php).
     *
     * @param  list<array{domain: string}>  $configured
     * @return array<string, int> dominio => nº de envíos, ordenado desc
     */
    private function suggestedDomains(array $configured): array
    {
        $known = collect($configured)->pluck('domain')->all();

        // Agrupación y orden en SQL (SUBSTRING_INDEX + GROUP BY + COUNT) en vez
        // de traer from_address de cada fila del rango y agregar en PHP.
        return EmailLog::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('from_address')
            ->where('from_address', 'like', '%@%')
            ->selectRaw("SUBSTRING_INDEX(from_address, '@', -1) as domain, COUNT(*) as sends")
            ->groupBy('domain')
            ->orderByDesc('sends')
            ->pluck('sends', 'domain')
            ->reject(fn ($sends, $domain) => in_array($domain, $known, true))
            ->all();
    }
}
