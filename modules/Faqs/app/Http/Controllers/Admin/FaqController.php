<?php

namespace Modules\Faqs\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Faqs\Enums\FaqStatus;
use Modules\Faqs\Http\Requests\StoreFaqRequest;
use Modules\Faqs\Http\Requests\UpdateFaqRequest;
use Modules\Faqs\Models\Faq;
use Modules\Faqs\Models\FaqCategory;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('faqs.view');

        $query = Faq::query()->with('category')->latest();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->paginate(20);
        $categories = FaqCategory::query()->orderBy('name')->get();
        $statuses = FaqStatus::cases();

        return view('faqs::admin.faqs.index', [
            'faqs' => $faqs,
            'categories' => $categories,
            'statuses' => $statuses,
            'pageTitle' => 'Preguntas frecuentes',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function create(): View
    {
        $this->authorize('faqs.create');

        return view('faqs::admin.faqs.create', [
            'categories' => FaqCategory::query()->orderBy('name')->get(),
            'statuses' => FaqStatus::cases(),
            'pageTitle' => 'Nueva pregunta',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        Faq::query()->create($request->validated());

        return redirect()->route('faqs.index')
            ->with('success', 'Pregunta creada correctamente.');
    }

    public function edit(Faq $faq): View
    {
        $this->authorize('faqs.update');

        return view('faqs::admin.faqs.edit', [
            'faq' => $faq,
            'categories' => FaqCategory::query()->orderBy('name')->get(),
            'statuses' => FaqStatus::cases(),
            'pageTitle' => 'Editar pregunta',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return redirect()->route('faqs.index')
            ->with('success', 'Pregunta actualizada correctamente.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->authorize('faqs.delete');

        $faq->delete();

        return redirect()->route('faqs.index')
            ->with('success', 'Pregunta eliminada correctamente.');
    }
}
