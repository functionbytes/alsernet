<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Seo\Models\SeoAlert;

class SeoAlertsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function index(): View
    {
        $alerts = SeoAlert::query()
            ->orderByDesc('created_at')
            ->paginate(30);

        $row = SeoAlert::unacknowledged()->selectRaw('
            COUNT(*) as unacknowledged,
            SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as critical,
            SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as warning,
            SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as info
        ', [SeoAlert::SEVERITY_CRITICAL, SeoAlert::SEVERITY_WARNING, SeoAlert::SEVERITY_INFO])->first();

        $stats = [
            'unacknowledged' => (int) $row->unacknowledged,
            'critical' => (int) $row->critical,
            'warning' => (int) $row->warning,
            'info' => (int) $row->info,
        ];

        return view('Seo::settings.alerts.index', compact('alerts', 'stats'));
    }

    public function acknowledge(SeoAlert $alert): RedirectResponse
    {
        $alert->acknowledge(auth()->id());

        return back()->with('success', 'Alerta marcada como revisada.');
    }

    public function acknowledgeAll(): RedirectResponse
    {
        $count = SeoAlert::unacknowledged()->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->id(),
        ]);

        return back()->with('success', "{$count} alertas marcadas como revisadas.");
    }
}
