<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\PromptSanitizer;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Convierte grupos de tickets ya resueltos en borradores de artículo.
 *
 * Cierra el círculo de todo lo anterior: la detección de similitud ya sabe
 * cuándo veinte tickets tratan del mismo asunto, y la deflexión del portal ya
 * enseña artículos antes de abrir el ticket. Lo que faltaba en medio era
 * escribir el artículo — el trabajo que nadie hace nunca porque siempre hay un
 * ticket más urgente.
 *
 * Se redacta a partir de las RESPUESTAS QUE FUNCIONARON: tickets cerrados o
 * resueltos, con la contestación real que se les dio. No es el modelo
 * inventando documentación, es el modelo ordenando lo que el equipo ya
 * respondió veinte veces.
 *
 * Siempre BORRADOR (`draft`, sin publicar). Un artículo publicado sin revisar
 * es documentación oficial escrita por nadie, y el cliente que la lee no sabe
 * que salió de una máquina.
 */
class ArticleDraftService
{
    /** Sin al menos esto, no hay patrón que documentar. */
    private const MIN_TICKETS = 4;

    public function __construct(
        private readonly AgentLlmService $llm,
        private readonly PromptSanitizer $sanitizer,
    ) {}

    public function isAvailable(): bool
    {
        return config('helpdesktickets.article_drafts.enabled', false)
            && helpdesk_helpcenter_enabled()
            && class_exists(HelpCenterArticle::class)
            && $this->llm->isConfigured();
    }

    /**
     * Redacta y guarda el borrador de artículo para un grupo de tickets.
     *
     * @param  array<int, int>  $ticketIds
     */
    public function draftFrom(array $ticketIds, ?string $topic = null): ?HelpCenterArticle
    {
        if (! $this->isAvailable() || count($ticketIds) < self::MIN_TICKETS) {
            return null;
        }

        $tickets = Ticket::query()
            ->whereIn('id', $ticketIds)
            // Solo resueltos: un ticket abierto no tiene todavía una respuesta
            // que sirva de documentación.
            ->whereNotNull('closed_at')
            ->with(['items'])
            ->limit(12)
            ->get();

        if ($tickets->count() < self::MIN_TICKETS) {
            return null;
        }

        $cases = $this->buildCases($tickets);

        if ($cases === '') {
            return null;
        }

        $article = $this->askLlm($cases, $topic);

        if ($article === null) {
            return null;
        }

        return $this->store($article, $tickets->pluck('id')->all());
    }

    /**
     * Pares problema/solución a partir de los tickets.
     *
     * Se toma el primer mensaje del cliente y la ÚLTIMA respuesta pública del
     * agente: la última es la que cerró el caso. Las notas internas quedan
     * fuera — son conversación entre compañeros y publicarlas sería filtrarlas.
     *
     * @param  Collection<int, Ticket>  $tickets
     */
    private function buildCases(Collection $tickets): string
    {
        return $tickets->map(function (Ticket $ticket): ?string {
            $question = $ticket->items
                ->where('is_internal', false)
                ->whereNull('user_id')
                ->sortBy('created_at')
                ->first()?->body;

            $answer = $ticket->items
                ->where('is_internal', false)
                ->filter(fn ($i) => $i->user_id !== null)
                ->sortByDesc('created_at')
                ->first()?->body;

            if (! $question || ! $answer) {
                return null;
            }

            return sprintf(
                "--- Caso\nConsulta: %s\nRespuesta que resolvió: %s",
                $this->sanitizer->sanitize(mb_substr(trim(strip_tags($question)), 0, 600)),
                mb_substr(trim(strip_tags($answer)), 0, 900),
            );
        })->filter()->implode("\n\n");
    }

    /**
     * @return array{title: string, body: string, excerpt: string}|null
     */
    private function askLlm(string $cases, ?string $topic): ?array
    {
        $raw = $this->llm->chat([
            [
                'role' => 'system',
                'content' => 'Redactas un artículo de ayuda a partir de casos de soporte ya resueltos. '
                    .'Responde SOLO con {"title": "...", "excerpt": "...", "body": "..."} en JSON válido, '
                    .'sin markdown alrededor. '
                    .'El título es la pregunta que se hace el cliente, no una etiqueta interna. '
                    .'El excerpt son una o dos frases. '
                    .'El body va en HTML simple (<p>, <ul>, <li>, <strong>) y explica el problema y los '
                    .'pasos para resolverlo. '
                    .'Escribe SOLO lo que se deduce de los casos: no inventes pasos, plazos, precios ni '
                    .'nombres de opciones que no aparezcan. No cites datos de ningún cliente concreto '
                    .'(nombres, pedidos, importes, direcciones): el artículo es público. '
                    .'Si los casos no comparten un problema común claro, responde {"title": ""}.',
            ],
            [
                'role' => 'user',
                'content' => ($topic ? "Tema aparente: {$topic}\n\n" : '')."Casos resueltos:\n\n{$cases}",
            ],
        ], ['temperature' => 0.3, 'max_tokens' => 1400, 'feature' => 'article_draft']);

        return $this->parse($raw);
    }

    /**
     * @return array{title: string, body: string, excerpt: string}|null
     */
    private function parse(?string $raw): ?array
    {
        if ($raw === null || ! preg_match('/\{.*\}/s', $raw, $matches)) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        if (! is_array($decoded)) {
            return null;
        }

        $title = trim((string) ($decoded['title'] ?? ''));
        $body = trim((string) ($decoded['body'] ?? ''));

        // Título vacío es la señal acordada de "estos casos no comparten nada".
        if ($title === '' || $body === '') {
            return null;
        }

        return [
            'title' => mb_substr($title, 0, 250),
            'body' => $body,
            'excerpt' => mb_substr(trim((string) ($decoded['excerpt'] ?? '')), 0, 300),
        ];
    }

    /**
     * @param  array{title: string, body: string, excerpt: string}  $article
     * @param  array<int, int>  $ticketIds
     */
    private function store(array $article, array $ticketIds): ?HelpCenterArticle
    {
        try {
            return HelpCenterArticle::query()->create([
                'title' => $article['title'],
                'slug' => $this->uniqueSlug($article['title']),
                'body' => $article['body'],
                'content' => $article['body'],
                'excerpt' => $article['excerpt'],
                // Borrador y no publicado: las dos banderas, porque el módulo
                // usa ambas y publicar sin revisar es documentación oficial
                // escrita por nadie.
                'draft' => true,
                'is_published' => false,
                'active' => false,
                'category_id' => (int) config('helpdesktickets.article_drafts.category_id') ?: null,
                'meta_description' => mb_substr(
                    'Borrador generado a partir de '.count($ticketIds).' tickets resueltos.',
                    0,
                    255
                ),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ArticleDraftService: no se pudo guardar el borrador', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'articulo';
        $slug = $base;
        $i = 2;

        while (HelpCenterArticle::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
