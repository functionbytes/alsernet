<?php

namespace Modules\Helpdesk\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Services\CsatService;

class CsatController extends Controller
{
    public function __construct(private readonly CsatService $service) {}

    public function show(string $token): View|RedirectResponse
    {
        $survey = CsatRating::query()->where('survey_token', $token)->first();

        if (! $survey) {
            return redirect('/')->with('error', 'Esta encuesta no existe.');
        }

        return view('helpdesk::pages.csat-form', [
            'survey' => $survey,
            'token' => $token,
            'alreadyAnswered' => $survey->answered_at !== null,
            'expired' => $survey->expires_at && $survey->expires_at->isPast(),
        ]);
    }

    public function submit(Request $request, string $token): View|RedirectResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $survey = $this->service->answer($token, (int) $validated['rating'], $validated['comment'] ?? null);

        if (! $survey) {
            return redirect()->route('helpdesk.csat.show', ['token' => $token])
                ->with('error', 'No se pudo registrar tu respuesta.');
        }

        return view('helpdesk::pages.csat-thanks', [
            'rating' => $survey->rating,
        ]);
    }
}
