<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreCompanyRequest;
use Modules\Helpdesk\Http\Requests\Settings\UpdateCompanyRequest;
use Modules\Helpdesk\Models\Company;

class CompaniesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.companies.view')->only(['index']);
        $this->middleware('can:helpdesk.companies.manage')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request): View
    {
        $query = Company::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $companies = $query->withCount('customers')->latest()->paginate(20);

        $stats = [
            'total' => Company::count(),
            'healthy' => Company::where('health_score', '>=', 80)->count(),
            'at_risk' => Company::where('health_score', '<', 50)->whereNotNull('health_score')->count(),
            'with_customers' => Company::has('customers')->count(),
        ];

        return view('helpdesk::settings.companies.index', compact('companies', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.companies.create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        return redirect()
            ->route('settings.helpdesk.companies.index')
            ->with('success', 'Empresa creada exitosamente.');
    }

    public function edit(Company $company): View
    {
        return view('helpdesk::settings.companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return redirect()
            ->route('settings.helpdesk.companies.index')
            ->with('success', 'Empresa actualizada exitosamente.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()
            ->route('settings.helpdesk.companies.index')
            ->with('success', 'Empresa eliminada exitosamente.');
    }
}
