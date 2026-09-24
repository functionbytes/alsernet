<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsCartUpdated;
use Modules\HelpdeskPrestashop\Events\PsCustomerCreated;
use Modules\HelpdeskPrestashop\Events\PsCustomerUpdated;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;

/**
 * Registro de los eventos que PrestaShop envía al webhook del módulo
 * (pieza 38 "Eventos recibidos").
 *
 * Un evento queda "procesado" cuando se pudo atar a un cliente del helpdesk
 * (o no va de clientes, como los de producto) y "pendiente" cuando traía un
 * cliente de PrestaShop que aquí no existe todavía: los listeners que buscan
 * al cliente por email no hicieron nada con él. Reprocesar vuelve a buscarlo
 * y, si ahora aparece, repite el evento para que esos listeners actúen.
 */
class OpslogEventStore
{
    public const TABLE = 'helpdesk_ps_received_events';

    public const CONNECTION = 'helpdesk';

    /** Nombre de evento del webhook ⇄ clase (mismo mapa que PsEventReceiverController). */
    public const EVENTS = [
        'order.created' => PsOrderCreated::class,
        'order.status_changed' => PsOrderStatusChanged::class,
        'order.return_requested' => PsOrderReturned::class,
        'cart.abandoned' => PsCartAbandoned::class,
        'cart.updated' => PsCartUpdated::class,
        'customer.created' => PsCustomerCreated::class,
        'customer.updated' => PsCustomerUpdated::class,
        'product.price_dropped' => PsPriceDropped::class,
        'product.back_in_stock' => PsBackInStock::class,
    ];

    /**
     * Fila que se está reprocesando: mientras se repite el evento, el listener
     * actualiza esta fila en vez de crear otra.
     */
    public static ?int $replayingId = null;

    private static ?bool $tableExists = null;

    public function available(): bool
    {
        if (self::$tableExists === null) {
            try {
                self::$tableExists = Schema::connection(self::CONNECTION)->hasTable(self::TABLE);
            } catch (\Throwable) {
                self::$tableExists = false;
            }
        }

        return self::$tableExists;
    }

    public function eventName(object $event): ?string
    {
        $name = array_search($event::class, self::EVENTS, true);

        return $name === false ? null : $name;
    }

    /**
     * Guarda (o actualiza, si es un reintento del mismo envío) el evento.
     */
    public function record(string $name, array $payload, ?string $dedupKey): void
    {
        $resolved = $this->resolve($name, $payload);
        $now = now();

        $row = [
            'event' => mb_substr($name, 0, 64),
            'subject_type' => $resolved['subject_type'],
            'subject_id' => $resolved['subject_id'],
            'ps_customer_id' => $resolved['ps_customer_id'],
            'email' => $resolved['email'],
            'customer_id' => $resolved['customer_id'],
            'status' => $resolved['status'],
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            'updated_at' => $now,
        ];

        $table = DB::connection(self::CONNECTION)->table(self::TABLE);

        if ($dedupKey !== null && $dedupKey !== '') {
            $table->updateOrInsert(['dedup_key' => $dedupKey], $row + ['received_at' => $now, 'created_at' => $now]);
        } else {
            $table->insert($row + ['dedup_key' => null, 'received_at' => $now, 'created_at' => $now]);
        }
    }

    /**
     * @return array{subject_type:?string, subject_id:?int, ps_customer_id:?int, email:?string, customer_id:?int, status:string}
     */
    public function resolve(string $name, array $payload): array
    {
        $psCustomerId = $this->intOrNull($payload['customer_id'] ?? ($payload['customer']['id'] ?? null));
        $email = $payload['email'] ?? ($payload['customer']['email'] ?? null);
        $email = is_string($email) && str_contains($email, '@') ? mb_strtolower(trim($email)) : null;

        [$subjectType, $subjectId] = match (true) {
            str_starts_with($name, 'order.') => ['order', $this->intOrNull($payload['order_id'] ?? null)],
            str_starts_with($name, 'cart.') => ['cart', $this->intOrNull($payload['cart_id'] ?? null)],
            str_starts_with($name, 'product.') => ['product', $this->intOrNull($payload['product_id'] ?? null)],
            str_starts_with($name, 'customer.') => ['customer', $psCustomerId],
            default => [null, null],
        };

        // Los eventos de producto no van de ningún cliente: no hay nada que
        // vincular y se dan por procesados al llegar.
        if ($subjectType === 'product') {
            return [
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'ps_customer_id' => null,
                'email' => null,
                'customer_id' => null,
                'status' => 'processed',
            ];
        }

        $customer = $this->findCustomer($email, $psCustomerId);

        return [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'ps_customer_id' => $psCustomerId,
            'email' => $email ? mb_substr($email, 0, 191) : null,
            'customer_id' => $customer?->id,
            'status' => $customer ? 'processed' : 'pending',
        ];
    }

    public function findCustomer(?string $email, ?int $psCustomerId): ?Customer
    {
        if ($psCustomerId) {
            $customer = Customer::findByExternalId('prestashop', (string) $psCustomerId);
            if ($customer) {
                return $customer;
            }
        }

        if ($email) {
            return Customer::query()->where('email', $email)->first();
        }

        return null;
    }

    /**
     * @return array{all:int, processed:int, pending:int}
     */
    public function counts(): array
    {
        $rows = DB::connection(self::CONNECTION)->table(self::TABLE)
            ->selectRaw('status, COUNT(*) AS n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $processed = (int) ($rows['processed'] ?? 0);
        $pending = (int) ($rows['pending'] ?? 0);

        return ['all' => $processed + $pending, 'processed' => $processed, 'pending' => $pending];
    }

    public function paginate(?string $status, ?string $event, int $perPage): LengthAwarePaginator
    {
        return DB::connection(self::CONNECTION)->table(self::TABLE)
            ->when(in_array($status, ['processed', 'pending'], true), fn ($q) => $q->where('status', $status))
            ->when($event !== null && isset(self::EVENTS[$event]), fn ($q) => $q->where('event', $event))
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?object
    {
        return DB::connection(self::CONNECTION)->table(self::TABLE)->where('id', $id)->first();
    }

    /**
     * Vuelve a buscar el cliente de un evento pendiente. Si ahora existe,
     * repite el evento con su email (los listeners actuales buscan por email y
     * order.created no lo trae) y lo marca procesado.
     *
     * No se repite (solo se vincula) cuando repetirlo haría daño:
     *  - 'superseded': ya llegó después otro evento igual del mismo pedido o
     *    carrito. Repetir un order.status_changed viejo aplicaría un estado
     *    antiguo encima del actual (mapeo de estados, flujos salientes).
     *  - 'stale': el evento es más viejo que replay_max_age_hours. Un
     *    cart.abandoned de hace semanas lanzaría hoy un mensaje al cliente.
     *
     * @return array{status:string, customer:?Customer, replayed:bool, skipped:?string}
     */
    public function reprocess(object $row): array
    {
        $payload = json_decode((string) $row->payload, true) ?: [];
        $customer = $this->findCustomer($row->email, $row->ps_customer_id ? (int) $row->ps_customer_id : null);

        $update = [
            'reprocess_count' => (int) $row->reprocess_count + 1,
            'reprocessed_at' => now(),
            'updated_at' => now(),
        ];

        if ($customer === null) {
            DB::connection(self::CONNECTION)->table(self::TABLE)->where('id', $row->id)->update($update);

            return ['status' => 'pending', 'customer' => null, 'replayed' => false, 'skipped' => null];
        }

        $class = self::EVENTS[$row->event] ?? null;
        $replayed = false;
        $skipped = $this->replayBlocker($row);

        if ($class !== null && $skipped === null) {
            if (empty($payload['email']) && $customer->email) {
                $payload['email'] = $customer->email;
            }

            self::$replayingId = (int) $row->id;
            try {
                event(new $class($payload));
                $replayed = true;
            } finally {
                self::$replayingId = null;
            }
        }

        DB::connection(self::CONNECTION)->table(self::TABLE)->where('id', $row->id)->update($update + [
            'customer_id' => $customer->id,
            'status' => 'processed',
        ]);

        return ['status' => 'processed', 'customer' => $customer, 'replayed' => $replayed, 'skipped' => $skipped];
    }

    /**
     * Motivo para NO repetir el evento (null = se puede repetir).
     */
    private function replayBlocker(object $row): ?string
    {
        $maxAgeHours = (int) config('helpdeskprestashop.ext.opslog.events.replay_max_age_hours', 48);
        if ($maxAgeHours > 0 && $row->received_at && now()->subHours($maxAgeHours)->greaterThan(Carbon::parse($row->received_at))) {
            return 'stale';
        }

        if ($row->subject_id !== null && in_array($row->subject_type, ['order', 'cart', 'customer'], true)) {
            $newer = DB::connection(self::CONNECTION)->table(self::TABLE)
                ->where('event', $row->event)
                ->where('subject_type', $row->subject_type)
                ->where('subject_id', $row->subject_id)
                ->where('id', '>', $row->id)
                ->exists();

            if ($newer) {
                return 'superseded';
            }
        }

        return null;
    }

    /**
     * Poda por antigüedad, en lotes para no bloquear la tabla en el webhook.
     */
    public function prune(int $retentionDays, int $batch = 1000): int
    {
        try {
            $ids = DB::connection(self::CONNECTION)->table(self::TABLE)
                ->where('received_at', '<', now()->subDays(max(1, $retentionDays)))
                ->orderBy('id')
                ->limit($batch)
                ->pluck('id');

            return $ids->isEmpty() ? 0 : DB::connection(self::CONNECTION)->table(self::TABLE)->whereIn('id', $ids)->delete();
        } catch (\Throwable $e) {
            Log::debug('OpslogEventStore: poda fallida', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Etiqueta corta del objeto del evento, como en el diseño ("#829611",
     * "CART-#5821", "C114208").
     */
    public static function subjectLabel(object $row): string
    {
        $id = $row->subject_id;

        return match ($row->subject_type) {
            'order' => $id ? '#'.$id : 'Pedido',
            'cart' => $id ? 'CART-#'.$id : 'Carrito',
            'customer' => $id ? 'C'.$id : 'Cliente',
            'product' => $id ? 'Producto #'.$id : 'Producto',
            default => $row->event,
        };
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
