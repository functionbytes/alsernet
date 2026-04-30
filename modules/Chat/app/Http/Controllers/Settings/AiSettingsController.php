<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Settings\HelpdeskSettingsRepository;

class AiSettingsController extends Controller
{
    public function __construct(
        private readonly HelpdeskSettingsRepository $settings,
    ) {}

    public function index(): View
    {
        $settings = $this->settings->get('chat.ai', [
            'llm_provider' => 'openai',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o',
            'anthropic_api_key' => '',
            'anthropic_model' => 'claude-opus-4-5-20251101',
            'gemini_api_key' => '',
            'gemini_model' => 'gemini-2.0-flash',
            'embeddings_provider' => 'openai',
            'enable_embeddings' => true,
            'enable_rag' => false,
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'top_p' => 1.0,
        ]);

        $providers = [
            'openai' => 'OpenAI (GPT-4o)',
            'anthropic' => 'Anthropic (Claude)',
            'gemini' => 'Google Gemini',
        ];

        return view('Chat::settings.ai', compact('settings', 'providers'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'llm_provider' => 'required|in:openai,anthropic,gemini',
            'openai_api_key' => 'nullable|string',
            'openai_model' => 'nullable|string',
            'anthropic_api_key' => 'nullable|string',
            'anthropic_model' => 'nullable|string',
            'gemini_api_key' => 'nullable|string',
            'gemini_model' => 'nullable|string',
            'embeddings_provider' => 'required|in:openai,gemini',
            'enable_embeddings' => 'boolean',
            'enable_rag' => 'boolean',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:100|max:128000',
            'top_p' => 'required|numeric|min:0|max:1',
        ]);

        $this->settings->save('chat.ai', $validated);

        return back()->with('success', 'Configuración de IA actualizada correctamente');
    }
}
