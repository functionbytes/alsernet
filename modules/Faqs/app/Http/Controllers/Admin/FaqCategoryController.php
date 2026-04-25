<?php

namespace Modules\Faqs\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Faqs\Enums\FaqStatus;
use Modules\Faqs\Http\Requests\StoreFaqCategoryRequest;
use Modules\Faqs\Http\Requests\UpdateFaqCategoryRequest;
use Modules\Faqs\Models\FaqCategory;
use Modules\Locales\Models\Locale;

class FaqCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('faqs.categories.view');

        $query = FaqCategory::query()->withCount('faqs')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->paginate(20);
        $statuses = FaqStatus::cases();

        return view('faqs::admin.categories.index', [
            'categories' => $categories,
            'statuses' => $statuses,
            'pageTitle' => 'Categorías FAQ',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function create(): View
    {
        $this->authorize('faqs.categories.create');

        return view('faqs::admin.categories.create', [
            'statuses' => FaqStatus::cases(),
            'locales' => $this->getActiveLocales(),
            'pageTitle' => 'Nueva categoría',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function store(StoreFaqCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $translations = $data['translations'];
        unset($data['translations']);

        $defaultLocale = $this->getDefaultLocale();
        $defaultTranslation = collect($translations)->firstWhere('locale', $defaultLocale)
            ?? $translations[array_key_first($translations)];
        $data['name'] = $defaultTranslation['name'];
        $data['description'] = $defaultTranslation['description'] ?? null;

        $category = FaqCategory::query()->create($data);
        $this->syncTranslations($category, $translations);

        return redirect()->route('faqs.categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(FaqCategory $category): View
    {
        $this->authorize('faqs.categories.update');

        return view('faqs::admin.categories.edit', [
            'category' => $category->load('translations'),
            'statuses' => FaqStatus::cases(),
            'locales' => $this->getActiveLocales(),
            'pageTitle' => 'Editar categoría',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function update(UpdateFaqCategoryRequest $request, FaqCategory $category): RedirectResponse
    {
        $data = $request->validated();
        $translations = $data['translations'];
        unset($data['translations']);

        $defaultLocale = $this->getDefaultLocale();
        $defaultTranslation = collect($translations)->firstWhere('locale', $defaultLocale)
            ?? $translations[array_key_first($translations)];
        $data['name'] = $defaultTranslation['name'];
        $data['description'] = $defaultTranslation['description'] ?? null;

        $category->update($data);
        $this->syncTranslations($category, $translations);

        return redirect()->route('faqs.categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(FaqCategory $category): RedirectResponse
    {
        $this->authorize('faqs.categories.delete');

        $category->delete();

        return redirect()->route('faqs.categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
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
    private function syncTranslations(FaqCategory $category, array $translations): void
    {
        foreach ($translations as $translation) {
            $category->translations()->updateOrCreate(
                ['locale' => $translation['locale']],
                [
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null,
                ]
            );
        }
    }
}
