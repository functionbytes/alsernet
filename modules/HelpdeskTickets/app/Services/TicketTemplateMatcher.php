<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketTemplate;

/**
 * Preselecciona las plantillas candidatas que se le ensenan al LLM al sugerir
 * una respuesta.
 *
 * Existe por coste, no por elegancia. Meter el catalogo entero en el prompt
 * cuesta tokens en CADA iteracion del bucle de herramientas y ademas empeora
 * el resultado: con cuarenta textos delante, el modelo elige peor que con los
 * ocho que de verdad pegan.
 *
 * El criterio es deliberadamente simple y sin coste externo: filtrar por
 * categoria y visibilidad en SQL, y ordenar por solapamiento de palabras con
 * el ticket. Nada de embeddings — la lista final la revisa el modelo, que es
 * quien decide, asi que aqui basta con no dejar fuera la buena.
 *
 * Los textos salen con sus variables {{...}} SIN interpolar, a proposito: es
 * lo unico que hace que el modelo vea el hueco en vez de inventarse el valor.
 */
class TicketTemplateMatcher
{
    /** Palabras vacias del castellano/ingles que no discriminan nada. */
    private const STOPWORDS = [
        'para', 'como', 'este', 'esta', 'esto', 'pero', 'porque', 'cuando', 'donde',
        'todo', 'toda', 'hola', 'buenos', 'buenas', 'dias', 'tardes', 'gracias',
        'saludos', 'favor', 'puede', 'puedo', 'tengo', 'hemos', 'sobre', 'desde',
        'hasta', 'entre', 'mas', 'muy', 'sus', 'con', 'the', 'and', 'for', 'you',
        'that', 'this', 'have', 'from', 'with', 'your', 'not', 'are', 'was',
        // Formulas de cortesia: aparecen en casi todos los tickets Y en casi
        // todas las plantillas, asi que puntuan a todas por igual y solo
        // anaden ruido al ranking.
        'antemano', 'atentamente', 'cordialmente', 'estimado', 'estimada',
        'estimados', 'regards', 'thanks', 'hello',
    ];

    /**
     * @return array<int, array{id: int, kind: string, name: string, subject: string|null, body: string}>
     */
    public function candidates(Ticket $ticket, ?int $userId = null, int $limit = 8): array
    {
        $terms = $this->terms($ticket);

        $pool = array_merge(
            $this->templates($ticket, $userId),
            $this->cannedReplies($userId),
            $this->macros($userId),
        );

        if ($pool === []) {
            return [];
        }

        usort($pool, fn (array $a, array $b): int => $this->score($b, $terms) <=> $this->score($a, $terms));

        return array_slice($pool, 0, max(1, $limit));
    }

    /**
     * Palabras significativas del ticket con las que puntuar.
     *
     * @return array<int, string>
     */
    private function terms(Ticket $ticket): array
    {
        $text = mb_strtolower(strip_tags(($ticket->subject ?? '').' '.($ticket->description ?? '')));
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $words = array_filter(
            $words,
            fn (string $w): bool => mb_strlen($w) >= 4 && ! in_array($w, self::STOPWORDS, true)
        );

        return array_slice(array_values(array_unique($words)), 0, 30);
    }

    /**
     * @param  array{name: string, subject: string|null, body: string}  $item
     * @param  array<int, string>  $terms
     */
    private function score(array $item, array $terms): int
    {
        if ($terms === []) {
            return 0;
        }

        $haystack = mb_strtolower($item['name'].' '.($item['subject'] ?? '').' '.$item['body']);
        $score = 0;

        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                // El nombre pesa mas que el cuerpo: una plantilla llamada
                // "Devolucion fuera de plazo" acierta el tema aunque el cuerpo
                // comparta poco vocabulario con el mensaje del cliente.
                $score += str_contains(mb_strtolower($item['name']), $term) ? 3 : 1;
            }
        }

        return $score;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(Ticket $ticket, ?int $userId): array
    {
        return TicketTemplate::query()
            ->active()
            ->when(
                $userId,
                fn ($q) => $q->visibleTo($userId),
                fn ($q) => $q->general()
            )
            // Las plantillas sin categoria valen para cualquier ticket; las que
            // tienen una, solo para la suya.
            ->when(
                $ticket->category_id,
                fn ($q) => $q->where(fn ($sub) => $sub->whereNull('category_id')->orWhere('category_id', $ticket->category_id)),
                fn ($q) => $q->whereNull('category_id')
            )
            ->limit(60)
            ->get()
            ->map(fn (TicketTemplate $t): array => [
                'id' => $t->id,
                'kind' => 'plantilla',
                'name' => (string) $t->name,
                'subject' => $t->subject,
                'body' => (string) $t->body,
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cannedReplies(?int $userId): array
    {
        return TicketCannedReply::query()
            ->where('is_active', true)
            ->when(
                $userId,
                fn ($q) => $q->where(fn ($sub) => $sub->where('is_global', true)->orWhere('user_id', $userId)),
                fn ($q) => $q->where('is_global', true)
            )
            ->orderByDesc('usage_count')
            ->limit(60)
            ->get()
            ->map(fn (TicketCannedReply $r): array => [
                'id' => $r->id,
                'kind' => 'respuesta_rapida',
                'name' => (string) $r->title,
                'subject' => null,
                'body' => (string) $r->content,
            ])->all();
    }

    /**
     * Solo macros con accion de respuesta: las que unicamente cambian estado o
     * asignan no aportan texto que reutilizar.
     *
     * @return array<int, array<string, mixed>>
     */
    private function macros(?int $userId): array
    {
        return Macro::query()
            ->where('is_active', true)
            ->when(
                $userId,
                fn ($q) => $q->where(fn ($sub) => $sub->where('is_shared', true)->orWhere('user_id', $userId)),
                fn ($q) => $q->where('is_shared', true)
            )
            ->orderByDesc('usage_count')
            ->limit(40)
            ->get()
            ->map(function (Macro $macro): ?array {
                $reply = collect($macro->actions ?? [])
                    ->first(fn ($a) => ($a['type'] ?? null) === 'reply' && filled($a['value'] ?? null));

                return $reply === null ? null : [
                    'id' => $macro->id,
                    'kind' => 'macro',
                    'name' => (string) $macro->name,
                    'subject' => null,
                    'body' => (string) $reply['value'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
