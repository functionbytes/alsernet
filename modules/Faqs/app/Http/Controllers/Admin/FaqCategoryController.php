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
            'pageTitle' => 'Nueva categoría',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function store(StoreFaqCategoryRequest $request): RedirectResponse
    {
        FaqCategory::query()->create($request->validated());

        return redirect()->route('faqs.categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(FaqCategory $category): View
    {
        $this->authorize('faqs.categories.update');

        return view('faqs::admin.categories.edit', [
            'category' => $category,
            'statuses' => FaqStatus::cases(),
            'pageTitle' => 'Editar categoría',
            'breadcrumb' => 'FAQs',
        ]);
    }

    public function update(UpdateFaqCategoryRequest $request, FaqCategory $category): RedirectResponse
    {
        $category->update($request->validated());

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
}
