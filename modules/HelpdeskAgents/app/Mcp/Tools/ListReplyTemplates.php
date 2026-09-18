<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketTemplate;

/**
 * Catalogo de textos aprobados: plantillas de ticket, respuestas rapidas y
 * macros con accion de respuesta.
 *
 * Al sugerir una respuesta, TicketReplySuggestionService ya mete una preseleccion
 * en el prompt (ver TicketTemplateMatcher) — el catalogo entero dispararia el
 * coste por sugerencia. Esta tool esta para cuando esa preseleccion se queda
 * corta y el modelo necesita mirar mas, y para el uso desde el servidor MCP.
 *
 * Devuelve el texto con las variables {{...}} SIN interpolar, a proposito: es
 * la unica forma de que el modelo vea que la plantilla lleva un hueco y no se
 * invente el valor. La interpolacion real la hace TicketVariableInterpolator
 * al aplicar la plantilla.
 */
#[IsReadOnly]
class ListReplyTemplates extends HelpdeskTool
{
    protected string $description = 'Lista las plantillas de respuesta, respuestas rapidas y macros aprobadas, opcionalmente filtradas por texto o categoria. El texto conserva sus variables {{...}} sin rellenar.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Filtra por nombre o contenido.'),
            'kind' => $schema->string()
                ->description('Tipo a listar: "plantilla", "respuesta_rapida", "macro" o "todas" (por defecto).')
                ->enum(['plantilla', 'respuesta_rapida', 'macro', 'todas']),
            'limit' => $schema->integer()
                ->description('Numero maximo de resultados por tipo (1-15, por defecto 8).'),
        ];
    }

    protected function run(Request $request): Response
    {
        $query = trim((string) ($request->get('query') ?? ''));
        $kind = (string) ($request->get('kind') ?? 'todas');
        $limit = max(1, min(15, (int) ($request->get('limit') ?? 8)));
        $userId = (int) (auth()->id() ?? 0);
        $categoryId = $this->context()->ticket()?->category_id;

        $payload = [];

        if ($kind === 'todas' || $kind === 'plantilla') {
            $payload['plantillas'] = $this->templates($query, $limit, $userId, $categoryId);
        }

        if ($kind === 'todas' || $kind === 'respuesta_rapida') {
            $payload['respuestas_rapidas'] = $this->cannedReplies($query, $limit, $userId);
        }

        if ($kind === 'todas' || $kind === 'macro') {
            $payload['macros'] = $this->macros($query, $limit, $userId);
        }

        return Response::json($payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(string $query, int $limit, int $userId, ?int $categoryId): array
    {
        return TicketTemplate::query()
            ->active()
            ->when($userId > 0, fn ($q) => $q->visibleTo($userId), fn ($q) => $q->general())
            ->when($categoryId, fn ($q) => $q->where(fn ($sub) => $sub->where('category_id', $categoryId)->orWhereNull('category_id')))
            ->when($query !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$query}%")->orWhere('body', 'like', "%{$query}%")))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (TicketTemplate $t): array => [
                'id' => $t->id,
                'tipo' => 'plantilla',
                'nombre' => $t->name,
                'asunto' => $t->subject,
                'texto' => $this->clip($t->body, 1500),
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cannedReplies(string $query, int $limit, int $userId): array
    {
        return TicketCannedReply::query()
            ->where('is_active', true)
            ->when(
                $userId > 0,
                fn ($q) => $q->where(fn ($sub) => $sub->where('is_global', true)->orWhere('user_id', $userId)),
                fn ($q) => $q->where('is_global', true)
            )
            ->when($query !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('title', 'like', "%{$query}%")->orWhere('content', 'like', "%{$query}%")))
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get()
            ->map(fn (TicketCannedReply $r): array => [
                'id' => $r->id,
                'tipo' => 'respuesta_rapida',
                'nombre' => $r->title,
                'atajo' => $r->short_code,
                'texto' => $this->clip($r->content, 1500),
            ])->all();
    }

    /**
     * Solo macros con una accion de respuesta: las que unicamente cambian
     * estado o asignan no aportan texto que reutilizar.
     *
     * @return array<int, array<string, mixed>>
     */
    private function macros(string $query, int $limit, int $userId): array
    {
        return Macro::query()
            ->where('is_active', true)
            ->when(
                $userId > 0,
                fn ($q) => $q->where(fn ($sub) => $sub->where('is_shared', true)->orWhere('user_id', $userId)),
                fn ($q) => $q->where('is_shared', true)
            )
            ->when($query !== '', fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orderByDesc('usage_count')
            ->limit($limit * 2)
            ->get()
            ->map(function (Macro $macro): ?array {
                $reply = collect($macro->actions ?? [])
                    ->first(fn ($a) => ($a['type'] ?? null) === 'reply' && filled($a['value'] ?? null));

                if ($reply === null) {
                    return null;
                }

                return [
                    'id' => $macro->id,
                    'tipo' => 'macro',
                    'nombre' => $macro->name,
                    'texto' => $this->clip($reply['value'], 1500),
                ];
            })
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }
}
