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

        $stats = [
            'unacknowledged' => SeoAlert::unacknowledged()->count(),
            'critical' => SeoAlert::unacknowledged()->ofSeverity(SeoAlert::SEVERITY_CRITICAL)->count(),
            'warning' => SeoAlert::unacknowledged()->ofSeverity(SeoAlert::SEVERITY_WARNING)->count(),
            'info' => SeoAlert::unacknowledged()->ofSeverity(SeoAlert::SEVERITY_INFO)->count(),
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
