<?php

namespace Modules\Reviews\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\AI\AiClient;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewScreener;

/**
 * Ajustes del módulo de opiniones.
 */
class ReviewSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reviews.settings');
    }

    public function index(AiClient $ai): View
    {
        return view('reviews::settings.index', [
            'enabled' => ReviewScreener::isEnabled(),
            'flags' => ReviewScreener::watchedFlags(),
            'labels' => ReviewScreener::LABELS,
            'autoReject' => (bool) Setting::get('reviews.screening.auto_reject', false),
            'aiReady' => $ai->isEnabled(),
            'stats' => [
                'pendientes' => Review::pending()->count(),
                'cribadas' => Review::whereNotNull('screened_at')->count(),
                'marcadas' => Review::whereNotNull('screening')
                    ->where('screening', '!=', ReviewScreener::CLEAN)->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'flags' => ['nullable', 'array'],
            'flags.*' => ['string', 'in:'.implode(',', array_keys(ReviewScreener::LABELS))],
        ]);

        Setting::set('reviews.screening.enabled', $request->boolean('enabled'), 'reviews');
        Setting::set('reviews.screening.auto_reject', $request->boolean('auto_reject'), 'reviews');
        Setting::set('reviews.screening.flags', json_encode($datos['flags'] ?? []), 'reviews');

        return back()->with('success', $request->boolean('enabled')
            ? 'Revisión asistida activada.'
            : 'Revisión asistida desactivada. Nada más cambia: las opiniones siguen llegando y moderándose igual.');
    }
}
