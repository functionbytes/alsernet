<?php

namespace Modules\Questions\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Questions\Models\Question;
use Modules\Questions\Services\QuestionModerator;

/**
 * Bandeja de consultas.
 *
 * Ordenada por lo que de verdad falta: primero las que nadie ha contestado,
 * porque una consulta sin respuesta no puede publicarse y es la que deja al
 * cliente esperando.
 */
class QuestionsManagerController extends Controller
{
    public function __construct(private readonly QuestionModerator $moderator)
    {
        $this->middleware('can:questions.moderate')->only(['approve', 'reject', 'answer', 'bulk']);
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'unanswered');
        $search = trim((string) $request->query('q', ''));

        $questions = Question::query()
            ->when($status === 'unanswered', fn ($q) => $q->unanswered()->where('status', '!=', Question::STATUS_REJECTED))
            ->when($status === 'answered_pending', fn ($q) => $q->answered()->pending())
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->when($status === 'conflict', fn ($q) => $q->inConflict())
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%");
            }))
            ->when($request->filled('lang'), fn ($q) => $q->where('lang_iso', $request->query('lang')))
            ->latest('ps_date')
            ->paginate(25)
            ->withQueryString();

        return view('questions::manage.index', [
            'questions' => $questions,
            'status' => $status,
            'search' => $search,
            'counts' => $this->counts(),
            'languages' => array_keys((array) config('questions.languages', [])),
        ]);
    }

    public function show(Question $question): View
    {
        $question->load(['translations', 'events']);

        $idiomas = (array) config('questions.languages', []);
        $porIdioma = $question->translations->keyBy('lang_iso');
        $filas = [];

        foreach ($idiomas as $iso => $psLangId) {
            if ((int) $psLangId === (int) $question->ps_lang_id) {
                continue;
            }

            $filas[] = ['iso' => $iso, 'ps_lang_id' => $psLangId, 'translation' => $porIdioma->get($iso)];
        }

        return view('questions::manage.show', ['question' => $question, 'rows' => $filas]);
    }

    public function answer(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:4000'],
        ]);

        $resultado = $this->moderator->answer($question, $validated['answer'], $request->boolean('publish'));

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function approve(Question $question): RedirectResponse
    {
        $resultado = $this->moderator->approve($question);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function reject(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $resultado = $this->moderator->reject($question, $validated['reason'] ?? null);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $hechas = 0;
        $sinRespuesta = 0;
        $fallidas = 0;

        foreach (Question::whereIn('id', $validated['ids'])->get() as $question) {
            if ($validated['action'] === 'approve' && ! $question->isAnswered()) {
                $sinRespuesta++;

                continue;
            }

            $r = $validated['action'] === 'approve'
                ? $this->moderator->approve($question)
                : $this->moderator->reject($question);

            $r['ok'] ? $hechas++ : $fallidas++;
        }

        $mensaje = $hechas.' '.($validated['action'] === 'approve' ? 'publicadas' : 'retiradas');

        if ($sinRespuesta) {
            $mensaje .= '. '.$sinRespuesta.' se saltaron por no tener respuesta';
        }

        if ($fallidas) {
            return back()->with('warning', $mensaje.'. '.$fallidas.' no llegaron a la tienda.');
        }

        return back()->with('success', $mensaje.'.');
    }

    private function counts(): array
    {
        return [
            'unanswered' => Question::unanswered()->where('status', '!=', Question::STATUS_REJECTED)->count(),
            'answered_pending' => Question::answered()->pending()->count(),
            'approved' => Question::where('status', Question::STATUS_APPROVED)->count(),
            'rejected' => Question::where('status', Question::STATUS_REJECTED)->count(),
            'conflict' => Question::inConflict()->count(),
        ];
    }
}
