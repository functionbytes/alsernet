<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\ProductQuestion;

class ProductQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $questions = ProductQuestion::query()
            ->with('product')
            ->when($request->input('status') === 'pending', fn ($q) => $q->whereNull('answer'))
            ->when($request->input('status') === 'answered', fn ($q) => $q->whereNotNull('answer'))
            ->latest()
            ->paginate(20);

        return view('ecommerce::product-questions.index', compact('questions'));
    }

    public function answer(Request $request, ProductQuestion $question): RedirectResponse
    {
        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:2000'],
        ]);

        $question->update([
            'answer' => $validated['answer'],
            'answered_by' => auth()->user()?->name ?? 'Equipo',
            'answered_at' => now(),
            'is_published' => true,
        ]);

        return back()->with('success', 'Respuesta publicada.');
    }

    public function destroy(ProductQuestion $question): RedirectResponse
    {
        $question->delete();

        return back()->with('success', 'Pregunta eliminada.');
    }
}
