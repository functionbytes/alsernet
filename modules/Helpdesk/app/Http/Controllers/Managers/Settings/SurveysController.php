<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreSurveyRequest;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateSurveyRequest;
use Modules\Helpdesk\Models\Survey;
use Modules\Helpdesk\Models\SurveyResponse;

class SurveysController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.surveys.manage');
    }

    public function index(Request $request): View
    {
        $surveys = Survey::query()
            ->withCount('responses')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => Survey::count(),
            'active' => Survey::where('is_active', true)->count(),
            'responses' => SurveyResponse::whereNotNull('answered_at')->count(),
        ];

        return view('helpdesk::settings.surveys.index', compact('surveys', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.surveys.form', [
            'triggerTypes' => Survey::TRIGGER_TYPES,
            'questionTypes' => Survey::QUESTION_TYPES,
        ]);
    }

    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Survey::create($data);

        return redirect()->route('settings.helpdesk.surveys.index')
            ->with('success', 'Encuesta creada exitosamente.');
    }

    public function edit(Survey $survey): View
    {
        return view('helpdesk::settings.surveys.form', [
            'survey' => $survey,
            'triggerTypes' => Survey::TRIGGER_TYPES,
            'questionTypes' => Survey::QUESTION_TYPES,
        ]);
    }

    public function update(UpdateSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $survey->update($data);

        return redirect()->route('settings.helpdesk.surveys.index')
            ->with('success', 'Encuesta actualizada exitosamente.');
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $survey->delete();

        return redirect()->route('settings.helpdesk.surveys.index')
            ->with('success', 'Encuesta eliminada exitosamente.');
    }

    public function responses(Survey $survey, Request $request): View
    {
        $responses = SurveyResponse::query()
            ->where('survey_id', $survey->id)
            ->with('customer')
            ->whereNotNull('answered_at')
            ->latest('answered_at')
            ->paginate(20);

        return view('helpdesk::settings.surveys.responses', compact('survey', 'responses'));
    }
}
