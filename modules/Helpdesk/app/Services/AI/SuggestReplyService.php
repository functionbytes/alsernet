<?php

namespace Modules\Helpdesk\Services\AI;

use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Conversation;

class SuggestReplyService
{
    /**
     * Punto de extensión: los módulos satélite (p. ej. HelpdeskErp) escuchan
     * este evento de cadena y devuelven un fragmento de texto con contexto
     * del cliente para el prompt. Payload: [Conversation, ?User agente].
     * Cada listener decide sus permisos y NO debe bloquear (solo caché).
     */
    public const CONTEXT_EVENT = 'helpdesk.ai.reply-context';

    /** Tope por fragmento de contexto aportado por una extensión. */
    private const EXTRA_CONTEXT_MAX = 1500;

    // Mismos 6 idiomas que helpdesk_customers.language / el panel "Traducir"
    // de HelpdeskTranslate — instrucción en lenguaje natural para el prompt.
    private const LANGUAGE_NAMES = [
        'es' => 'español',
        'en' => 'inglés',
        'fr' => 'francés',
        'de' => 'alemán',
        'pt' => 'portugués',
        'it' => 'italiano',
    ];

    public function __construct(
        private readonly AiClient $client,
        private readonly PromptSanitizer $sanitizer,
    ) {}

    /**
     * Suggest reply texts for a conversation.
     *
     * @return string[]
     */
    public function suggest(Conversation $conversation, int $count = 3): array
    {
        if (! $this->client->isEnabled()) {
            return [];
        }

        $context = $this->buildContext($conversation);

        $systemPrompt = <<<'PROMPT'
Eres un agente de soporte al cliente profesional y empático. Tu objetivo es sugerir respuestas
cortas, claras y directas al cliente. Usa un tono profesional pero cercano.
Responde SOLO con un array JSON de strings. Sin explicaciones ni texto adicional.
Ejemplo: ["Respuesta 1", "Respuesta 2", "Respuesta 3"]
PROMPT;

        // Las sugerencias son borradores para ENVIAR al cliente (a diferencia
        // del resumen interno del caso, que es solo para el agente) — antes
        // salían siempre en español aunque el cliente escribiera en otro
        // idioma. buildContext() ya carga la relación customer más abajo.
        $customerLang = strtolower((string) ($conversation->customer?->language ?? ''));
        if (isset(self::LANGUAGE_NAMES[$customerLang]) && $customerLang !== 'es') {
            $systemPrompt .= "\nEl cliente escribe en ".self::LANGUAGE_NAMES[$customerLang].'. Redacta las respuestas en ese mismo idioma.';
        }

        $systemPrompt .= "\n".$this->sanitizer->systemGuard();

        $userPrompt = "Genera exactamente {$count} sugerencias de respuesta para este caso de soporte.\n\n"
            .$context;

        $raw = $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);

        return $this->parseJsonArray($raw, $count);
    }

    private function buildContext(Conversation $conversation): string
    {
        $conversation->loadMissing('customer');

        $customerName = $conversation->customer?->name ?? 'Cliente';
        $channel = $conversation->channel ?? 'web';
        $subject = $conversation->subject ?? 'Sin asunto';

        $recentItems = $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->latest()
            ->limit(10)
            ->get()
            ->reverse();

        $thread = $recentItems->map(function ($item) {
            $role = $item->isFromAgent() ? 'Agente' : 'Cliente';
            $body = strip_tags($item->body ?? '');

            if (! $item->isFromAgent()) {
                $body = $this->sanitizer->sanitize($body);
            }

            return "[{$role}]: ".$body;
        })->implode("\n");

        $extra = $this->extraContext($conversation);

        return <<<TEXT
Canal: {$channel}
Asunto: {$subject}
Cliente: {$customerName}
{$extra}
Últimos mensajes:
{$thread}
TEXT;
    }

    /**
     * Fragmentos de contexto aportados por extensiones (CONTEXT_EVENT). Un
     * fallo de una extensión nunca impide la sugerencia.
     */
    private function extraContext(Conversation $conversation): string
    {
        try {
            $responses = Event::dispatch(self::CONTEXT_EVENT, [$conversation, auth()->user()]);
        } catch (\Throwable $e) {
            report($e);

            return '';
        }

        $parts = [];
        foreach ((array) $responses as $fragment) {
            if (is_string($fragment) && trim($fragment) !== '') {
                $parts[] = $this->sanitizer->sanitize(mb_substr(trim($fragment), 0, self::EXTRA_CONTEXT_MAX));
            }
        }

        return $parts === [] ? '' : "\n".implode("\n\n", $parts)."\n";
    }

    /**
     * @return string[]
     */
    private function parseJsonArray(?string $raw, int $expected): array
    {
        if (empty($raw)) {
            return [];
        }

        // Extract JSON array from the response (handle markdown code blocks)
        if (preg_match('/\[.*\]/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded, 'is_string'));
            }
        }

        return [];
    }
}
