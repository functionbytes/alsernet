<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\CustomerCommerceSyncService;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\Webhooks\FacebookMessageProcessor;
use Modules\Helpdesk\Services\Webhooks\InstagramMessageProcessor;
use Modules\Helpdesk\Services\Webhooks\WhatsAppMessageProcessor;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Banco de pruebas del Helpdesk omnicanal.
 *
 * Permite buscar clientes que existen en PrestaShop (y en gestion/ERP),
 * simular un mensaje entrante por cada canal (WhatsApp/Facebook/Instagram)
 * ejecutando el pipeline REAL de procesamiento, y enlazar el cliente del
 * helpdesk con su id de PrestaShop y de gestion para que el contexto
 * (pedidos) aparezca en la conversacion.
 */
class HelpdeskSimulatorController extends Controller
{
    /** Nombre de la base de datos de PrestaShop (lectura para el buscador). */
    private function psDb(): string
    {
        return (string) config('helpdeskprestashop.ps_db', 'alvarez_cristia');
    }

    public function index(): View
    {
        $this->authorize('viewAny', Conversation::class);

        return view('helpdesk::helpdesk.simulator.index');
    }

    /**
     * Re-sincroniza (forzado) los vínculos de e-commerce del cliente de una
     * conversación: redetecta PrestaShop + gestión por email y refresca la caché
     * de contexto para que pedidos e integraciones se actualicen.
     */
    public function resyncConversation(Conversation $conversation, CustomerCommerceSyncService $sync): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $customer = $conversation->customer;

        if (! $customer) {
            return response()->json(['ok' => false, 'error' => 'La conversación no tiene cliente asociado.'], 422);
        }

        $result = $sync->sync($customer, force: true);

        return response()->json([
            'ok' => true,
            'linked' => $result['linked'],
            'prestashop_id' => $result['prestashop_id'],
            'gestion_id' => $result['gestion_id'],
            'message' => $this->syncMessage($result),
        ]);
    }

    /**
     * @param  array{prestashop_id: ?int, gestion_id: ?int, linked: bool}  $result
     */
    private function syncMessage(array $result): string
    {
        if (! $result['linked']) {
            return 'El cliente no se encontró en PrestaShop ni en gestión.';
        }

        if ($result['prestashop_id'] && $result['gestion_id']) {
            return 'Cliente sincronizado con PrestaShop y gestión.';
        }

        if ($result['prestashop_id']) {
            return 'Cliente sincronizado con PrestaShop.';
        }

        return 'Cliente sincronizado con gestión (ERP).';
    }

    /**
     * Busca clientes en PrestaShop por email o nombre y anota si estan en gestion (ERP).
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $db = $this->psDb();
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
                  WHERE c.deleted = 0 AND (c.email LIKE ? OR CONCAT(c.firstname, ' ', c.lastname) LIKE ?)
               ORDER BY ps_orders DESC
                  LIMIT 15",
                [$like, $like]
            );
        } catch (\Throwable $e) {
            Log::warning('Simulator: fallo al buscar clientes PrestaShop', ['error' => $e->getMessage()]);

            return response()->json(['data' => [], 'error' => 'No se pudo consultar PrestaShop.'], 200);
        }

        $data = array_map(fn ($r) => [
            'prestashop_id' => (int) $r->id_customer,
            'email' => $r->email,
            'name' => trim($r->firstname.' '.$r->lastname),
            'ps_orders' => (int) $r->ps_orders,
            'gestion_id' => $r->gestion_id ? (int) $r->gestion_id : null,
            'in_prestashop' => true,
            'in_gestion' => $r->gestion_id ? true : false,
        ], $rows);

        return response()->json(['data' => $data]);
    }

    /**
     * Previsualiza los pedidos de PrestaShop de un cliente (accion customer.orders).
     */
    public function orders(Request $request, PrestashopContextService $ps): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $email = trim((string) $request->input('email', ''));
        $psId = $request->filled('prestashop_id') ? (int) $request->input('prestashop_id') : null;

        if ($email === '' && ! $psId) {
            return response()->json(['data' => []]);
        }

        $result = $ps->getCustomerOrders($email, $psId, 10, 1);
        $list = $result['orders'] ?? ($result['data'] ?? []);

        return response()->json([
            'total' => $result['pagination']['total'] ?? count($list),
            'data' => $list,
        ]);
    }

    /**
     * Simula un mensaje entrante por el canal indicado ejecutando el pipeline real,
     * y enlaza el cliente del helpdesk con PrestaShop/gestion para el contexto.
     */
    public function simulate(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $data = $request->validate([
            'channel' => ['required', 'in:whatsapp,facebook,instagram'],
            'message' => ['required', 'string', 'max:2000'],
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'sender_id' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'prestashop_id' => ['nullable', 'integer'],
            'gestion_id' => ['nullable', 'integer'],
        ]);

        $channel = $data['channel'];
        $name = $data['name'] ?? 'Cliente Simulado';
        $message = $data['message'];

        try {
            $item = match ($channel) {
                'whatsapp' => $this->runWhatsApp($data['phone'] ?? '34600000001', $name, $message),
                'facebook' => $this->runMeta('facebook', $data['sender_id'] ?? ('PSID_'.uniqid()), $message),
                'instagram' => $this->runMeta('instagram', $data['sender_id'] ?? ('IG_'.uniqid()), $message),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Simulator: fallo al simular inbound', ['channel' => $channel, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        if (! $item) {
            return response()->json(['ok' => false, 'error' => 'El mensaje se omitio (duplicado o payload invalido).'], 422);
        }

        $item->load('conversation.customer');
        $conversation = $item->conversation;
        $customer = $conversation->customer;

        $this->linkCustomer($customer, $data['email'] ?? null, $data['prestashop_id'] ?? null, $data['gestion_id'] ?? null);

        return response()->json([
            'ok' => true,
            'channel' => $channel,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'item_id' => $item->id,
            'subject' => $conversation->subject,
            'url' => route('manager.helpdesk.conversations.index').'?selected='.$conversation->id,
        ]);
    }

    private function runWhatsApp(string $phone, string $name, string $message): mixed
    {
        $service = app(WhatsAppBusinessService::class);
        $processor = app(WhatsAppMessageProcessor::class);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WHATSAPP_BUSINESS_ACCOUNT_ID',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '+15551234567', 'phone_number_id' => 'PHONE_NUMBER_ID'],
                        'contacts' => [['profile' => ['name' => $name], 'wa_id' => $phone]],
                        'messages' => [[
                            'from' => $phone,
                            'id' => 'wamid.sim_'.uniqid(),
                            'timestamp' => (string) time(),
                            'type' => 'text',
                            'text' => ['body' => $message],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        foreach ($service->parseWebhookPayload($payload) as $event) {
            if (($event['type'] ?? null) === 'message') {
                return $processor->process($event);
            }
        }

        return null;
    }

    private function runMeta(string $platform, string $senderId, string $message): mixed
    {
        [$service, $processor] = $platform === 'facebook'
            ? [app(FacebookMessengerService::class), app(FacebookMessageProcessor::class)]
            : [app(InstagramService::class), app(InstagramMessageProcessor::class)];

        $payload = $platform === 'facebook'
            ? [
                'object' => 'page',
                'entry' => [['id' => 'PAGE_ID', 'time' => time(), 'messaging' => [[
                    'sender' => ['id' => $senderId], 'recipient' => ['id' => 'PAGE_ID'], 'timestamp' => time(),
                    'message' => ['mid' => 'm_sim_'.uniqid(), 'text' => $message],
                ]]]],
            ]
            : [
                'object' => 'instagram',
                'entry' => [['id' => 'IG_ACCOUNT_ID', 'time' => time(), 'messaging' => [[
                    'sender' => ['id' => $senderId], 'recipient' => ['id' => 'IG_ACCOUNT_ID'], 'timestamp' => time(),
                    'message' => ['mid' => 'ig_sim_'.uniqid(), 'text' => $message],
                ]]]],
            ];

        foreach ($service->parseWebhookPayload($payload) as $event) {
            if (in_array($event['type'] ?? null, ['message', 'story_reply'], true)) {
                return $processor->process($event);
            }
        }

        return null;
    }

    /**
     * Enlaza el cliente del helpdesk con su email PS y sus ids externos (prestashop/erp)
     * para que el panel de contexto recupere pedidos de PrestaShop y de gestion.
     */
    private function linkCustomer(object $customer, ?string $email, ?int $prestashopId, ?int $gestionId): void
    {
        try {
            if ($email && empty($customer->email)) {
                $customer->email = $email;
                $customer->save();
            }

            if ($prestashopId) {
                $customer->linkExternalId('prestashop', (string) $prestashopId, ['linked_via' => 'simulator']);
            }

            if ($gestionId) {
                $customer->linkExternalId('erp', (string) $gestionId, ['linked_via' => 'simulator']);
            }
        } catch (\Throwable $e) {
            Log::warning('Simulator: no se pudo enlazar el cliente', ['error' => $e->getMessage()]);
        }
    }
}
