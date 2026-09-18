<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Collection;
use Modules\HelpdeskAgents\Models\TicketEmbedding;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Detecta que muchos tickets recientes hablan de lo mismo.
 *
 * Quince tickets en una hora sobre la misma caída no son quince problemas: son
 * uno. Sin esta señal, quince agentes investigan lo mismo por separado, cada
 * uno tarda lo que tarda y el cliente número quince espera como si fuera el
 * primero. Con ella, alguien investiga una vez y se responde en bloque —
 * BulkTicketsController ya existe para eso.
 *
 * El agrupamiento es voraz y deliberadamente simple: se recorren los tickets de
 * la ventana del más reciente al más antiguo y cada uno abre grupo o se une al
 * primero con el que supere el umbral. No es k-means ni pretende serlo; para
 * "¿hay un pico de lo mismo ahora mismo?" un algoritmo más fino no cambia la
 * respuesta y sí el coste, porque el coseno se calcula en PHP.
 *
 * El LLM solo entra al final y para una cosa: ponerle nombre al grupo. La
 * detección es puramente vectorial, así que funciona igual sin él.
 */
class IncidentDetectionService
{
    public function __construct(
        private readonly TicketEmbeddingService $embeddings,
        private readonly AgentLlmService $llm,
    ) {}

    /**
     * @return Collection<int, array{ticket_ids: array<int, int>, size: int, label: string|null, first_seen: string, category_id: int|null}>
     */
    public function detect(?int $windowMinutes = null, ?int $minSize = null, ?int $maxTickets = null): Collection
    {
        if (! config('helpdeskagents.ticket_similarity.enabled', false) || ! $this->embeddings->isAvailable()) {
            return collect();
        }

        $windowMinutes ??= (int) config('helpdeskagents.ticket_similarity.incident_window_minutes', 60);
        $minSize ??= (int) config('helpdeskagents.ticket_similarity.incident_min_size', 5);
        $threshold = (float) config('helpdeskagents.ticket_similarity.incident_similarity', 0.88);

        // El techo de tickets escala con la ventana: 300 sobra para «la última
        // hora» y se queda corto para el barrido mensual que usa
        // SuggestHelpArticlesCommand sobre este mismo agrupamiento.
        $recent = $this->embeddings->recent(
            $windowMinutes,
            $maxTickets ?? (int) config('helpdeskagents.ticket_similarity.incident_max_tickets', 300),
        );

        if ($recent->count() < $minSize) {
            return collect();
        }

        return $this->cluster($recent, $threshold)
            ->filter(fn (array $group) => count($group['ticket_ids']) >= $minSize)
            ->map(fn (array $group) => $this->describe($group))
            ->sortByDesc('size')
            ->values();
    }

    /**
     * Agrupamiento voraz por similitud coseno.
     *
     * @param  Collection<int, TicketEmbedding>  $items
     * @return Collection<int, array{ticket_ids: array<int, int>, size: int, first_seen: string, category_id: int|null, centroid_id: int}>
     */
    private function cluster(Collection $items, float $threshold): Collection
    {
        /** @var array<int, array{seed: object, members: array<int, object>}> $groups */
        $groups = [];

        foreach ($items as $item) {
            $placed = false;

            foreach ($groups as &$group) {
                $similarity = $this->embeddings->cosine(
                    $group['seed']->embedding,
                    $group['seed']->vector_norm,
                    $item->embedding,
                    $item->vector_norm,
                );

                if ($similarity >= $threshold) {
                    $group['members'][] = $item;
                    $placed = true;
                    break;
                }
            }
            unset($group);

            if (! $placed) {
                $groups[] = ['seed' => $item, 'members' => [$item]];
            }
        }

        return collect($groups)->map(function (array $group): array {
            $members = collect($group['members']);

            return [
                'ticket_ids' => $members->pluck('ticket_id')->all(),
                'size' => $members->count(),
                'first_seen' => (string) $members->min('created_at'),
                'category_id' => $group['seed']->category_id,
                'centroid_id' => $group['seed']->ticket_id,
            ];
        });
    }

    /**
     * Pone nombre al grupo con el asunto de sus tickets.
     *
     * Sin LLM cae al asunto del ticket semilla, que ya es informativo: el
     * aviso sirve igual, solo con peor título.
     *
     * @param  array<string, mixed>  $group
     * @return array<string, mixed>
     */
    private function describe(array $group): array
    {
        $subjects = Ticket::query()
            ->whereIn('id', array_slice($group['ticket_ids'], 0, 10))
            ->pluck('subject')
            ->filter()
            ->values();

        $group['label'] = $this->labelFor($subjects) ?? $subjects->first();

        unset($group['centroid_id']);

        return $group;
    }

    /**
     * @param  Collection<int, string>  $subjects
     */
    private function labelFor(Collection $subjects): ?string
    {
        if ($subjects->isEmpty() || ! $this->llm->isConfigured()) {
            return null;
        }

        $label = $this->llm->chat([
            [
                'role' => 'system',
                'content' => 'Te doy los asuntos de varios tickets de soporte que tratan del mismo problema. '
                    .'Responde SOLO con un titular de como mucho 10 palabras que describa ese problema comun, '
                    .'sin comillas ni puntuacion final. Nada mas.',
            ],
            ['role' => 'user', 'content' => $subjects->map(fn ($s) => '- '.$s)->implode("\n")],
        ], ['temperature' => 0.2, 'max_tokens' => 40, 'feature' => 'incident_label']);

        $label = trim((string) $label);

        return $label !== '' ? mb_substr($label, 0, 150) : null;
    }
}
