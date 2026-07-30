<?php

namespace Modules\Helpdesk\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Http\Requests\Public\InjectSimulatorMessageRequest;
use Modules\Helpdesk\Http\Requests\Public\SendSimulatorMessageRequest;
use Modules\Helpdesk\Http\Requests\Public\StartSimulatorRequest;
use Modules\Helpdesk\Http\Requests\Public\UploadSimulatorAttachmentRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Services\Public\PublicSimulatorService;
use Throwable;

/**
 * PUBLIC (no auth) multi-channel conversation simulator.
 *
 * A visitor picks a channel (WhatsApp / Facebook / Instagram / Web), enters an
 * identity and a message, and has a real-time two-way conversation that reaches
 * the real agent inbox via the real inbound pipeline. Agent replies stream back
 * over the public broadcast channel (Reverb) with an AJAX polling fallback.
 *
 * The whole feature is gated by config('helpdesk.simulator_public_enabled') —
 * disabled in production. Per-conversation writes require the opaque token issued
 * on start. Outbound sends to real channel APIs are suppressed for simulated
 * conversations (see SimulatorOutboundMessageService).
 */
class PublicSimulatorController extends Controller
{
    public function __construct(private readonly PublicSimulatorService $simulator)
    {
        abort_unless((bool) config('helpdesk.simulator_public_enabled'), 404);
    }

    public function sessions(Request $request): JsonResponse
    {
        // Solo las sesiones creadas por ESTE navegador: el listado devuelve el
        // sim_token de cada sesión (la credencial que la protege), así que no
        // debe exponer las de otros visitantes (IDOR).
        $owner = $this->simulatorOwner($request);

        $conversations = Conversation::query()
            ->whereRaw("JSON_EXTRACT(metadata, '$.is_simulator') = true")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.sim_owner')) = ?", [$owner])
            ->with([
                'status',
                'customer:id,name,email',
                'items' => fn ($q) => $q->orderByDesc('id')->limit(1),
            ])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $data = $conversations->map(function (Conversation $c) {
            $meta = $c->metadata ?? [];
            $token = data_get($meta, 'sim_token');
            $channel = data_get($meta, 'sim_channel', 'web');
            $last = $c->items->first();

            return [
                'id' => $c->id,
                'token' => $token,
                'channel' => $channel,
                'customer_name' => $c->customer?->name ?? data_get($meta, 'sim_name', 'Cliente simulado'),
                'last_message' => $last ? mb_substr((string) ($last->body ?: '📎 Archivo'), 0, 80) : '',
                'last_message_at' => $c->updated_at?->toIso8601String(),
                'created_at' => $c->created_at?->toIso8601String(),
                'is_closed' => $c->status?->is_closed ?? false,
                'status_name' => $c->status?->name ?? 'Abierta',
                'message_count' => $c->items_count ?? 0,
            ];
        });

        return response()->json(['ok' => true, 'data' => $data]);
    }

    public function index(): View
    {
        return view('helpdesk::helpdesk.public-simulator.index', [
            'channels' => PublicSimulatorService::CHANNELS,
            'reverb' => $this->reverbConfig(),
        ]);
    }

    public function start(StartSimulatorRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->simulator->startSession(
                $data['channel'],
                $data['name'] ?? null,
                $data['identifier'] ?? null,
                $data['message'],
                [
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'prestashop_id' => $data['prestashop_id'] ?? null,
                    'gestion_id' => $data['gestion_id'] ?? null,
                ],
                $data['simulated_datetime'] ?? null,
                $this->simulatorOwner($request),
            );
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true] + $result);
    }

    public function inbound(Conversation $conversation, SendSimulatorMessageRequest $request): JsonResponse
    {
        $this->authorizeToken($conversation, $request->input('token'));

        try {
            $item = $this->simulator->injectInbound($conversation, $request->validated()['message']);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'item' => $this->simulator->present($item)]);
    }

    public function messages(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorizeToken($conversation, $request->query('token'));

        $afterId = $request->integer('after_id') ?: null;

        return response()->json([
            'ok' => true,
            'messages' => $this->simulator->messagesSince($conversation, $afterId),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        // El autocompletado de clientes devuelve PII (email/nombre/pedidos) de
        // producción; exige que este navegador ya tenga alguna sesión de
        // simulador para no ser un oráculo de enumeración totalmente anónimo.
        abort_unless($this->ownerHasSession($request), 403);

        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $db = (string) config('helpdeskprestashop.ps_db', 'alvarez_cristia');
        $like = '%'.$q.'%';

        try {
            $rows = DB::connection('mysql')->select(
                "SELECT c.id_customer, c.email, c.firstname, c.lastname,
                        (SELECT COUNT(*) FROM `{$db}`.aalv_orders o WHERE o.id_customer = c.id_customer) AS ps_orders,
                        (SELECT MAX(sp.id_cliente_gestion)
                           FROM `{$db}`.aalv_orders o2
                           JOIN `{$db}`.seguimiento_pedidos sp ON sp.id_internet = o2.id_order AND sp.id_cliente_gestion > 0
                          WHERE o2.id_customer = c.id_customer) AS gestion_id
                   FROM `{$db}`.aalv_customer c
                  WHERE c.deleted = 0 AND (c.email LIKE ? OR CONCAT(c.firstname, ' ', c.lastname) LIKE ? OR c.email LIKE ?)
               ORDER BY ps_orders DESC
                  LIMIT 8",
                [$like, $like, $like]
            );
        } catch (Throwable $e) {
            return response()->json(['data' => [], 'error' => 'No disponible.']);
        }

        $data = array_map(fn ($r) => [
            'prestashop_id' => (int) $r->id_customer,
            'email' => $r->email,
            'name' => trim($r->firstname.' '.$r->lastname),
            'ps_orders' => (int) $r->ps_orders,
            'gestion_id' => $r->gestion_id ? (int) $r->gestion_id : null,
        ], $rows);

        return response()->json(['data' => $data]);
    }

    public function attachment(Conversation $conversation, UploadSimulatorAttachmentRequest $request): JsonResponse
    {
        $this->authorizeToken($conversation, $request->input('token'));

        $file = $request->file('file');
        $path = $file->store('helpdesk/attachments', 'public');
        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $url = asset('storage/'.$path);

        $attachmentUrls = [[
            'name' => $file->getClientOriginalName(),
            'url' => $url,
            'size' => $file->getSize(),
            'mime_type' => $mime,
            'mime' => $mime,
            'type' => $this->mediaTypeFromMime($mime),
            'path' => $path,
        ]];

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $conversation->customer_id,
            'type' => 'message',
            'body' => '',
            'attachment_urls' => $attachmentUrls,
            'metadata' => ['platform' => data_get($conversation->metadata, 'sim_channel', 'web'), 'simulator' => true],
            'external_id' => 'sim_att_'.uniqid('', true),
        ]);

        $conversation->touch('last_message_at');

        broadcast(new ConversationMessageCreated($item));

        return response()->json(['ok' => true, 'item' => $this->simulator->present($item)]);
    }

    public function inject(Conversation $conversation, InjectSimulatorMessageRequest $request): JsonResponse
    {
        $this->authorizeToken($conversation, $request->input('token'));

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'author_id' => null,
            'type' => 'message',
            'body' => $request->validated('message'),
            'metadata' => ['injected_as_agent' => true, 'simulator' => true],
            'external_id' => 'sim_inj_'.uniqid('', true),
        ]);

        $conversation->touch('last_message_at');

        broadcast(new ConversationMessageCreated($item));

        return response()->json(['ok' => true, 'item' => $this->simulator->present($item)]);
    }

    public function csat(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorizeToken($conversation, $request->query('token'));

        $rating = CsatRating::query()
            ->where('conversation_id', $conversation->id)
            ->whereNull('answered_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->first();

        if (! $rating) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok' => true,
            'token' => $rating->survey_token,
            'url' => route('helpdesk.csat.show', $rating->survey_token),
        ]);
    }

    /**
     * Opaque per-browser owner id, kept in the web session. Scopes /sessions so
     * a visitor only ever sees (and gets the tokens of) the simulator sessions
     * their own browser created.
     */
    private function simulatorOwner(Request $request): string
    {
        $owner = $request->session()->get('sim_owner');

        if (! is_string($owner) || $owner === '') {
            $owner = Str::random(40);
            $request->session()->put('sim_owner', $owner);
        }

        return $owner;
    }

    /**
     * True when this browser already owns at least one simulator session — used
     * to gate the customer-lookup PII endpoint behind a real simulator user.
     */
    private function ownerHasSession(Request $request): bool
    {
        $owner = $request->session()->get('sim_owner');

        if (! is_string($owner) || $owner === '') {
            return false;
        }

        return Conversation::query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.sim_owner')) = ?", [$owner])
            ->exists();
    }

    /**
     * A request may only touch a conversation that is a simulator session AND
     * whose stored token matches the supplied one (constant-time comparison).
     */
    private function authorizeToken(Conversation $conversation, ?string $token): void
    {
        $expected = data_get($conversation->metadata, 'sim_token');
        $isSimulated = data_get($conversation->metadata, 'is_simulator') === true;

        abort_unless(
            $isSimulated && is_string($expected) && is_string($token) && hash_equals($expected, $token),
            403,
            'Sesión de simulación no válida.',
        );
    }

    private function mediaTypeFromMime(string $mime): string
    {
        $primary = strtolower(explode('/', explode(';', $mime, 2)[0], 2)[0] ?? '');

        return match ($primary) {
            'image' => 'image',
            'audio' => 'audio',
            'video' => 'video',
            default => 'document',
        };
    }

    /**
     * Reverb connection settings for the browser Echo client. Mirrors the livechat
     * widget's WidgetScriptController::buildConfig().
     *
     * @return array{key: string, host: string, port: int, scheme: string}
     */
    private function reverbConfig(): array
    {
        return [
            'key' => (string) config('broadcasting.connections.reverb.key', ''),
            'host' => (string) config('broadcasting.connections.reverb.options.host', request()->getHost()),
            'port' => (int) config('broadcasting.connections.reverb.options.port', 8080),
            'scheme' => (string) config('broadcasting.connections.reverb.options.scheme', request()->isSecure() ? 'https' : 'http'),
        ];
    }
}
