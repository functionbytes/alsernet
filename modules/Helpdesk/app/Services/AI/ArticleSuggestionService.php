<?php

namespace Modules\Helpdesk\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\KnowledgeRetrievalService;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;

/**
 * Sugiere artículos de conocimiento relevantes al último mensaje del cliente
 * para el composer del inbox (deflexión asistida al agente).
 *
 * Fuentes (ambas opcionales, con degradación a lista vacía):
 *  - HelpdeskHelpcenter: artículos publicados, vía HelpcenterWidgetService
 *    (fulltext boolean-mode). Es el corpus canónico con URL pública, por eso
 *    es la fuente primaria. Los artículos del Helpcenter NO están indexados
 *    en la knowledge base de HelpdeskAgents (corpus separado
 *    helpdesk_helpcenter_article_embeddings), así que se consultan directo.
 *  - HelpdeskAgents: knowledge base con embeddings del primer agente IA
 *    activo (KnowledgeRetrievalService, similitud coseno con fallback
 *    fulltext). Complementa con documentación interna; url puede ser null.
 *
 * El resultado se cachea por (conversación, último item del cliente) unos
 * minutos: repetir la consulta sin mensajes nuevos no recalcula nada.
 */
class ArticleSuggestionService
{
    private const CACHE_TTL_MINUTES = 5;

    private const MAX_SUGGESTIONS = 5;

    /**
     * @return array{query: string, suggestions: array<int, array{id: string, title: string, excerpt: string, url: string|null, source: string}>}
     */
    public function suggest(Conversation $conversation): array
    {
        [$query, $lastItemId] = $this->lastCustomerMessage($conversation);

        if (Str::length($query) < 3) {
            return ['query' => $query, 'suggestions' => []];
        }

        $cacheKey = sprintf('helpdesk:article_suggestions:%d:%d', $conversation->id, $lastItemId);

        $suggestions = Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->buildSuggestions($query)
        );

        return ['query' => $query, 'suggestions' => $suggestions];
    }

    /**
     * Plain-text del último mensaje no interno del cliente y su id (0 si no
     * hay ninguno; se usa el asunto como consulta de reserva).
     *
     * @return array{0: string, 1: int}
     */
    private function lastCustomerMessage(Conversation $conversation): array
    {
        $item = $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereNotNull('author_id')
            ->latest()
            ->first();

        $text = trim(strip_tags($item?->body ?? ''));

        if ($text === '') {
            $text = trim((string) ($conversation->subject ?? ''));
        }

        return [Str::limit($text, 300, ''), (int) ($item?->id ?? 0)];
    }

    /**
     * @return array<int, array{id: string, title: string, excerpt: string, url: string|null, source: string}>
     */
    private function buildSuggestions(string $query): array
    {
        $results = array_merge(
            $this->fromHelpcenter($query),
            $this->fromKnowledgeBase($query),
        );

        // Dedupe por url (o título si no hay url), conservando el orden:
        // Helpcenter primero (tiene URL insertable), knowledge base después.
        $seen = [];
        $unique = [];

        foreach ($results as $result) {
            $key = mb_strtolower($result['url'] ?: $result['title']);

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $result;
        }

        return array_slice($unique, 0, self::MAX_SUGGESTIONS);
    }

    /**
     * @return array<int, array{id: string, title: string, excerpt: string, url: string|null, source: string}>
     */
    private function fromHelpcenter(string $query): array
    {
        if (! function_exists('helpdesk_helpcenter_enabled')
            || ! helpdesk_helpcenter_enabled()
            || ! class_exists(HelpcenterWidgetService::class)) {
            return [];
        }

        try {
            $articles = app(HelpcenterWidgetService::class)
                ->searchArticles($query);
        } catch (\Throwable $e) {
            Log::warning('ArticleSuggestionService: helpcenter search failed', ['error' => $e->getMessage()]);

            return [];
        }

        return array_map(fn (array $a): array => [
            'id' => (string) $a['id'],
            'title' => (string) $a['title'],
            'excerpt' => (string) ($a['excerpt'] ?? ''),
            'url' => (string) ($a['url'] ?? '') ?: null,
            'source' => 'helpcenter',
        ], array_slice($articles, 0, self::MAX_SUGGESTIONS));
    }

    /**
     * Knowledge base con embeddings de HelpdeskAgents (soft dependency): se
     * consulta con el primer agente IA activo. Documentos sin source_url se
     * devuelven con url null (el composer solo inserta el extracto).
     *
     * @return array<int, array{id: string, title: string, excerpt: string, url: string|null, source: string}>
     */
    private function fromKnowledgeBase(string $query): array
    {
        if (! class_exists(AiAgent::class)
            || ! class_exists(KnowledgeRetrievalService::class)) {
            return [];
        }

        try {
            $agent = AiAgent::query()->active()->first();

            if (! $agent) {
                return [];
            }

            $docs = app(KnowledgeRetrievalService::class)
                ->findRelevant($agent, $query, self::MAX_SUGGESTIONS);
        } catch (\Throwable $e) {
            Log::warning('ArticleSuggestionService: knowledge base search failed', ['error' => $e->getMessage()]);

            return [];
        }

        return $docs->map(fn ($doc): array => [
            'id' => 'kb-'.$doc->id,
            'title' => (string) $doc->title,
            'excerpt' => (string) ($doc->summary ?: $doc->excerpt),
            'url' => $doc->source_url ?: null,
            'source' => 'knowledge_base',
        ])->values()->all();
    }
}
