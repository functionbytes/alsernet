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
        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json(['success' => true, 'data' => ['products' => []]]);
        }

        $products = $this->catalog->forWeb($this->resolveWeb($conversation))->search($query, 12);

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
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:'.ProductShowcaseService::MAX_PRODUCTS],
            'product_ids.*' => ['required', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $driver = $this->catalog->forWeb($this->resolveWeb($conversation));

        $products = [];
        foreach ($validated['product_ids'] as $id) {
            $product = $driver->find((string) $id);
            if ($product) {
                $products[] = $product;
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
        ]);
    }

    private function resolveWeb(Conversation $conversation): ?Web
    {
        $channel = $conversation->inbox?->channel;

        return $channel instanceof Web ? $channel : null;
    }
}
