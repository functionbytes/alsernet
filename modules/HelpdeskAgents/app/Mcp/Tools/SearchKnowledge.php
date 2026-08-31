<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\HelpdeskAgents\Concerns\InteractsWithDefaultAiAgent;
use Modules\HelpdeskAgents\Services\KnowledgeRetrievalService;

/**
 * Base de conocimiento del agente IA.
 *
 * Envuelve KnowledgeRetrievalService tal cual: busca por similaridad coseno si
 * hay embeddings configurados y cae a fulltext si no. Esa decision ya esta
 * tomada alli y no se duplica aqui.
 */
#[IsReadOnly]
class SearchKnowledge extends HelpdeskTool
{
    use InteractsWithDefaultAiAgent;

    protected string $description = 'Busca en la base de conocimiento interna documentacion relevante para el problema. Uselo cuando la respuesta dependa de un procedimiento, una politica o una condicion que deba citar con exactitud.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Que se busca, en lenguaje natural.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Numero maximo de documentos (1-8, por defecto 4).'),
        ];
    }

    protected function run(Request $request): Response
    {
        $query = trim((string) $request->get('query'));

        if ($query === '') {
            return Response::error('query no puede estar vacio.');
        }

        $agent = $this->getDefaultAgent();

        if ($agent === null) {
            return Response::error('No hay ningun agente IA configurado.');
        }

        $limit = max(1, min(8, (int) ($request->get('limit') ?? 4)));

        $documents = app(KnowledgeRetrievalService::class)->findRelevant($agent, $query, $limit);

        return Response::json([
            'query' => $query,
            'documents' => $documents->map(fn ($doc): array => [
                'title' => $doc->title ?? null,
                'content' => $this->clip($doc->content ?? '', 1200),
            ])->values()->all(),
        ]);
    }
}
