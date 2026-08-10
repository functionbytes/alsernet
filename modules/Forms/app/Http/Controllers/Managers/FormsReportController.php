<?php

namespace Modules\Forms\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Forms\Models\Form;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;

/**
 * Panel de gestión: qué formularios de alsernetforms existen (tabla
 * helpdesk_forms, gestionable en panel/forms/manage) y qué tickets ha
 * generado cada uno. No reimplementa el listado de tickets: cada fila
 * enlaza al listado real de HelpdeskTickets ya filtrado por categoría +
 * source=formulario.
 */
class FormsReportController extends Controller
{
    private const TREND_DAYS = 14;

    public function index(): View
    {
        $forms = Form::with('category')->orderBy('name')->get();

        $categoryIds = $forms->pluck('category_id')->filter()->unique();

        $totalsByCategory = Ticket::where('source', 'formulario')
            ->whereIn('category_id', $categoryIds)
            ->selectRaw('category_id, count(*) as total, max(created_at) as last_submitted_at')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $openStatusIds = TicketStatus::open()->pluck('id');

        $openByCategory = Ticket::where('source', 'formulario')
            ->whereIn('category_id', $categoryIds)
            ->whereIn('status_id', $openStatusIds)
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $rows = $forms->map(function (Form $form) use ($totalsByCategory, $openByCategory) {
            $stat = $form->category_id ? $totalsByCategory->get($form->category_id) : null;

            return [
                'form' => $form,
                'category' => $form->category,
                'total' => $stat->total ?? 0,
                'open' => $form->category_id ? $openByCategory->get($form->category_id, 0) : 0,
                'last_submitted_at' => $stat?->last_submitted_at,
            ];
        });

        return view('forms::report.index', [
            'rows' => $rows,
            'totalTickets' => $rows->sum('total'),
            'totalOpen' => $rows->sum('open'),
            'formsEnabled' => helpdesk_forms_enabled(),
            'trend' => $this->buildTrend($categoryIds),
        ]);
    }

    /**
     * Serie diaria de los últimos self::TREND_DAYS días (todos los
     * formularios agregados) + comparación contra el período anterior de
     * igual longitud, mismo espíritu que FormsDashboardController::resolveDates()
     * del módulo Forms de "system" pero sin selector de rango (fijo a 14 días).
     *
     * @return array{labels: array<int, string>, series: array<int, int>, current_total: int, previous_total: int, change_percent: float}
     */
    private function buildTrend(Collection $categoryIds): array
    {
        $days = self::TREND_DAYS;
        $start = now()->subDays($days - 1)->startOfDay();
        $prevStart = now()->subDays(($days * 2) - 1)->startOfDay();
        $prevEnd = now()->subDays($days)->endOfDay();

        $countsByDay = Ticket::where('source', 'formulario')
            ->whereIn('category_id', $categoryIds)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $series = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $series[] = (int) ($countsByDay->get($date->format('Y-m-d')) ?? 0);
        }

        $currentTotal = array_sum($series);

        $previousTotal = Ticket::where('source', 'formulario')
            ->whereIn('category_id', $categoryIds)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->count();

        $changePercent = match (true) {
            $previousTotal > 0 => round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1),
            $currentTotal > 0 => 100.0,
            default => 0.0,
        };

        return [
            'labels' => $labels,
            'series' => $series,
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'change_percent' => $changePercent,
        ];
    }
}
