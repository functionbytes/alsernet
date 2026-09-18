<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\SubmitSurveyRequest;
use Modules\Helpdesk\Models\SurveyResponse;

class SurveyController extends Controller
{
    public function show(string $token): View
    {
        $response = SurveyResponse::query()
            ->where('survey_token', $token)
            ->with('survey')
            ->firstOrFail();

        if ($response->answered_at) {
            return view('helpdesk::public.survey-thanks', compact('response'));
        }

        if ($response->is_expired) {
            return view('helpdesk::public.survey-expired', compact('response'));
        }

        return view('helpdesk::public.survey', compact('response'));
    }

    public function submit(SubmitSurveyRequest $request, string $token): RedirectResponse
    {
        $response = SurveyResponse::query()
            ->where('survey_token', $token)
            ->with('survey')
            ->firstOrFail();

        if ($response->answered_at || $response->is_expired) {
            return redirect()->route('helpdesk.survey.show', $token);
        }

        $response->update([
            'answers' => $request->validated()['answers'],
            'answered_at' => now(),
        ]);

        return redirect()->route('helpdesk.survey.show', $token)
            ->with('success', 'Gracias por completar la encuesta.');
    }
}
