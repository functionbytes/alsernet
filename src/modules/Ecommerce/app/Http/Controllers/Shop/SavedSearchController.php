<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\SavedSearch;

class SavedSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:ecommerce');
    }

    public function index(): View
    {
        $searches = SavedSearch::query()
            ->where('customer_id', auth('ecommerce')->id())
            ->latest()
            ->get();

        return view('ecommerce::shop.account.saved-searches', compact('searches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:255'],
            'filters' => ['nullable', 'array'],
        ]);

        SavedSearch::query()->create([
            'customer_id' => auth('ecommerce')->id(),
            'name' => $validated['name'],
            'query' => $validated['query'] ?? null,
            'filters' => $validated['filters'] ?? [],
            'is_active' => true,
        ]);

        return back()->with('success', 'Busqueda guardada. Te avisaremos cuando aparezcan nuevos productos.');
    }

    public function toggle(SavedSearch $savedSearch): RedirectResponse
    {
        abort_if($savedSearch->customer_id !== auth('ecommerce')->id(), 403);

        $savedSearch->update(['is_active' => ! $savedSearch->is_active]);

        $message = $savedSearch->is_active ? 'Notificaciones activadas.' : 'Notificaciones pausadas.';

        return back()->with('success', $message);
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        abort_if($savedSearch->customer_id !== auth('ecommerce')->id(), 403);

        $savedSearch->delete();

        return back()->with('success', 'Busqueda eliminada.');
    }
}
