<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreLegalPageRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateLegalPageRequest;
use Modules\Ecommerce\Models\LegalPage;

class LegalPageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LegalPage::class);

        $legalPages = LegalPage::query()->latest()->paginate(20);

        return view('ecommerce::legal-pages.index', compact('legalPages'));
    }

    public function create(): View
    {
        $this->authorize('create', LegalPage::class);

        return view('ecommerce::legal-pages.create');
    }

    public function store(StoreLegalPageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_published'] = $request->has('is_published');

        LegalPage::query()->create($data);

        return redirect()->route('ecommerce.legal-pages.index')->with('success', 'Página legal creada exitosamente.');
    }

    public function edit(LegalPage $legalPage): View
    {
        $this->authorize('update', $legalPage);

        return view('ecommerce::legal-pages.edit', compact('legalPage'));
    }

    public function update(UpdateLegalPageRequest $request, LegalPage $legalPage): RedirectResponse
    {
        $data = $request->validated();
        $data['is_published'] = $request->has('is_published');

        $legalPage->update($data);

        return redirect()->route('ecommerce.legal-pages.index')->with('success', 'Página legal actualizada exitosamente.');
    }

    public function destroy(LegalPage $legalPage): RedirectResponse
    {
        $this->authorize('delete', $legalPage);

        $legalPage->delete();

        return redirect()->route('ecommerce.legal-pages.index')->with('success', 'Página legal eliminada exitosamente.');
    }
}
