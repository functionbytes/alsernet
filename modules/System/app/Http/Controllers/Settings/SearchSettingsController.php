<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class SearchSettingsController extends Controller
{
    /**
     * Display search backups page
     */
    public function index()
    {
        $pageTitle = 'Configuración de búsqueda';
        $breadcrumb = 'Configuración / Búsqueda';

        // Get search backups from database
        $settings = Setting::getSearchSettings();

        return view('theme.views.backups.search.index', compact(
            'pageTitle',
            'breadcrumb',
            'settings'
        ));
    }

    /**
     * Update search backups
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'search_enabled' => 'nullable|boolean',
            'search_driver' => 'required|string',
            'min_search_length' => 'required|integer|min:1|max:10',
            'search_results_per_page' => 'required|integer|min:5|max:100',
            'search_highlight_results' => 'nullable|boolean',
            'search_modules' => 'nullable|array',
        ]);

        try {
            Setting::setSearchSettings($validated);

            return redirect()->route('manager.backups.search.index')
                ->with('success', 'Configuración de búsqueda actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la configuración: '.$e->getMessage())
                ->withInput();
        }
    }
}
