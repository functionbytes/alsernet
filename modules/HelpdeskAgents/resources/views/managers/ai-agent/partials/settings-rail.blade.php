{{-- Rail de ayuda de los ajustes del Agente IA. $showUsage controla la
     tarjeta de consumo (en el primer arranque no hay nada que enseñar). --}}
<div class="ais-card">
    <h3 class="ais-section-title mb-0">Dónde sacar la clave</h3>
    <div class="ais-links">
        <a href="https://console.anthropic.com/" target="_blank" rel="noopener">Anthropic · console.anthropic.com</a>
        <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">OpenAI · platform.openai.com</a>
        <a href="https://aistudio.google.com/" target="_blank" rel="noopener">Google Gemini · aistudio.google.com</a>
        <a href="https://ollama.ai/" target="_blank" rel="noopener">Ollama (local) · ollama.ai</a>
    </div>
</div>

@if (($showUsage ?? true) && ! is_null($usage ?? null))
    <div class="ais-card">
        <h3 class="ais-section-title mb-0">Consumo del mes</h3>
        @if ($usage['calls'] > 0)
            <div class="ais-usage-line">
                <span class="ais-usage-value">{{ number_format($usage['calls'], 0, ',', '.') }}</span>
                <span class="ais-help">{{ $usage['calls'] === 1 ? 'llamada al modelo' : 'llamadas al modelo' }}</span>
            </div>
            <div class="ais-usage-line">
                <span class="ais-help">Tokens consumidos</span>
                <span class="ais-help fw-semibold">{{ number_format($usage['tokens'], 0, ',', '.') }}</span>
            </div>
        @else
            <p class="ais-help mb-0">Sin actividad este mes.</p>
        @endif
    </div>
@endif
