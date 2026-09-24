<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use App\Helpers\PiiMasker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\OpslogEventsIndexRequest;
use Modules\HelpdeskPrestashop\Services\Ext\OpslogEventStore;

/**
 * Pantalla "Eventos recibidos" (pieza 38): lo que PrestaShop ha mandado al
 * webhook del módulo, filtrable por Procesados/Pendientes, y "Reprocesar"
 * para los que llegaron antes de que el cliente existiese en el helpdesk.
 */
class OpslogWebhooksController extends Controller
{
    public function __construct(
        private readonly OpslogEventStore $store
    ) {}

    public function index(OpslogEventsIndexRequest $request): View
    {
        $available = $this->store->available();
        $status = $request->status();
        $event = $request->eventName();

        $page = $available
            ? $this->store->paginate($status, $event, (int) config('helpdeskprestashop.ext.opslog.events.per_page', 30))
            : null;

        if ($page !== null) {
            $customers = $this->customersFor($page->getCollection());
            $page->through(fn (object $row) => $this->present($row, $customers->get($row->customer_id)));
        }

        return view('helpdeskprestashop::ext.opslog.webhooks', [
            'available' => $available,
            'counts' => $available ? $this->store->counts() : ['all' => 0, 'processed' => 0, 'pending' => 0],
            'events' => $page,
            'status' => $status,
            'event' => $event,
            'eventNames' => array_keys(OpslogEventStore::EVENTS),
            'canReprocess' => $request->user()->can('helpdeskprestashop.ops.events.reprocess'),
            'retentionDays' => (int) config('helpdeskprestashop.ext.opslog.events.retention_days', 30),
        ]);
    }

    public function reprocess(Request $request, int $event): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('helpdeskprestashop.ops.events.reprocess')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para reprocesar eventos.'], 403);
        }

        if (! $this->store->available()) {
            return response()->json(['success' => false, 'message' => 'El registro de eventos no está instalado.'], 404);
        }

        // Dos clics (o dos responsables) a la vez sobre la misma fila verían
        // ambos "pending" y repetirían el evento dos veces: se serializa.
        $lock = Cache::lock('helpdeskprestashop:opslog:reprocess:'.$event, 30);
        if (! $lock->get()) {
            return response()->json(['success' => false, 'message' => 'Este evento ya se está reprocesando.'], 409);
        }

        try {
            $row = $this->store->find($event);
            if ($row === null) {
                return response()->json(['success' => false, 'message' => 'El evento ya no existe.'], 404);
            }

            // Idempotente: uno ya procesado no se repite (sus listeners ya
            // actuaron sobre el cliente; repetirlo duplicaría efectos).
            if ($row->status !== 'pending') {
                return response()->json(['success' => true, 'data' => $this->present($row, $this->customersFor(collect([$row]))->get($row->customer_id)), 'message' => 'Este evento ya estaba procesado.']);
            }

            $outcome = $this->store->reprocess($row);
        } finally {
            $lock->release();
        }

        $customer = $outcome['customer'];

        if (function_exists('activity')) {
            $log = activity('helpdeskprestashop')->causedBy($user);
            if ($customer !== null) {
                $log->performedOn($customer);
            }
            $log->withProperties([
                'event_id' => (int) $row->id,
                'event' => $row->event,
                'subject' => OpslogEventStore::subjectLabel($row),
                'result' => $outcome['status'],
                'replayed' => $outcome['replayed'],
                'not_replayed_reason' => $outcome['skipped'],
            ])->log('ps.ops.event_reprocessed');
        }

        $fresh = $this->store->find((int) $row->id) ?? $row;

        return response()->json([
            'success' => true,
            'data' => $this->present($fresh, $customer),
            'message' => match (true) {
                $outcome['status'] !== 'processed' => 'Sigue sin cliente vinculado: ese cliente de PrestaShop aún no existe en el helpdesk.',
                $outcome['skipped'] === 'superseded' => 'Vinculado al cliente. No se repite: después llegó un evento más reciente del mismo objeto.',
                $outcome['skipped'] === 'stale' => 'Vinculado al cliente. No se repite: el evento es demasiado antiguo para volver a aplicarlo.',
                default => 'Evento reprocesado y vinculado al cliente.',
            },
        ]);
    }

    /**
     * Fila lista para la vista y para la respuesta JSON del reprocesado. El
     * nombre del cliente solo se muestra a quien puede verlo (CustomerPolicy).
     */
    private function present(object $row, ?Customer $customer): array
    {
        $payload = json_decode((string) $row->payload, true) ?: [];
        $customerName = null;
        $customerUrl = null;

        if ($customer !== null && auth()->user()?->can('view', $customer)) {
            $customerName = $customer->name ?: PiiMasker::email($customer->email);
            $customerUrl = route('manager.helpdesk.customers.show', $customer);
        }

        return [
            'id' => (int) $row->id,
            'event' => (string) $row->event,
            'subject' => OpslogEventStore::subjectLabel($row),
            'status' => (string) $row->status,
            'received_at' => Carbon::parse($row->received_at),
            'reprocess_count' => (int) $row->reprocess_count,
            'customer_name' => $customerName,
            'customer_url' => $customerUrl,
            'payload' => $this->maskPayload($payload),
        ];
    }

    /**
     * Clientes de una página de eventos en una sola consulta.
     *
     * @return Collection<int, Customer>
     */
    private function customersFor(Collection $rows): Collection
    {
        $ids = $rows->pluck('customer_id')->filter()->unique()->values();

        return $ids->isEmpty() ? collect() : Customer::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * El detalle es para diagnosticar, no para leer datos personales: el
     * email se enmascara y los nombres se reducen a la inicial.
     */
    private function maskPayload(array $payload): array
    {
        array_walk_recursive($payload, function (&$value, $key) {
            if (! is_string($value)) {
                return;
            }
            if ($key === 'email' || str_contains($value, '@')) {
                $value = PiiMasker::email($value);
            } elseif (in_array($key, ['firstname', 'lastname', 'phone', 'address1', 'address2'], true) && $value !== '') {
                $value = mb_substr($value, 0, 1).'…';
            }
        });

        return $payload;
    }
}
