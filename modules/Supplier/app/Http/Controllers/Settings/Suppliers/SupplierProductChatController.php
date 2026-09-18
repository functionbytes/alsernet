<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Chat\CompareChatRequest;
use Modules\Supplier\Http\Requests\Chat\RegenerateChatRequest;
use Modules\Supplier\Http\Requests\Chat\ResetChatRequest;
use Modules\Supplier\Http\Requests\Chat\SaveChatRequest;
use Modules\Supplier\Http\Requests\Chat\SendChatRequest;
use Modules\Supplier\Models\Ai\AiAuditLog;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductChat;
use Modules\Supplier\Models\Product\ProductChatMessage;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Services\ProductChatService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierProductChatController extends Controller
{
    private const AVAILABLE_MODELS = [
        'gpt-4o-mini' => [
            'label' => 'GPT-4o Mini',
            'description' => 'Rápido y económico',
            'cost_per_1k_in' => 0.00015,
            'cost_per_1k_out' => 0.0006,
            'provider' => 'openai',
        ],
        'gpt-4o' => [
            'label' => 'GPT-4o',
            'description' => 'Potente y con búsqueda web',
            'cost_per_1k_in' => 0.0025,
            'cost_per_1k_out' => 0.01,
            'provider' => 'openai',
        ],
        'claude-3-5-haiku' => [
            'label' => 'Claude Haiku',
            'description' => 'Rápido, optimizado para volumen',
            'cost_per_1k_in' => 0.0008,
            'cost_per_1k_out' => 0.004,
            'provider' => 'anthropic',
        ],
        'claude-3-5-sonnet' => [
            'label' => 'Claude Sonnet',
            'description' => 'Avanzado, alta calidad',
            'cost_per_1k_in' => 0.003,
            'cost_per_1k_out' => 0.015,
            'provider' => 'anthropic',
        ],
        'gemini-2.5-flash' => [
            'label' => 'Gemini 2.5 Flash',
            'description' => 'Google — última generación + búsqueda web gratis',
            'cost_per_1k_in' => 0.00015,
            'cost_per_1k_out' => 0.0006,
            'provider' => 'google',
        ],
        'gemini-2.5-pro' => [
            'label' => 'Gemini 2.5 Pro',
            'description' => 'Google — máxima calidad + búsqueda web gratis',
            'cost_per_1k_in' => 0.00125,
            'cost_per_1k_out' => 0.01,
            'provider' => 'google',
        ],
    ];

    public function __construct(private readonly ProductChatService $chatService) {}

    public function show(Request $request, string $uid): View
    {
        $product = Product::withDetails()->where('uid', $uid)->firstOrFail();

        $prompts = Prompt::active()
            ->notTemplates()
            ->orderBy('label')
            ->get(['id', 'uid', 'label', 'scope', 'prompt_template', 'output_language', 'tone', 'enable_web_search', 'ai_model']);

        $chatUid = $request->query('chat');
        $currentChat = null;
        $messages = collect();

        if ($chatUid) {
            $currentChat = ProductChat::with('conversation')
                ->where('uid', $chatUid)
                ->where('product_id', $product->id)
                ->first();
            if ($currentChat) {
                $messages = $currentChat->conversation;
            }
        }

        $historyQuery = ProductChat::where('product_id', $product->id)
            ->orderByDesc('created_at');

        if ($search = $request->query('history_search')) {
            $historyQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('messages', fn ($m) => $m->where('content', 'like', "%{$search}%"));
            });
        }

        $recentChats = $historyQuery->limit(50)->get();

        // Contenido existente para este producto (cualquier estado útil para el botón "volver")
        $existingContent = AiContent::where('supplier_product_id', $product->id)
            ->whereNotIn('status', [
                AiContent::STATUS_GENERATING,
                AiContent::STATUS_PENDING_GENERATION,
                AiContent::STATUS_ERROR_INSUFFICIENT_INFO,
                AiContent::STATUS_ERROR_SOURCE_UNAVAILABLE,
                AiContent::STATUS_ERROR_GENERATION_FAILED,
            ])
            ->latest()
            ->first();

        // Variables del producto para renderizar prompts en el cliente
        $promptVariables = $this->chatService->buildProductVariables($product);

        $pageTitle = 'Chat IA: '.$product->name;
        $breadcrumb = 'Configuración / Proveedores / Productos / Chat IA';

        $models = self::AVAILABLE_MODELS;
        $forceChat = $request->boolean('force');

        return view('supplier::settings.views.products.chat', compact(
            'product', 'prompts', 'models', 'recentChats', 'currentChat', 'messages',
            'pageTitle', 'breadcrumb', 'existingContent', 'promptVariables', 'forceChat'
        ));
    }

    public function send(SendChatRequest $request, string $uid): JsonResponse
    {
        $product = Product::withDetails()->where('uid', $uid)->firstOrFail();

        $data = $request->validated();

        $model = $data['model'] ?? 'gpt-4o-mini';
        $webSearch = (bool) ($data['web_search'] ?? false);
        $chatUidParam = ! empty($data['chat_uid']) ? $data['chat_uid'] : null;
        $promptUidParam = ! empty($data['prompt_uid']) ? $data['prompt_uid'] : null;

        $chat = $this->resolveChat($product, $chatUidParam, $model, $webSearch, $promptUidParam);

        $userMessage = $this->buildUserMessage($product, $chat, $data);
        if ($userMessage === null) {
            return response()->json(['success' => false, 'message' => 'Mensaje vacío'], 422);
        }

        $history = $this->buildChatHistory($product, $chat, $userMessage);

        try {
            $started = microtime(true);
            $response = $this->chatService->chat($history, $model, $webSearch);
            $latency = (int) round((microtime(true) - $started) * 1000);

            $userMsg = ProductChatMessage::create([
                'chat_id' => $chat->id,
                'role' => 'user',
                'content' => $userMessage,
            ]);

            $assistantMsg = ProductChatMessage::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $response['content'],
                'sources' => $response['sources'] ?? [],
                'web_search_used' => $response['web_search_used'] ?? false,
                'input_tokens' => $response['tokens']['input'] ?? 0,
                'output_tokens' => $response['tokens']['output'] ?? 0,
                'cost' => $response['cost'] ?? 0,
                'model' => $response['model'] ?? $model,
                'latency_ms' => $latency,
            ]);

            $chat->increment('total_cost', $response['cost'] ?? 0);
            $chat->increment('total_tokens', $response['tokens']['total'] ?? 0);
            $chat->increment('messages_count', 2);

            if (! $chat->title) {
                $chat->update(['title' => Str::limit($userMessage, 80)]);
            }

            AiAuditLog::log('chat.send', [
                'resource_type' => 'product_chat',
                'resource_id' => $chat->uid,
                'model' => $response['model'] ?? $model,
                'cost' => $response['cost'] ?? 0,
                'tokens' => $response['tokens']['total'] ?? 0,
                'metadata' => ['product_id' => $product->id, 'web_search' => $webSearch],
            ]);

            return response()->json([
                'success' => true,
                'chat_uid' => $chat->uid,
                'user_message' => $userMessage,
                'user_message_id' => $userMsg->id,
                'message_id' => $assistantMsg->id,
                'content' => $response['content'],
                'sources' => $response['sources'] ?? [],
                'web_search_used' => $response['web_search_used'] ?? false,
                'tokens' => $response['tokens'],
                'cost' => round($response['cost'], 6),
                'total_cost' => round((float) ($freshChat = $chat->fresh())->total_cost, 6),
                'total_tokens' => (int) $freshChat->total_tokens,
                'model' => $response['model'],
                'latency_ms' => $latency,
            ]);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            // Mensaje legible para errores de cuota/rate limit de Gemini
            if (str_contains($msg, 'RESOURCE_EXHAUSTED') || str_contains($msg, 'free_tier')) {
                $msg = 'Cuota de Google Gemini agotada (plan gratuito). Cambia el modelo a GPT-4o Mini o actualiza tu plan en aistudio.google.com';
            } elseif (str_contains($msg, '429')) {
                $msg = 'Límite de peticiones alcanzado. Espera unos segundos e inténtalo de nuevo.';
            }
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function regenerate(RegenerateChatRequest $request, string $uid): JsonResponse
    {
        $product = Product::withDetails()->where('uid', $uid)->firstOrFail();

        $data = $request->validated();

        $chat = ProductChat::where('uid', $data['chat_uid'])
            ->where('product_id', $product->id)
            ->firstOrFail();

        $lastUser = $chat->messages()->where('role', 'user')->orderByDesc('created_at')->first();
        $lastAssistant = $chat->lastAssistantMessage();

        if (! $lastUser) {
            return response()->json(['success' => false, 'message' => 'No hay mensaje previo para regenerar'], 422);
        }

        if ($lastAssistant) {
            $chat->decrement('total_cost', $lastAssistant->cost ?? 0);
            $chat->decrement('total_tokens', $lastAssistant->total_tokens);
            $chat->decrement('messages_count', 1);
            $lastAssistant->delete();
        }

        $history = $this->buildChatHistory($product, $chat->fresh(), null);

        try {
            $started = microtime(true);
            $response = $this->chatService->chat($history, $chat->model, $chat->web_search_enabled);
            $latency = (int) round((microtime(true) - $started) * 1000);

            $assistantMsg = ProductChatMessage::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $response['content'],
                'sources' => $response['sources'] ?? [],
                'web_search_used' => $response['web_search_used'] ?? false,
                'input_tokens' => $response['tokens']['input'] ?? 0,
                'output_tokens' => $response['tokens']['output'] ?? 0,
                'cost' => $response['cost'] ?? 0,
                'model' => $response['model'] ?? $chat->model,
                'latency_ms' => $latency,
            ]);

            $chat->increment('total_cost', $response['cost'] ?? 0);
            $chat->increment('total_tokens', $response['tokens']['total'] ?? 0);
            $chat->increment('messages_count', 1);

            return response()->json([
                'success' => true,
                'message_id' => $assistantMsg->id,
                'content' => $response['content'],
                'sources' => $response['sources'] ?? [],
                'web_search_used' => $response['web_search_used'] ?? false,
                'tokens' => $response['tokens'],
                'cost' => round($response['cost'], 6),
                'total_cost' => round((float) ($freshChat = $chat->fresh())->total_cost, 6),
                'total_tokens' => (int) $freshChat->total_tokens,
                'model' => $response['model'],
                'latency_ms' => $latency,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reset(ResetChatRequest $request, string $uid): JsonResponse
    {
        $product = Product::where('uid', $uid)->firstOrFail();

        $data = $request->validated();

        if (! empty($data['chat_uid'])) {
            ProductChat::where('uid', $data['chat_uid'])
                ->where('product_id', $product->id)
                ->delete();
        }

        return response()->json(['success' => true]);
    }

    public function save(SaveChatRequest $request, string $uid): JsonResponse
    {
        $product = Product::with('supplier')->where('uid', $uid)->firstOrFail();

        $data = $request->validated();

        $chat = ProductChat::where('uid', $data['chat_uid'])
            ->where('product_id', $product->id)
            ->firstOrFail();

        // Hereda los source_attributes del último contenido existente para este producto.
        // Así no se pierden los datos ricos (categoría, deporte, proveedor, etc.) de la sincronización ERP
        // cuando se guarda contenido generado desde el chat.
        $sourceAttributes = AiContent::where('supplier_product_id', $product->id)
            ->whereNotNull('source_attributes')
            ->orderByDesc('created_at')
            ->value('source_attributes');

        $sourceAttributes = is_array($sourceAttributes) && ! empty($sourceAttributes)
            ? $sourceAttributes
            : [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_code' => $product->code,
            ];

        if (! empty($data['brand'])) {
            $sourceAttributes['brand'] = $data['brand'];
        }

        $promptId = $chat->prompt_uid ? Prompt::where('uid', $chat->prompt_uid)->value('id') : null;

        $sources = [];
        if (! empty($data['sources'])) {
            $decoded = json_decode($data['sources'], true);
            if (is_array($decoded)) {
                $sources = array_values(array_filter($decoded, fn ($s) => ! empty($s['url'])));
            }
        }

        $generationMeta = [
            'source'        => 'chat',
            'product_id'    => $product->id,
            'chat_uid'      => $chat->uid,
            'model'         => $chat->model,
            'total_cost'    => (float) $chat->total_cost,
            'total_tokens'  => (int) $chat->total_tokens,
            'generated_at'  => now()->toIso8601String(),
        ];

        // Upsert: reutilizar el único registro del producto si existe, nunca crear duplicados.
        // Solo se sobreescribe si no está en un estado terminal (validado/publicado).
        $aiContent = AiContent::where('supplier_product_id', $product->id)
            ->whereNotIn('status', [AiContent::STATUS_VALIDATED, AiContent::STATUS_PUBLISHED])
            ->orderByDesc('created_at')
            ->first();

        if ($aiContent) {
            // Acumular historial de fuentes: añadir las nuevas sin duplicar
            $history = $aiContent->sources_history ?? [];
            if (! empty($sources)) {
                $history[] = [
                    'saved_at' => now()->toIso8601String(),
                    'sources'  => $sources,
                ];
            }

            $aiContent->update([
                'status'              => AiContent::STATUS_PENDING_VALIDATION,
                'prompt_id'           => $promptId,
                'generated_name'      => ! empty($data['name']) ? $data['name'] : $aiContent->generated_name,
                'long_description'    => $data['content'],
                'source_attributes'   => $sourceAttributes,
                'generation_metadata' => $generationMeta,
                'sources_used'        => ! empty($sources) ? $sources : $aiContent->sources_used,
                'sources_history'     => $history,
                'rejection_reason'    => null,
                'validated_by'        => null,
                'validated_at'        => null,
            ]);
        } else {
            $history = ! empty($sources) ? [['saved_at' => now()->toIso8601String(), 'sources' => $sources]] : [];

            $aiContent = AiContent::create([
                'supplier_id'         => $product->supplier_id,
                'supplier_product_id' => $product->id,
                'erp_reference'       => $product->code,
                'status'              => AiContent::STATUS_PENDING_VALIDATION,
                'prompt_id'           => $promptId,
                'generated_name'      => ! empty($data['name']) ? $data['name'] : null,
                'long_description'    => $data['content'],
                'source_attributes'   => $sourceAttributes,
                'generation_metadata' => $generationMeta,
                'sources_used'        => $sources,
                'sources_history'     => $history,
            ]);
        }

        $chat->update(['saved_content_id' => $aiContent->id]);

        AiAuditLog::log('content.save', [
            'resource_type' => 'ai_content',
            'resource_id' => $aiContent->uid,
            'metadata' => [
                'product_id' => $product->id,
                'chat_uid' => $chat->uid,
                'chars' => strlen($data['content']),
            ],
        ]);

        return response()->json([
            'success' => true,
            'content_id' => $aiContent->id,
            'content_uid' => $aiContent->uid,
            'redirect_url' => route('settings.suppliers.content.show', $aiContent->uid),
            'message' => 'Contenido guardado correctamente',
        ]);
    }

    public function fork(string $uid, string $chatUid): JsonResponse
    {
        $product = Product::where('uid', $uid)->firstOrFail();
        $original = ProductChat::with('conversation')
            ->where('uid', $chatUid)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $fork = ProductChat::create([
            'product_id' => $original->product_id,
            'user_id' => auth()->id(),
            'title' => 'Fork: '.($original->title ?? $original->uid),
            'model' => $original->model,
            'web_search_enabled' => $original->web_search_enabled,
            'prompt_uid' => $original->prompt_uid,
        ]);

        $totalCost = 0;
        $totalTokens = 0;
        $count = 0;

        foreach ($original->conversation as $msg) {
            ProductChatMessage::create([
                'chat_id' => $fork->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'sources' => $msg->sources,
                'web_search_used' => $msg->web_search_used,
                'input_tokens' => $msg->input_tokens,
                'output_tokens' => $msg->output_tokens,
                'cost' => $msg->cost,
                'model' => $msg->model,
                'latency_ms' => $msg->latency_ms,
            ]);
            $totalCost += (float) ($msg->cost ?? 0);
            $totalTokens += (int) ($msg->total_tokens ?? 0);
            $count++;
        }

        $fork->update([
            'total_cost' => $totalCost,
            'total_tokens' => $totalTokens,
            'messages_count' => $count,
        ]);

        AiAuditLog::log('chat.fork', [
            'resource_type' => 'product_chat',
            'resource_id' => $fork->uid,
            'metadata' => [
                'original_uid' => $original->uid,
                'product_id' => $product->id,
                'messages_copied' => $count,
            ],
        ]);

        return response()->json([
            'success' => true,
            'chat_uid' => $fork->uid,
            'title' => $fork->title,
            'redirect_url' => route('settings.suppliers.products.chat', ['uid' => $product->uid, 'chat' => $fork->uid]),
        ]);
    }

    public function destroy(string $uid, string $chatUid): JsonResponse
    {
        $product = Product::where('uid', $uid)->firstOrFail();
        ProductChat::where('uid', $chatUid)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function compare(CompareChatRequest $request, string $uid): JsonResponse
    {
        $product = Product::withDetails()->where('uid', $uid)->firstOrFail();

        $data = $request->validated();

        if ($data['model_a'] === $data['model_b']) {
            return response()->json(['success' => false, 'message' => 'Elige dos modelos distintos'], 422);
        }

        if (! empty($data['prompt_uid'])) {
            $prompt = Prompt::where('uid', $data['prompt_uid'])->first();
            if ($prompt) {
                $variables = $this->chatService->buildProductVariables($product);
                $userMessage = $prompt->render($variables);
            }
        }
        if (empty($userMessage)) {
            $userMessage = trim($data['message'] ?? '');
        }
        if ($userMessage === '') {
            return response()->json(['success' => false, 'message' => 'Escribe un mensaje o elige un prompt'], 422);
        }

        $systemContext = $this->chatService->buildProductContext($product);
        $history = [
            ['role' => 'system', 'content' => $systemContext],
            ['role' => 'user',   'content' => $userMessage],
        ];

        $results = [];
        foreach (['model_a' => $data['model_a'], 'model_b' => $data['model_b']] as $key => $model) {
            try {
                $started = microtime(true);
                $response = $this->chatService->chat($history, $model, (bool) ($data['web_search'] ?? false));
                $latency = (int) round((microtime(true) - $started) * 1000);
                $results[$key] = [
                    'model' => $model,
                    'content' => $response['content'],
                    'sources' => $response['sources'] ?? [],
                    'cost' => $response['cost'] ?? 0,
                    'tokens' => $response['tokens']['total'] ?? 0,
                    'latency_ms' => $latency,
                    'success' => true,
                ];
            } catch (Exception $e) {
                $results[$key] = [
                    'model' => $model,
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'user_message' => $userMessage,
            'results' => $results,
        ]);
    }

    public function export(Request $request, string $uid, string $chatUid): StreamedResponse|Response
    {
        $product = Product::where('uid', $uid)->firstOrFail();
        $chat = ProductChat::with(['conversation', 'product.supplier'])
            ->where('uid', $chatUid)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $format = $request->query('format', 'md');
        $filename = 'chat-'.$chat->product->code.'-'.$chat->uid.'.'.$format;

        if ($format === 'json') {
            return response()->streamDownload(function () use ($chat) {
                echo json_encode([
                    'chat' => [
                        'uid' => $chat->uid,
                        'title' => $chat->title,
                        'product' => $chat->product->name,
                        'model' => $chat->model,
                        'created_at' => $chat->created_at->toIso8601String(),
                        'total_cost' => $chat->total_cost,
                        'total_tokens' => $chat->total_tokens,
                    ],
                    'messages' => $chat->conversation->map(fn ($m) => [
                        'role' => $m->role,
                        'content' => $m->content,
                        'tokens' => $m->total_tokens,
                        'cost' => $m->cost,
                        'model' => $m->model,
                        'sources' => $m->sources,
                        'created_at' => $m->created_at->toIso8601String(),
                    ])->all(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }, $filename, ['Content-Type' => 'application/json']);
        }

        if ($format === 'html') {
            $html = view('supplier::settings.views.products.chat-export', compact('chat'))->render();

            return response()->streamDownload(fn () => print ($html), $filename, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        // Default: Markdown
        $lines = [
            '# '.($chat->title ?? 'Conversación'),
            '',
            '- **Producto:** '.$chat->product->name.' ('.$chat->product->code.')',
            '- **Proveedor:** '.($chat->product->supplier?->label ?? '-'),
            '- **Modelo:** '.$chat->model,
            '- **Fecha:** '.$chat->created_at->format('d/m/Y H:i'),
            '- **Coste:** $'.number_format($chat->total_cost, 6),
            '- **Tokens:** '.number_format($chat->total_tokens),
            '',
            '---',
            '',
        ];

        foreach ($chat->conversation as $msg) {
            $lines[] = '## '.($msg->role === 'user' ? '👤 Usuario' : '🤖 Asistente');
            $lines[] = '';
            $lines[] = $msg->content;
            if (! empty($msg->sources)) {
                $lines[] = '';
                $lines[] = '**Fuentes:**';
                foreach ($msg->sources as $src) {
                    $lines[] = '- ['.($src['title'] ?? $src['url']).']('.$src['url'].')';
                }
            }
            $lines[] = '';
            $lines[] = '_'.$msg->created_at->format('d/m/Y H:i').' · '.$msg->model.'_';
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
        }

        return response()->streamDownload(fn () => print (implode("\n", $lines)), $filename, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    private function resolveChat(Product $product, ?string $chatUid, string $model, bool $webSearch, ?string $promptUid): ProductChat
    {
        if ($chatUid) {
            $chat = ProductChat::where('uid', $chatUid)
                ->where('product_id', $product->id)
                ->first();
            if ($chat) {
                return $chat;
            }
        }

        return ProductChat::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'model' => $model,
            'web_search_enabled' => $webSearch,
            'prompt_uid' => $promptUid,
        ]);
    }

    private function buildUserMessage(Product $product, ProductChat $chat, array $data): ?string
    {
        $isFirstMessage = (int) ($chat->messages_count ?? 0) === 0;

        if ($isFirstMessage && ! empty($data['prompt_uid'])) {
            $prompt = Prompt::where('uid', $data['prompt_uid'])->first();
            if ($prompt) {
                $variables = $this->chatService->buildProductVariables($product);
                $rendered = $prompt->render($variables);
                if (trim($rendered) !== '') {
                    return $rendered;
                }
            }
        }

        $message = trim($data['message'] ?? '');

        return $message === '' ? null : $message;
    }

    private function buildChatHistory(Product $product, ProductChat $chat, ?string $newUserMessage): array
    {
        $systemContext = $this->chatService->buildProductContext($product);
        $history = [['role' => 'system', 'content' => $systemContext]];

        foreach ($chat->conversation as $msg) {
            $history[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        if ($newUserMessage !== null) {
            $history[] = ['role' => 'user', 'content' => $newUserMessage];
        }

        return $history;
    }
}
