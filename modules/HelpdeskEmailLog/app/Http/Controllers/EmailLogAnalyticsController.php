<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Services\EmailLogAnalyticsService;
use Throwable;

/**
 * Analítica derivada del log (rendimiento por mailable, latencia de entrega,
 * entregabilidad por dominio destinatario) — misma tabla email_logs que el
 * resto del módulo, sin ninguna migración nueva. Ver EmailLogAnalyticsService
 * para el cálculo de cada bloque.
 *
 * Ruta pendiente de registrar (no se toca routes/web.php desde aquí):
 *   GET /panel/helpdeskemaillog/analytics  ->  name('helpdeskemaillog.analytics.index')
 *   middleware ['auth'], mismo grupo/prefix que el resto de
 *   panel/helpdeskemaillog (ver routes/web.php).
 */
class EmailLogAnalyticsController extends Controller
{
    public function __construct(private readonly EmailLogAnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmailLog::class);

        abort_if(! helpdesk_emaillog_enabled(), 404);

        [$from, $to] = $this->resolveWindow($request);

        return view('helpdeskemaillog::emails.analytics', [
            'from' => $from,
            'to' => $to,
            'mailables' => $this->analytics->mailablePerformance($from, $to),
            'latency' => $this->analytics->deliveryLatency($from, $to),
            'domains' => $this->analytics->domainDeliverability($from, $to),
            'thresholds' => [
                'bounce_warning' => (float) Setting::get('helpdeskemaillog.bounce_rate_warning_pct', config('helpdeskemaillog.bounce_rate_warning_pct')),
                'bounce_critical' => (float) Setting::get('helpdeskemaillog.bounce_rate_critical_pct', config('helpdeskemaillog.bounce_rate_critical_pct')),
                'complaint_warning' => (float) Setting::get('helpdeskemaillog.complaint_rate_warning_pct', config('helpdeskemaillog.complaint_rate_warning_pct')),
                'complaint_critical' => (float) Setting::get('helpdeskemaillog.complaint_rate_critical_pct', config('helpdeskemaillog.complaint_rate_critical_pct')),
            ],
        ]);
    }

    /**
     * [from, to] del filtro de la request, o los últimos
     * EmailLogAnalyticsService::DEFAULT_WINDOW_DAYS días si no se pidió
     * ningún rango — mismo criterio de "solo fecha, se asume día completo"
     * que EmailLogController::parseDateFilter()/resolveDeltaPeriods() (no
     * reutilizable desde aquí: son privados de un controlador que esta tarea
     * no puede tocar), reimplementado en miniatura porque es una utilidad
     * genérica de un par de líneas, no una regla de negocio propia del
     * dashboard principal.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveWindow(Request $request): array
    {
        $from = $this->parseDate($request->input('date_from'));
        $to = $this->parseDate($request->input('date_to'));

        $resolvedTo = $to ? $to->copy()->endOfDay() : now();
        $resolvedFrom = $from
            ? $from->copy()->startOfDay()
            : $resolvedTo->copy()->subDays(EmailLogAnalyticsService::DEFAULT_WINDOW_DAYS - 1)->startOfDay();

        return [$resolvedFrom, $resolvedTo];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (Throwable) {
            return null;
        }
    }
}
