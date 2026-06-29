<?php

namespace Modules\Reviews\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewAiSetting;
use Modules\Reviews\Services\AiReplyService;

class AiSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reviews.settings.manage');
    }

    public function index(): View
    {
        $aiSettings = ReviewAiSetting::getInstance();

        return view('reviews::settings.ai', compact('aiSettings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:anthropic,openai',
            'api_key' => 'nullable|string',
            'model' => 'required|string',
            'is_enabled' => 'boolean',
            'tone' => 'required|in:professional,friendly,formal,casual',
            'language' => 'required|in:es,en,pt,fr',
            'max_tokens' => 'required|integer|min:100|max:2000',
            'custom_instructions' => 'nullable|string|max:500',
        ]);

        $aiSettings = ReviewAiSetting::firstOrNew([]);

        $shouldUpdateApiKey = filled($validated['api_key'])
            && ! str_contains($validated['api_key'], '•');

        if (! $shouldUpdateApiKey) {
            if (! $aiSettings->exists) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'La API key es requerida para guardar la configuración por primera vez.');
            }
            unset($validated['api_key']);
        }

        $validated['is_enabled'] = $request->boolean('is_enabled');

        $aiSettings->fill($validated)->save();

        activity()
            ->causedBy(auth()->user())
            ->log('AI reply settings updated');

        return redirect()
            ->route('settings.reviews.ai.index')
            ->with('success', 'Configuración de IA actualizada correctamente');
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|in:anthropic,openai',
            'api_key' => 'required|string',
            'model' => 'required|string',
        ]);

        $tempSettings = new ReviewAiSetting([
            'provider' => $request->input('provider'),
            'api_key' => $request->input('api_key'),
            'model' => $request->input('model'),
        ]);

        $service = new AiReplyService;
        $success = $service->testConnection($tempSettings);

        return response()->json([
            'success' => $success,
            'message' => $success
                ? 'Conexión exitosa. La API key es válida.'
                : 'No se pudo conectar. Verifica la API key y el modelo seleccionado.',
        ]);
    }
}
