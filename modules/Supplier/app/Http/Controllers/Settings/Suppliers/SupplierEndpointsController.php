<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Supplier\Support\ErpEndpointGuard;
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

        // 29-sep-2026: solo hosts del ERP (lista blanca); antes se podía desviar
        // la escritura de contenido del ERP a cualquier servidor.
        $erpHost = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== null && $value !== '' && ! ErpEndpointGuard::isAllowed((string) $value)) {
                $fail('El host de :attribute no está en la lista de hosts permitidos del ERP ('.implode(', ', ErpEndpointGuard::allowedHosts()).').');
            }
        };

        $validated = $request->validate([
            'erp_modelo_url' => ['nullable', 'url:http,https', 'max:500', $erpHost],
            'erp_internal_url' => ['nullable', 'url:http,https', 'max:500', $erpHost],
            'erp_caracteristica_url' => ['nullable', 'url:http,https', 'max:500', $erpHost],
        ]);

        try {
            Setting::set('supplier.erp_modelo_url', $validated['erp_modelo_url'] ?? '');
            Setting::set('supplier.erp_internal_url', $validated['erp_internal_url'] ?? '');
            Setting::set('supplier.erp_caracteristica_url', $validated['erp_caracteristica_url'] ?? '');

            return redirect()->back()->with('success', 'Configuración de endpoints actualizada correctamente.');
        } catch (\Exception $e) {
            report($e);

            return back()->withInput()->with('error', 'Error al guardar la configuración de endpoints.');
        }
    }

    public function test(Request $request): JsonResponse
    {
        $this->authorize('suppliers.sync.config');

        $request->validate(['url' => ['required', 'url:http,https', 'max:500']]);

        $url = $request->input('url');

        // 29-sep-2026: solo se prueban hosts del ERP (evita usar esto para
        // escanear o leer servicios internos).
        if (! ErpEndpointGuard::isAllowed($url)) {
            return response()->json([
                'success' => false,
                'message' => 'El host no está en la lista de hosts permitidos del ERP.',
            ], 422);
        }

        $body = array_filter([
            'idmodelo' => $request->input('idmodelo'),
            'nombre' => $request->input('nombre') ?: null,
            'descripcion' => $request->input('descripcion') ?: null,
            'publicar' => $request->has('publicar') ? (int) $request->input('publicar') : null,
            'marca' => $request->input('marca') ?: null,
        ], fn ($v) => $v !== null);

        try {
            // Sin seguir redirecciones y sin devolver el cuerpo: basta el código HTTP.
            $response = Http::timeout(5)->withoutRedirecting()->asForm()->post($url, $body);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'sent' => $body,
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con el endpoint.',
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
