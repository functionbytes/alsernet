<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Supplier\Traits\ValidatesPublicUrl;

class SupplierEndpointsController extends Controller
{
    use ValidatesPublicUrl;

    public function index(): View
    {
        $this->authorize('suppliers.sync.config');

        return view('supplier::settings.views.endpoints.index', [
            'endpoints' => $this->getEndpoints(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('suppliers.sync.config');

        $validated = $request->validate([
            'erp_modelo_url' => 'nullable|url|max:500',
            'erp_internal_url' => 'nullable|url|max:500',
            'erp_caracteristica_url' => 'nullable|url|max:500',
        ]);

        try {
            Setting::set('supplier.erp_modelo_url', $validated['erp_modelo_url'] ?? '');
            Setting::set('supplier.erp_internal_url', $validated['erp_internal_url'] ?? '');
            Setting::set('supplier.erp_caracteristica_url', $validated['erp_caracteristica_url'] ?? '');

            return redirect()->back()->with('success', 'Configuración de endpoints actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al guardar: '.$e->getMessage());
        }
    }

    public function test(Request $request): JsonResponse
    {
        $this->authorize('suppliers.sync.config');

        $request->validate(['url' => ['required', 'url:http,https', 'max:500']]);

        $url = $request->input('url');

        $body = array_filter([
            'idmodelo' => $request->input('idmodelo'),
            'nombre' => $request->input('nombre') ?: null,
            'descripcion' => $request->input('descripcion') ?: null,
            'publicar' => $request->has('publicar') ? (int) $request->input('publicar') : null,
            'marca' => $request->input('marca') ?: null,
        ], fn ($v) => $v !== null);

        try {
            $response = Http::timeout(5)->asForm()->post($url, $body);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'sent' => $body,
                'body' => Str::limit($response->body(), 300),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'sent' => $body,
            ]);
        }
    }

    private function getEndpoints(): array
    {
        return [
            'erp_modelo_url' => Setting::get('supplier.erp_modelo_url', 'http://interges:8080/api-gestion/modelo/'),
            'erp_internal_url' => Setting::get('supplier.erp_internal_url', config('supplier.erp_internal_url', 'http://nginx')),
            'erp_caracteristica_url' => Setting::get('supplier.erp_caracteristica_url', 'http://interges:8080/api-gestion/asignar-caracteristica/'),
        ];
    }
}
