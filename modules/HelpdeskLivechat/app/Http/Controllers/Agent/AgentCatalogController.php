<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogManager;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;
use Modules\HelpdeskLivechat\Services\Widget\ProductShowcaseService;

/**
 * API de catálogo para el AGENTE en el panel: buscar productos y compartirlos
 * en la conversación como carrusel (coviewer). Autenticada por sesión web +
 * permiso helpdesk.conversations.reply (ver routes/agent.php).
 */
class AgentCatalogController extends Controller
{
    public function __construct(
        private readonly CatalogManager $catalog,
        private readonly ProductShowcaseService $showcase,
    ) {}

    /**
     * Busca productos del catálogo del canal Web de la conversación.
     */
    public function search(Request $request, Conversation $conversation): JsonResponse
    {
        // El permiso de ruta (helpdesk.conversations.reply) no basta: el agente
        // debe poder ver ESTA conversación (bandejas restringidas, solo propias).
        $this->authorize('view', $conversation);

        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json(['success' => true, 'data' => ['products' => []]]);
        }

        $products = $this->catalog->forWeb($this->resolveWeb($conversation), $conversation->customer?->language)->search($query, 12);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => array_map(static fn (CatalogProduct $p): array => $p->toArray(), $products),
            ],
        ]);
    }

    /**
     * Comparte en la conversación los productos indicados por id (los busca en
     * el catálogo y publica el carrusel como mensaje saliente del agente).
     */
    public function share(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:'.ProductShowcaseService::MAX_PRODUCTS],
            'product_ids.*' => ['required', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Idioma del cliente: las tarjetas le llegan con títulos y URLs en su idioma.
        $driver = $this->catalog->forWeb($this->resolveWeb($conversation), $conversation->customer?->language);

        $byId = $driver->findMany(array_map('strval', $validated['product_ids']));

        // Conserva el orden en que el agente seleccionó los productos.
        $products = [];
        foreach ($validated['product_ids'] as $id) {
            if (isset($byId[$id])) {
                $products[] = $byId[$id];
            }
        }

        if ($products === []) {
            return response()->json([
                'success' => false,
                'error' => 'No matching products found in catalog',
            ], 422);
        }

        $item = $this->showcase->showcase(
            $conversation,
            $products,
            $request->user()?->id,
            $validated['note'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => ['message_id' => $item?->id],
            // Misma forma que el resto de envíos del inbox, para que el hilo
            // del agente pinte el carrusel al momento (appendBubbleToThread).
            'item' => $item ? [
                'id' => $item->id,
                'type' => $item->type,
                'body' => $item->body,
                'metadata' => $item->metadata,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => $request->user()?->name,
                'is_outgoing' => true,
            ] : null,
        ]);
    }

    private function resolveWeb(Conversation $conversation): ?Web
    {
        $channel = $conversation->inbox?->channel;

        return $channel instanceof Web ? $channel : null;
    }
}
