<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class SupplierAiConfigController extends Controller
{
    public function index(): View
    {
        $this->authorize('suppliers.sync.config');

        return view('supplier::settings.views.ai-config.index', [
            'config' => $this->getConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('suppliers.sync.config');

        $validated = $request->validate([
            'openai_api_key'    => 'nullable|string|max:500',
            'anthropic_api_key' => 'nullable|string|max:500',
            'google_api_key'    => 'nullable|string|max:500',
            'default_model'     => 'nullable|string|max:100',
        ]);

        try {
            foreach ([
                'openai_api_key'    => 'supplier.openai_api_key',
                'anthropic_api_key' => 'supplier.anthropic_api_key',
                'google_api_key'    => 'supplier.google_api_key',
            ] as $field => $settingKey) {
                if ($request->filled($field)) {
                    Setting::set($settingKey, encrypt($validated[$field]));
                } elseif ($request->has($field)) {
                    // Field submitted empty → remove DB override, fall back to .env
                    Setting::where('key', $settingKey)->delete();
                }
            }
            Setting::set('supplier.ai_default_model', $validated['default_model'] ?? 'gpt-4o-mini');

            return redirect()->back()->with('success', 'Configuración de IA actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al guardar: '.$e->getMessage());
        }
    }

    public function test(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('suppliers.sync.config');

        $provider = $request->input('provider', 'openai');
        $apiKey = $request->input('api_key');

        if (! in_array($provider, ['openai', 'anthropic', 'google'], true)) {
            return response()->json(['success' => false, 'message' => 'Proveedor no válido.'], 422);
        }

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Debes proporcionar una API key para probar.',
            ]);
        }

        try {
            if ($provider === 'openai') {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                ])->timeout(10)->get('https://api.openai.com/v1/models');
            } elseif ($provider === 'google') {
                $response = Http::timeout(10)->get(
                    "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}"
                );
            } else {
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])->timeout(10)->get('https://api.anthropic.com/v1/models');
            }

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful()
                    ? 'Conexión exitosa con '.ucfirst($provider)
                    : 'Error de autenticación: '.$response->body(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ]);
        }
    }

    private function getConfig(): array
    {
        return [
            'openai_api_key'    => self::decryptApiKey(Setting::get('supplier.openai_api_key', '')) ?: config('services.openai.api_key', ''),
            'anthropic_api_key' => self::decryptApiKey(Setting::get('supplier.anthropic_api_key', '')) ?: config('services.anthropic.api_key', ''),
            'google_api_key'    => self::decryptApiKey(Setting::get('supplier.google_api_key', '')) ?: config('services.google.api_key', ''),
            'default_model'     => Setting::get('supplier.ai_default_model', config('supplier.ai.default_model', 'gpt-4o-mini')),
        ];
    }

    public static function decryptApiKey(?string $value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return decrypt($value);
        } catch (\Exception) {
            return $value; // backward compat: valor guardado sin cifrar
        }
    }
}
