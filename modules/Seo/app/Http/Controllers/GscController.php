<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Seo\Services\GoogleSearchConsoleService;

/**
 * Admin UI for Google Search Console OAuth connection and data import.
 */
class GscController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function index(GoogleSearchConsoleService $service): View
    {
        $status = [
            'configured' => $service->isConfigured(),
            'connected' => $service->isConnected(),
            'property_url' => (string) seo_setting('gsc.property_url', config('Seo.gsc.property_url', config('app.url'))),
        ];

        return view('Seo::settings.gsc.index', compact('status'));
    }

    public function connect(GoogleSearchConsoleService $service): RedirectResponse
    {
        if (! $service->isConfigured()) {
            return redirect()->route('settings.seo.gsc.index')
                ->with('error', 'Configura client_id y client_secret en /panel/setting/seo/settings antes de conectar.');
        }

        return redirect()->away($service->authorizationUrl(csrf_token()));
    }

    public function callback(Request $request, GoogleSearchConsoleService $service): RedirectResponse
    {
        if (! $request->filled('code')) {
            return redirect()->route('settings.seo.gsc.index')
                ->with('error', 'Google no devolvió un código de autorización.');
        }

        $ok = $service->handleCallback((string) $request->input('code'));

        return redirect()->route('settings.seo.gsc.index')
            ->with($ok ? 'success' : 'error', $ok
                ? 'Conectado a Google Search Console.'
                : 'No se pudo canjear el código por un refresh token.');
    }

    public function disconnect(GoogleSearchConsoleService $service): RedirectResponse
    {
        $service->disconnect();

        return redirect()->route('settings.seo.gsc.index')
            ->with('success', 'Desconectado de Google Search Console.');
    }

    public function import(Request $request, GoogleSearchConsoleService $service): RedirectResponse
    {
        $days = (int) $request->input('days', 28);
        $days = max(1, min(90, $days));

        $updated = $service->importIntoMetas($days);

        return redirect()->route('settings.seo.gsc.index')
            ->with('success', "Importadas {$updated} URLs desde Search Console.");
    }
}
