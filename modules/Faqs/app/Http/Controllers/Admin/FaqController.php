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
use Modules\Locales\Models\Locale;

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
            'categories' => FaqCategory::query()->with('translations')->orderBy('name')->get(),
            'statuses' => FaqStatus::cases(),
            'locales' => $this->getActiveLocales(),
            'pageTitle' => 'Nueva pregunta',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $translations = $data['translations'];
        unset($data['translations']);

        $defaultLocale = $this->getDefaultLocale();
        $defaultTranslation = collect($translations)->firstWhere('locale', $defaultLocale)
            ?? $translations[array_key_first($translations)];
        $data['question'] = $defaultTranslation['question'];
        $data['answer'] = $defaultTranslation['answer'];

        $faq = Faq::query()->create($data);
        $this->syncTranslations($faq, $translations);

        return redirect()->route('faqs.index')
            ->with('success', 'Pregunta creada correctamente.');
    }

    public function edit(Faq $faq): View
    {
        $this->authorize('faqs.update');

        return view('faqs::admin.faqs.edit', [
            'faq' => $faq->load('translations'),
            'categories' => FaqCategory::query()->with('translations')->orderBy('name')->get(),
            'statuses' => FaqStatus::cases(),
            'locales' => $this->getActiveLocales(),
            'pageTitle' => 'Editar pregunta',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $data = $request->validated();
        $translations = $data['translations'];
        unset($data['translations']);

        $defaultLocale = $this->getDefaultLocale();
        $defaultTranslation = collect($translations)->firstWhere('locale', $defaultLocale)
            ?? $translations[array_key_first($translations)];
        $data['question'] = $defaultTranslation['question'];
        $data['answer'] = $defaultTranslation['answer'];

        $faq->update($data);
        $this->syncTranslations($faq, $translations);

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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActiveLocales(): array
    {
        try {
            return Locale::active()->get(['code', 'name', 'native_name', 'is_default'])->toArray();
        } catch (\Throwable) {
            return [['code' => config('app.locale', 'es'), 'name' => 'Default', 'native_name' => 'Default', 'is_default' => true]];
        }
    }

    private function getDefaultLocale(): string
    {
        try {
            return Locale::active()->firstWhere('is_default', true)?->code ?? config('app.locale', 'es');
        } catch (\Throwable) {
            return config('app.locale', 'es');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $translations
     */
    private function syncTranslations(Faq $faq, array $translations): void
    {
        foreach ($translations as $translation) {
            $faq->translations()->updateOrCreate(
                ['locale' => $translation['locale']],
                [
                    'question' => $translation['question'],
                    'answer' => $translation['answer'],
                ]
            );
        }
    }
}
