<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\SubmitCsatRequest;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Services\CsatService;

class CsatController extends Controller
{
    public function __construct(
        private readonly CsatService $csatService,
    ) {}

    public function show(string $token): View
    {
        $survey = CsatRating::query()
            ->where('survey_token', $token)
            ->firstOrFail();

        if ($survey->answered_at !== null) {
            return view('helpdesk::public.csat-thanks', compact('survey'));
        }

        if ($survey->expires_at && $survey->expires_at->isPast()) {
            return view('helpdesk::public.csat-expired', compact('survey'));
        }

        return view('helpdesk::public.csat-form', compact('survey'));
    }

    public function submit(SubmitCsatRequest $request, string $token): RedirectResponse
    {
        $validated = $request->validated();

        $survey = $this->csatService->answer(
            $token,
            (int) $validated['rating'],
            $validated['comment'] ?? null,
        );

        if ($survey === null) {
            return redirect()
                ->route('helpdesk.csat.show', $token)
                ->with('error', 'Esta encuesta ya fue respondida o ha expirado.');
        }

        return redirect()
            ->route('helpdesk.csat.show', $token)
            ->with('success', '¡Gracias por tu calificación!');
    }
}
