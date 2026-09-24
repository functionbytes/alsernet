<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Nwidart\Modules\Facades\Module;
use Spatie\Activitylog\Models\Activity;

/**
 * Lectura de la auditoría de acciones contra la tienda (pieza 40): lo que
 * registran OpsmapAuditStoreWrite y los controladores que ya se auditaban
 * solos (vale de compensación), todo en el log 'helpdeskprestashop'.
 */
class OpsmapAuditService
{
    public const LOG_NAME = 'helpdeskprestashop';

    /**
     * Texto e icono por acción. {order}, {cart}, {address}, {code},
     * {amount}, {tracking}, {state} se sustituyen con los datos guardados.
     * El texto entre ** se pinta en negrita (referencia del objeto). El
     * tercer elemento es la etiqueta corta del filtro por acción.
     */
    private const ACTIONS = [
        'ps.voucher.created' => ['Creó el vale **{code}** de {amount}', 'fa-tag', 'Vale de compensación'],
        'ps.orders.status' => ['Cambió el estado del pedido **#{order}** a «{state}»', 'fa-arrows-rotate', 'Cambio de estado del pedido'],
        'ps.orders.tracking' => ['Asignó el seguimiento **{tracking}** al pedido **#{order}**', 'fa-truck', 'Seguimiento del pedido'],
        'ps.orders.note' => ['Añadió una nota al pedido **#{order}**', 'fa-note-sticky', 'Nota en el pedido'],
        'ps.orders.return' => ['Inició una devolución del pedido **#{order}**', 'fa-rotate-left', 'Devolución del pedido'],
        'ps.orders.address' => ['Cambió la dirección de envío del pedido **#{order}**', 'fa-location-dot', 'Dirección del pedido'],
        'ps.orders.address.invoice' => ['Cambió la dirección de facturación del pedido **#{order}**', 'fa-location-dot', 'Dirección de facturación del pedido'],
        'ps.orders.email' => ['Reenvió un correo del pedido **#{order}**', 'fa-envelope', 'Correo del pedido'],
        'ps.cart.address' => ['Cambió la dirección del carrito **CART-#{cart}**', 'fa-location-dot', 'Dirección del carrito'],
        'ps.cart.products.add' => ['Añadió un producto al carrito **CART-#{cart}**', 'fa-cart-shopping', 'Producto añadido al carrito'],
        'ps.cart.products.remove' => ['Quitó un producto del carrito **CART-#{cart}**', 'fa-cart-shopping', 'Producto quitado del carrito'],
        'ps.cart.products.quantity' => ['Cambió cantidades del carrito **CART-#{cart}**', 'fa-cart-shopping', 'Cantidades del carrito'],
        'ps.cart.voucher' => ['Aplicó el cupón **{code}** al carrito **CART-#{cart}**', 'fa-ticket', 'Cupón aplicado al carrito'],
        'ps.cart.voucher.remove' => ['Quitó el cupón **{code}** del carrito **CART-#{cart}**', 'fa-ticket', 'Cupón quitado del carrito'],
        'ps.addresses.store' => ['Creó una dirección del cliente', 'fa-address-book', 'Dirección creada'],
        'ps.addresses.update' => ['Editó la dirección **#{address}** del cliente', 'fa-address-book', 'Dirección editada'],
        'ps.orders.status.cancel' => ['Anuló el pedido **#{order}** («{state}»)', 'fa-ban', 'Cambio de estado del pedido'],
        'ps.orders.address.both' => ['Cambió las direcciones de envío y facturación del pedido **#{order}**', 'fa-location-dot', 'Dirección del pedido'],

        // Acciones que su controlador ya registra con su propia descripción
        // (la auditoría genérica no las duplica): mismo tono de frase.
        'ps.address.created' => ['Creó una dirección del cliente', 'fa-address-book', 'Dirección creada'],
        'ps.address.updated' => ['Editó la dirección **#{address}** del cliente', 'fa-address-book', 'Dirección editada'],
        'ps.voucher.edited' => ['Editó el vale **{code}**', 'fa-tag', 'Vale editado'],
        'ps.voucher.duplicated' => ['Duplicó un vale como **{code}**', 'fa-tag', 'Vale duplicado'],
        'ps.refund.issued' => ['Hizo un reembolso de {amount} del pedido **#{order}**', 'fa-money-bill-wave', 'Reembolso'],
        'ps.rma.state_changed' => ['Cambió la devolución **#{rma}** a «{state}»', 'fa-rotate-left', 'Estado de la devolución'],
        'ps.ship_claim' => ['Abrió una reclamación de transporte del pedido **#{order}**', 'fa-truck', 'Reclamación de transporte'],
        'ps.catalog.stock_alert' => ['Apuntó al cliente al aviso de stock del producto **#{product}**', 'fa-bell', 'Aviso de stock'],
        'ps.orderedit.reorder' => ['Creó el carrito **CART-#{cart}** repitiendo el pedido **#{order}**', 'fa-cart-plus', 'Repetir pedido'],
        'ps.cart.converted' => ['Convirtió el carrito **CART-#{cart}** en el pedido **#{order}**', 'fa-cart-arrow-down', 'Carrito convertido en pedido'],
        'ps.cart.emptied' => ['Vació el carrito **CART-#{cart}**', 'fa-cart-shopping', 'Carrito vaciado'],
        'ps.account.updated' => ['Editó la ficha del cliente en la tienda', 'fa-user-pen', 'Ficha del cliente'],
        'ps.account.group_changed' => ['Cambió el grupo del cliente en la tienda', 'fa-users', 'Grupo del cliente'],
        'ps.account.password_reset_sent' => ['Envió al cliente el enlace para restablecer la contraseña', 'fa-key', 'Restablecer contraseña'],
        'ps.account.gdpr_exported' => ['Exportó los datos personales del cliente (RGPD)', 'fa-file-export', 'Exportación RGPD'],
        'ps.account.erasure_requested' => ['Pidió el borrado de la cuenta del cliente (RGPD)', 'fa-user-slash', 'Borrado de cuenta'],
    ];

    public function baseQuery(int $days, ?int $agentId = null, ?string $action = null, ?string $search = null): Builder
    {
        $query = $this->periodQuery($days);

        if ($agentId) {
            $query->where('causer_type', (new User)->getMorphClass())->where('causer_id', $agentId);
        }

        if ($action) {
            $query->where('description', $action);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $digits = ltrim($search, '#');

            $userIds = User::query()
                ->where(fn ($q) => $q->where('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereRaw("CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, '')) LIKE ?", [$like]))
                ->limit(200)
                ->pluck('id');
            $customerIds = Customer::query()->where('name', 'like', $like)->orWhere('email', 'like', $like)->limit(500)->pluck('id');

            $query->where(function (Builder $q) use ($like, $digits, $userIds, $customerIds) {
                $q->where('properties', 'like', $like);
                if (ctype_digit($digits)) {
                    $q->orWhere('properties', 'like', '%'.$digits.'%');
                }
                if ($userIds->isNotEmpty()) {
                    $q->orWhere(fn ($q) => $q->where('causer_type', (new User)->getMorphClass())->whereIn('causer_id', $userIds));
                }
                if ($customerIds->isNotEmpty()) {
                    $q->orWhere(fn ($q) => $q->where('subject_type', (new Customer)->getMorphClass())->whereIn('subject_id', $customerIds));
                }
            });
        }

        return $query->latest('created_at')->latest('id');
    }

    /**
     * Log 'helpdeskprestashop' del periodo sin lo que no es una acción contra
     * la tienda (operación de opslog, descargas de documentos).
     */
    private function periodQuery(int $days): Builder
    {
        $query = Activity::query()
            ->where('log_name', self::LOG_NAME)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay());

        foreach ((array) config('helpdeskprestashop.ext.opsmap.audit_hidden_descriptions', []) as $pattern) {
            $pattern = (string) $pattern;
            if (str_ends_with($pattern, '*')) {
                $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], substr($pattern, 0, -1));
                $query->where('description', 'not like', $prefix.'%');
            } elseif ($pattern !== '') {
                $query->where('description', '!=', $pattern);
            }
        }

        return $query;
    }

    public function paginate(int $days, ?int $agentId, ?string $action, ?string $search, int $perPage = 40): LengthAwarePaginator
    {
        $page = $this->baseQuery($days, $agentId, $action, $search)->paginate($perPage)->withQueryString();

        $page->setCollection($this->present($page->getCollection()));

        return $page;
    }

    /**
     * Filas listas para pintar: texto de la acción, icono, agente, cliente,
     * conversación (con su ticket si lo tiene) y fecha relativa.
     *
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    public function present(Collection $activities): Collection
    {
        $userIds = $activities->where('causer_type', (new User)->getMorphClass())->pluck('causer_id')->filter()->unique();
        $users = $this->userNames($userIds);

        $customerIds = $activities->where('subject_type', (new Customer)->getMorphClass())->pluck('subject_id')->filter()->unique();
        $customers = $customerIds->isEmpty() ? collect() : Customer::query()->whereIn('id', $customerIds)->get(['id', 'name', 'email'])->keyBy('id');

        $conversationIds = $activities->map(fn ($a) => (int) ($a->properties['conversation_id'] ?? 0))->filter()->unique();
        $tickets = $this->ticketNumbers($conversationIds);

        return $activities->map(function (Activity $activity) use ($users, $customers, $tickets) {
            $props = $activity->properties?->toArray() ?? [];
            [$text, $icon] = $this->describe((string) $activity->description, $props);
            $customer = $customers->get($activity->subject_id);
            $conversationId = (int) ($props['conversation_id'] ?? 0) ?: null;

            return [
                'id' => $activity->id,
                'action' => (string) $activity->description,
                'html' => $this->toHtml($text),
                'text' => str_replace('**', '', $text),
                'icon' => $icon,
                'muted' => $this->isMuted((string) $activity->description, $props),
                'agent' => $users->get($activity->causer_id) ?? 'Sistema',
                'customer' => $customer ? ($customer->name ?: $customer->email) : null,
                'customer_url' => $customer && Route::has('contacts.show') ? route('contacts.show', $customer->id) : null,
                'conversation_id' => $conversationId,
                'conversation_label' => $conversationId ? ($tickets[$conversationId] ?? 'Conv. #'.$conversationId) : null,
                'conversation_url' => $conversationId && Route::has('manager.helpdesk.conversations.show') ? route('manager.helpdesk.conversations.show', $conversationId) : null,
                'when' => $this->when($activity->created_at),
                'at' => $activity->created_at?->format('Y-m-d H:i:s'),
                'route' => $props['route'] ?? null,
                'details' => array_filter(['params' => $props['params'] ?? null, 'input' => $props['input'] ?? null, 'fields' => $props['fields'] ?? null]),
            ];
        });
    }

    /**
     * @return array<string, string> acción => etiqueta, solo las presentes en el periodo
     */
    public function actionOptions(int $days): array
    {
        return $this->periodQuery($days)
            ->distinct()
            ->orderBy('description')
            ->pluck('description')
            ->mapWithKeys(fn ($d) => [$d => $this->actionLabel((string) $d)])
            ->all();
    }

    /**
     * @return array<int, string> id => nombre, solo agentes con acciones en el periodo
     */
    public function agentOptions(int $days): array
    {
        $ids = $this->periodQuery($days)
            ->where('causer_type', (new User)->getMorphClass())
            ->distinct()
            ->pluck('causer_id');

        return $this->userNames($ids)->sort(fn ($a, $b) => strcasecmp($a, $b))->all();
    }

    /**
     * User no tiene columna name: el nombre visible es fullName()
     * (firstname + lastname sin duplicar), o el email si está vacío.
     *
     * @param  Collection<int, mixed>  $ids
     * @return Collection<int, string>
     */
    private function userNames(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'firstname', 'lastname', 'email'])
            ->mapWithKeys(fn (User $u) => [$u->id => $u->fullName() ?: (string) $u->email]);
    }

    /**
     * Etiqueta corta para el desplegable de acciones ("Cambió el estado del pedido").
     */
    public function actionLabel(string $description): string
    {
        return self::ACTIONS[$description][2]
            ?? Str::of($description)->after('ps.')->replaceStart('ext.', '')->replace(['.', '_', '-'], ' ')->ucfirst()->toString();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function describe(string $description, array $props): array
    {
        $params = (array) ($props['params'] ?? []);
        $input = (array) ($props['input'] ?? []);

        $key = $description;
        if ($description === 'ps.orders.address' && in_array($input['type'] ?? null, ['invoice', 'both'], true)) {
            $key = 'ps.orders.address.'.$input['type'];
        } elseif ($description === 'ps.orders.status' && $this->isCancelState($input['state_name'] ?? null)) {
            $key = 'ps.orders.status.cancel';
        }

        [$template, $icon] = self::ACTIONS[$key] ?? ['Acción en la tienda: '.$this->actionLabel($description), 'fa-store'];

        // Las filas de la auditoría genérica traen los ids en params (ruta)
        // e input (cuerpo); las de controladores que se auditan solos, como
        // propiedades sueltas (order_id, cart_id…).
        $amount = $props['amount'] ?? $input['amount'] ?? null;
        $state = $input['state_name'] ?? $props['state_name'] ?? null;
        $stateId = $input['state_id'] ?? $props['state_id'] ?? null;
        $values = [
            '{order}' => $params['order'] ?? $props['order_id'] ?? '—',
            '{cart}' => $params['cart'] ?? $props['cart_id'] ?? '—',
            '{address}' => $params['address'] ?? $props['address_id'] ?? '—',
            '{product}' => $params['product'] ?? $props['product_id'] ?? '—',
            '{rma}' => $params['rma'] ?? $props['return_id'] ?? '—',
            '{code}' => $props['code'] ?? $input['code'] ?? '—',
            '{amount}' => is_numeric($amount) ? number_format((float) $amount, 2, ',', '.').' €' : '—',
            '{tracking}' => $input['tracking_number'] ?? $props['tracking_number'] ?? '—',
            '{state}' => $state ?? ($stateId !== null ? 'estado '.$stateId : '—'),
        ];
        $values = array_map(fn ($v) => is_scalar($v) ? (string) $v : '—', $values);

        return [strtr($template, $values), $icon];
    }

    /**
     * Anulaciones y cancelaciones se pintan en gris (nunca en rojo): son las
     * acciones que el supervisor busca primero.
     */
    private function isMuted(string $description, array $props): bool
    {
        if (Str::contains($description, ['cancel', 'anul'])) {
            return true;
        }

        return $this->isCancelState($props['input']['state_name'] ?? $props['state_name'] ?? null);
    }

    private function isCancelState(mixed $stateName): bool
    {
        $state = Str::lower(Str::ascii((string) $stateName));

        return $state !== '' && Str::contains($state, ['cancel', 'anulad']);
    }

    private function toHtml(string $text): string
    {
        $escaped = e($text);

        return (string) preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', $escaped);
    }

    private function when(?Carbon $at): string
    {
        if ($at === null) {
            return '';
        }

        $at = $at->copy()->timezone(config('app.timezone'));

        return match (true) {
            $at->isToday() => 'hoy '.$at->format('H:i'),
            $at->isYesterday() => 'ayer '.$at->format('H:i'),
            default => $at->locale('es')->translatedFormat('j M').' '.$at->format('H:i'),
        };
    }

    /**
     * @param  Collection<int, int>  $conversationIds
     * @return array<int, string> conversation_id => número de ticket
     */
    private function ticketNumbers(Collection $conversationIds): array
    {
        if ($conversationIds->isEmpty() || ! class_exists(Ticket::class) || ! Module::find('HelpdeskTickets')?->isEnabled()) {
            return [];
        }

        try {
            return Ticket::query()
                ->whereIn('conversation_id', $conversationIds->all())
                ->orderBy('id')
                ->pluck('ticket_number', 'conversation_id')
                ->map(fn ($n) => (string) $n)
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
