<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Support\ReportsCache;

/**
 * Concrete implementation of the Helpdesk → Tickets bridge.
 * Bound to TicketServiceContract by HelpdeskServiceProvider when
 * helpdesk_tickets_enabled() returns true. The Helpdesk module never
 * imports HelpdeskTickets symbols directly — it always goes through this.
 */
class HelpdeskTicketBridgeService implements TicketServiceContract
{
    /**
     * Prioridades válidas del módulo. NO existe 'medium': la columna es un
     * string libre, así que cualquier valor fuera de esta lista se guarda tal
     * cual y rompe en silencio el orden por prioridad
     * (FIELD(priority,'urgent','high','normal','low') devuelve 0 y el ticket
     * se cuela por delante de los urgentes), el color de la tarjeta del panel
     * y el match de las políticas SLA. Mismo bug que ya se corrigió en los
     * formularios create/edit del panel (ago-2026) y que el modal del inbox
     * seguía arrastrando.
     */
    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    private const DEFAULT_PRIORITY = 'normal';

    public function isAvailable(): bool
    {
        return true;
    }

    public function canCreateTickets(): bool
    {
        return Gate::allows('create', Ticket::class);
    }

    public function createFromConversation(Conversation $conversation, array $payload = []): ?array
    {
        $firstItem = $conversation->items()
            ->whereNull('user_id')
            ->oldest()
            ->first()
            ?? $conversation->items()->oldest()->first();

        $rawTitle = $firstItem ? strip_tags($firstItem->body ?? '') : '';
        $defaultTitle = blank($rawTitle)
            ? "Conversación #{$conversation->id}"
            : Str::limit($rawTitle, 100, '…');

        $description = $payload['description'] ?? ($firstItem?->body ?? '');
        $notifyCustomer = (bool) ($payload['notify_customer'] ?? true);

        // Transacción explícita por dos motivos: el ticket y su primer mensaje
        // son una sola unidad (un ticket sin cuerpo no sirve de nada), y
        // generateTicketNumber() solo puede sostener su SELECT ... FOR UPDATE
        // dentro de una transacción — fuera de ella dos escalados simultáneos
        // leen el mismo último número. Mismo criterio que TicketsCrudController::store().
        $ticket = DB::connection((new Ticket)->getConnectionName())->transaction(
            function () use ($conversation, $payload, $defaultTitle, $description) {
                $categoryId = $payload['category_id'] ?? null;

                $attributes = [
                    'customer_id' => $conversation->customer_id,
                    'conversation_id' => $conversation->id,
                    'subject' => $payload['subject'] ?? $defaultTitle,
                    'description' => $description,
                    'priority' => $this->normalizePriority($payload['priority'] ?? null),
                    'category_id' => $categoryId,
                    'source' => $payload['source'] ?? 'conversation',
                    'assignee_id' => $payload['assignee_id'] ?? $conversation->assignee_id,
                    // El escalado real suele ir a un equipo, no a una persona:
                    // group_id estaba en $fillable pero ninguna vía de la
                    // bandeja lo rellenaba.
                    'group_id' => $payload['group_id'] ?? null,
                ];

                // SLA heredado de la categoría, igual que el alta desde el
                // panel. Sin esto el ticket escalado nacía sin política salvo
                // que hubiera una genérica por canal, y quedaba fuera de los
                // avisos de vencimiento.
                if ($categoryId) {
                    $category = TicketCategory::find($categoryId);

                    if ($category && $category->default_sla_policy_id) {
                        $attributes['sla_policy_id'] = $category->default_sla_policy_id;
                    }
                }

                // Estado inicial. El observer solo lo rellena si hay un estado
                // marcado is_default, y en esta instalación no hay ninguno: los
                // tickets escalados desde la bandeja nacían con status_id NULL
                // y salían en el listado sin etiqueta ni color (comprobado con
                // un escalado real). Mismo fallback que TicketsCrudController::store():
                // el marcado por defecto y, si no hay, el primero del catálogo.
                $attributes['status_id'] = CatalogCacheService::statuses()->firstWhere('is_default', true)?->id
                    ?? CatalogCacheService::statuses()->first()?->id;

                $ticket = Ticket::create($attributes);

                // Primer mensaje VISIBLE con la descripción: es lo que hace
                // TicketsCrudController::store() y lo que espera la ficha del
                // ticket. Antes el único item era una nota interna con el
                // texto "Ticket creado desde conversación #N" y el hilo nacía
                // sin el mensaje del cliente.
                if (! blank($description)) {
                    $ticket->items()->create([
                        'type' => 'message',
                        'user_id' => auth()->id(),
                        'body' => $description,
                        'is_internal' => false,
                    ]);
                }

                $ticket->items()->create([
                    'type' => 'message',
                    'user_id' => auth()->id(),
                    'body' => "Ticket creado desde conversación #{$conversation->id}.",
                    'is_internal' => true,
                ]);

                if ($payload['attach_transcript'] ?? false) {
                    $transcript = $this->buildTranscript($conversation);

                    if (! blank($transcript)) {
                        $ticket->items()->create([
                            'type' => 'message',
                            'user_id' => auth()->id(),
                            'body' => $transcript,
                            'is_internal' => true,
                        ]);
                    }
                }

                return $ticket;
            }
        );

        $this->logEscalationOnConversation($conversation, $ticket);

        // dispatch(), NO broadcast(): broadcast() entrega el evento SOLO al
        // broadcaster, así que los siete listeners de TicketCreated
        // (confirmación al cliente, aviso a agentes, automatizaciones,
        // auto-clasificación IA, auto-asignación...) no llegaban a ejecutarse
        // para los tickets nacidos en el inbox. Como el evento implementa
        // ShouldBroadcast, dispatch() hace ambas cosas.
        TicketCreated::dispatch($ticket, $notifyCustomer);

        // Un ticket escalado desde el inbox también tiene que resolver a su
        // cliente en gestión. Sin esto, el ticket nunca recibía su
        // CustomerErpResolved y las reglas de "El ERP ha respondido" no podían
        // enrutarlo: quedaban solo para los tickets nacidos de un correo.
        // El caso que lo hace necesario es la conversación que NO vino por
        // correo (chat web, WhatsApp) o la que se escaló mucho después de
        // entrar. Es best-effort: si el cliente ya está resuelto no cuesta una
        // consulta al ERP, el propio trabajo lo detecta y solo emite el evento.
        if ($ticket->customer_id
            && function_exists('helpdesk_erp_enabled')
            && helpdesk_erp_enabled()
            && class_exists(LinkCustomerToErpJob::class)) {
            LinkCustomerToErpJob::dispatch($ticket->customer_id, 'ticket', $ticket->id);
        }

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority,
            'url' => route('manager.helpdesk.tickets.show', $ticket),
        ];
    }

    /**
     * Deja constancia del escalado EN LA CONVERSACIÓN.
     *
     * El vínculo era de ida y vuelta a medias: el ticket guarda conversation_id,
     * pero en el hilo no quedaba ni rastro de que se hubiera escalado y el
     * agente solo lo veía si abría el tab lateral. Se usa el mismo tipo de item
     * que el resto de eventos del hilo (type 'activity' + activity_type, como
     * 'assigned' o 'status_changed'), que thread.blade.php ya pinta como
     * píldora de evento sin tocar la vista.
     */
    private function logEscalationOnConversation(Conversation $conversation, Ticket $ticket): void
    {
        try {
            $conversation->items()->create([
                'type' => 'activity',
                'activity_type' => 'ticket_created',
                'activity_data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'url' => route('manager.helpdesk.tickets.show', $ticket),
                ],
                'user_id' => auth()->id(),
                'body' => "Escalado al ticket {$ticket->ticket_number}",
                'is_internal' => true,
            ]);
        } catch (\Throwable $e) {
            // El ticket ya existe: que no se pueda anotar la actividad no puede
            // tumbar el escalado. Se registra y se sigue.
            Log::warning('[HelpdeskTickets] No se pudo anotar el escalado en la conversación', [
                'conversation_id' => $conversation->id,
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Devuelve una prioridad del vocabulario del módulo. Cualquier valor
     * desconocido (el histórico 'medium' del modal del inbox, o lo que llegue
     * de una integración) cae al default en vez de persistirse a ciegas.
     */
    private function normalizePriority(?string $priority): string
    {
        $priority = strtolower(trim((string) $priority));

        return in_array($priority, self::PRIORITIES, true)
            ? $priority
            : self::DEFAULT_PRIORITY;
    }

    /**
     * Transcripción de la conversación para adjuntarla como nota interna del
     * ticket (checkbox "Adjuntar transcripción del chat"). Se construye en
     * servidor a partir de los items reales: el modal solo puede ver los
     * mensajes que el DOM tenga cargados en ese momento.
     */
    private function buildTranscript(Conversation $conversation, int $limit = 100): string
    {
        $items = $conversation->items()
            ->where('is_internal', false)
            ->whereNotNull('body')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->sortBy('created_at');

        if ($items->isEmpty()) {
            return '';
        }

        $customerName = $conversation->customer?->name ?: 'Cliente';

        $lines = $items->map(function ($item) use ($customerName) {
            $author = $item->user_id ? ($item->user?->fullName() ?? 'Agente') : $customerName;
            $when = $item->created_at?->format('d/m/Y H:i') ?? '';
            $body = trim(strip_tags($item->html_body ?: $item->body ?: ''));

            return "[{$when}] {$author}: {$body}";
        })->implode("\n");

        return "Transcripción de la conversación #{$conversation->id}\n\n".$lines;
    }

    /**
     * Create a ticket directly for a customer (no originating conversation).
     * Used by the Contacts 360 panel. Returns the same compact shape as
     * createFromConversation so callers stay decoupled from Ticket internals.
     *
     * @param  array{subject?: string, message?: string, description?: string, priority?: string, category_id?: int|null, assignee_id?: int|null}  $payload
     * @return array{id: int, ticket_number: string, subject: string, status: ?string, priority: ?string, url: string}|null
     */
    public function createForCustomer(Customer $customer, array $payload = []): ?array
    {
        $subject = trim((string) ($payload['subject'] ?? ''));
        $description = $payload['description'] ?? $payload['message'] ?? '';
        $notifyCustomer = (bool) ($payload['notify_customer'] ?? true);

        // Misma transacción, mismo estado inicial y mismo SLA de categoría que
        // createFromConversation(): este camino (panel Contactos 360) arrastraba
        // los mismos dos fallos —ticket sin estado del catálogo y sin evento—
        // porque se escribió como copia reducida del otro.
        $ticket = DB::connection((new Ticket)->getConnectionName())->transaction(
            function () use ($customer, $payload, $subject, $description) {
                $categoryId = $payload['category_id'] ?? null;

                $attributes = [
                    'customer_id' => $customer->id,
                    'subject' => $subject !== '' ? Str::limit($subject, 191, '') : 'Nueva consulta',
                    'description' => $description,
                    'priority' => $this->normalizePriority($payload['priority'] ?? null),
                    'category_id' => $categoryId,
                    'source' => 'contacts',
                    'assignee_id' => $payload['assignee_id'] ?? null,
                    'group_id' => $payload['group_id'] ?? null,
                    'status_id' => CatalogCacheService::statuses()->firstWhere('is_default', true)?->id
                        ?? CatalogCacheService::statuses()->first()?->id,
                ];

                if ($categoryId) {
                    $category = TicketCategory::find($categoryId);

                    if ($category && $category->default_sla_policy_id) {
                        $attributes['sla_policy_id'] = $category->default_sla_policy_id;
                    }
                }

                $ticket = Ticket::create($attributes);

                if (! blank($description)) {
                    $ticket->items()->create([
                        'type' => 'message',
                        'user_id' => auth()->id(),
                        'body' => $description,
                        'is_internal' => false,
                    ]);
                }

                return $ticket;
            }
        );

        // Sin esto, un ticket abierto desde Contactos 360 no avisaba al cliente
        // ni a los agentes, ni pasaba por las automatizaciones. Ver el mismo
        // comentario en createFromConversation().
        TicketCreated::dispatch($ticket, $notifyCustomer);

        $ticket->loadMissing('status');

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status?->name,
            'priority' => $ticket->priority,
            'url' => route('manager.helpdesk.tickets.show', $ticket),
        ];
    }

    public function getCustomerTickets(Customer $customer, int $limit = 5): Collection
    {
        // La vista del panel accede a assignee y category por cada fila.
        return Ticket::query()
            ->where('customer_id', $customer->id)
            ->with(['status', 'category', 'assignee'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getConversationTickets(Conversation $conversation): Collection
    {
        return Ticket::query()
            ->where('conversation_id', $conversation->id)
            ->with('status')
            ->latest()
            ->get();
    }

    public function getCategories(): Collection
    {
        // Cacheado por el mismo motivo que getAssignableAgents(): el modal de
        // escalar a ticket se renderiza en CADA carga del inbox (aunque nadie
        // lo abra), así que esta query salía en todas las visitas a la bandeja.
        //
        // Se adjunta el resumen del SLA que heredará el ticket: las 27
        // categorías tienen política por defecto, pero el modal arranca en "Sin
        // categoría", así que el agente no tenía forma de saber que escalando
        // sin categoría el ticket nacía fuera de todos los avisos de vencimiento.
        return Cache::remember('helpdesktickets:bridge-categories', 300, fn () => TicketCategory::query()
            ->with('defaultSlaPolicy:id,name,first_response_time,resolution_time')
            ->orderBy('name')
            ->get(['id', 'name', 'default_sla_policy_id'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'sla' => $this->describeSlaPolicy($c->defaultSlaPolicy),
            ]));
    }

    public function getTicketGroups(): Collection
    {
        // Mismo criterio de caché que agentes y categorías: es catálogo de
        // staff, cambia poco y el modal lo pinta en cada carga del inbox.
        return Cache::remember('helpdesktickets:bridge-groups', 300, fn () => TicketGroup::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]));
    }

    /**
     * Resumen legible de una política SLA para el modal ("Primera respuesta en
     * 4 h · resolución en 1 d"). Los tiempos se guardan en minutos.
     */
    private function describeSlaPolicy(?TicketSlaPolicy $policy): ?string
    {
        if (! $policy) {
            return null;
        }

        $partes = [];

        if ($policy->first_response_time) {
            $partes[] = 'primera respuesta en '.$this->humanizeMinutes((int) $policy->first_response_time);
        }

        if ($policy->resolution_time) {
            $partes[] = 'resolución en '.$this->humanizeMinutes((int) $policy->resolution_time);
        }

        return $partes ? implode(' · ', $partes) : $policy->name;
    }

    private function humanizeMinutes(int $minutes): string
    {
        if ($minutes >= 1440) {
            $dias = round($minutes / 1440, 1);

            return rtrim(rtrim(number_format($dias, 1, ',', ''), '0'), ',').' d';
        }

        if ($minutes >= 60) {
            $horas = round($minutes / 60, 1);

            return rtrim(rtrim(number_format($horas, 1, ',', ''), '0'), ',').' h';
        }

        return $minutes.' min';
    }

    public function getAssignableAgents(): Collection
    {
        // Antes: query inline en el blade con roles 'agent'/'admin'/'manager'
        // (no existen — los reales son helpdesk-agent/helpdesk-admin/
        // helpdesk-manager, lanzaban RoleDoesNotExist) + un fallback por
        // permisos directos "like %ticket%" que enganchaba ~99 usuarios sin
        // relación real con ser agente de Helpdesk. Cacheado 5 min: es una
        // lista de staff que cambia poco, y el modal de escalar a ticket
        // puede abrirse varias veces por sesión de un agente.
        // La lista de personas se cachea; el ESTADO no, porque cambia por
        // minutos (presencia, plazas ocupadas) y un desplegable que diga
        // "Conectado" cinco minutos después de que el agente cierre el panel es
        // peor que no decir nada.
        $agentes = Cache::remember('helpdesktickets:assignable-agents', 300, fn () => User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['helpdesk-agent', 'helpdesk-admin', 'helpdesk-manager']))
            ->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname', 'email', 'last_login_at']));

        return app(AgentAvailabilityService::class)->describe($agentes)
            // Primero quien puede atenderlo de verdad: en una lista de doce
            // agentes de los que once no han entrado nunca, el orden alfabético
            // deja al único operativo en mitad del desplegable.
            ->sortBy([
                fn ($a, $b) => ($b['available'] <=> $a['available']),
                fn ($a, $b) => strcmp($a['name'], $b['name']),
            ])
            ->values();
    }

    public function getTicketDetail(int $ticketId): ?array
    {
        $ticket = Ticket::with([
            'status', 'category', 'assignee', 'group', 'customer',
            'slaPolicy', 'lastMessage.author', 'lastOutboundMail', 'conversation',
        ])
            ->withCount('messages')
            ->find($ticketId);

        if (! $ticket) {
            return null;
        }

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => [
                'name' => $ticket->status?->name ?? 'Abierto',
                'color' => $ticket->status?->color ?? '#6c757d',
            ],
            'category' => $ticket->category?->name,
            // ?->name daba SIEMPRE null y por tanto "Sin asignar" hasta en los
            // tickets asignados: el usuario del helpdesk no tiene columna
            // `name`, se compone de firstname + lastname (fullName()).
            'assignee' => $this->personName($ticket->assignee),
            'group' => $ticket->group?->name,
            'customer' => $this->detailCustomer($ticket),
            'source' => $ticket->source ?? 'widget',
            // El slug crudo se colaba en la ficha: la fila "Origen" enseñaba
            // "conversation" o "wa" en vez del canal que el agente reconoce.
            'source_label' => $this->sourceLabel($ticket->source),
            'created_at' => $ticket->created_at?->translatedFormat('d M Y · H:i'),
            'updated_at' => $ticket->updated_at?->diffForHumans(),
            'items_count' => $ticket->messages_count ?? 0,
            'url' => route('manager.helpdesk.tickets.show', $ticket),

            // ── Lo que el modal necesitaba y no recibía ──────────────────
            'tags' => array_values(array_filter((array) ($ticket->tags ?? []))),
            'order_ref' => $this->detailOrderRef($ticket),
            'sla' => $this->detailSla($ticket),
            'last_move' => $this->detailLastMove($ticket),
            'conversation' => $this->detailConversation($ticket),
            'activity' => $this->detailActivity($ticket),
        ];
    }

    /**
     * Nombre presentable de un usuario del helpdesk.
     */
    private function personName(mixed $user): ?string
    {
        if (! $user) {
            return null;
        }

        if (method_exists($user, 'fullName') && trim((string) $user->fullName()) !== '') {
            return trim((string) $user->fullName());
        }

        return trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: ($user->email ?? null);
    }

    /**
     * Ficha mínima del cliente, con sus iniciales y el vínculo con gestión.
     *
     * El id de ERP se incluye porque es la comprobación que el agente hacía
     * abriendo Contactos 360 en otra pestaña: si el cliente no está en gestión,
     * media respuesta del ticket no se puede dar.
     */
    private function detailCustomer(Ticket $ticket): array
    {
        $customer = $ticket->customer;
        $name = $customer?->name;

        return [
            'name' => $name ?: 'Sin cliente vinculado',
            'email' => $customer?->email,
            'erp_id' => $customer?->externalIdFor('erp'),
            // La ficha de Contactos 360 la publica HelpdeskContacts: si el
            // módulo está apagado la ruta no existe y no hay enlace que dar.
            'url' => $customer && Route::has('contacts.show')
                ? route('contacts.show', $customer->id)
                : null,
        ];
    }

    /**
     * Referencia de pedido, si el formulario de origen la traía.
     *
     * La fila "Pedido" del modal existía desde el principio y no se rellenaba
     * nunca: el dato vive en custom_fields, con el nombre que le puso cada
     * formulario. Se prueban las variantes conocidas, igual que hace el panel
     * de la pantalla de tickets (ticketOrderRef en tickets-app.js).
     */
    private function detailOrderRef(Ticket $ticket): ?string
    {
        $fields = (array) ($ticket->custom_fields ?? []);

        foreach (['order', 'pedido', 'order_id', 'order_reference', 'num_pedido', 'numero_pedido', 'id_order'] as $key) {
            $value = $fields[$key] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Estado del SLA en el lenguaje del agente: cuánto queda o cuánto se pasó.
     *
     * @return array{kind: string, label: string, policy: ?string, first_response: ?string}|null
     */
    private function detailSla(Ticket $ticket): ?array
    {
        $due = $ticket->sla_resolution_due_at;

        if (! $due && ! $ticket->sla_policy_id) {
            return null;
        }

        $kind = $ticket->slaRowKind();
        $label = match (true) {
            ! $due => 'Sin plazo de resolución',
            $due->isPast() => 'Resolución vencida hace '.$due->diffForHumans(null, true),
            default => 'Resolución vence en '.$due->diffForHumans(null, true),
        };

        return [
            'kind' => $kind,
            'label' => $label,
            'policy' => $ticket->slaPolicy?->name,
            'first_response' => $ticket->first_response_at
                ? 'primera respuesta cumplida'
                : ($ticket->sla_first_response_breached ? 'primera respuesta incumplida' : 'primera respuesta pendiente'),
        ];
    }

    /**
     * Último movimiento del hilo: quién y qué, no solo cuándo.
     *
     * "Última act. hace 2 h" no distingue al cliente insistiendo de un
     * compañero respondiendo, que es justo lo que decide si hay que abrir el
     * ticket ahora.
     */
    private function detailLastMove(Ticket $ticket): ?array
    {
        $message = $ticket->lastMessage;

        if (! $message) {
            return null;
        }

        $isCustomer = ($message->author_id ?? null) === ($ticket->customer_id ?? null);
        $author = $isCustomer
            ? ($ticket->customer?->name ?? 'Cliente')
            : ($this->personName($message->author) ?? 'Sistema');

        $mail = $ticket->lastOutboundMail;

        return [
            'author' => $author,
            'is_customer' => $isCustomer,
            'excerpt' => Str::limit(trim(strip_tags((string) ($message->body ?? ''))), 140),
            'when' => $message->created_at?->diffForHumans(),
            // Si la última respuesta rebotó, el ticket está muerto y hoy no lo
            // decía nadie en esta pantalla.
            'mail_status' => $mail?->status,
        ];
    }

    private function sourceLabel(?string $source): string
    {
        return [
            'email' => 'Email',
            'widget' => 'Widget web',
            'wa' => 'WhatsApp',
            'whatsapp' => 'WhatsApp',
            'fb' => 'Facebook',
            'ig' => 'Instagram',
            'formulario' => 'Formulario web',
            'web_form' => 'Formulario web',
            'conversation' => 'Conversación',
            'manual' => 'Creado a mano',
        ][$source] ?? ucfirst((string) ($source ?: 'desconocido'));
    }

    private function detailConversation(Ticket $ticket): ?array
    {
        $conversation = $ticket->conversation;

        if (! $conversation) {
            return null;
        }

        return [
            'id' => $conversation->id,
            'channel' => $this->sourceLabel($conversation->channel ?? $ticket->source),
            // Solo el nombre: serializar la entidad de estado entera metía en
            // el payload catorce columnas que el modal no pinta.
            'status' => is_object($conversation->status) ? ($conversation->status->name ?? null) : $conversation->status,
        ];
    }

    /**
     * Historial real del ticket para la línea de tiempo del modal.
     *
     * Antes el modal pintaba a mano una única línea fija ("Sistema creó el
     * ticket") aunque el historial existiera: cualquier reasignación, cambio de
     * estado o respuesta quedaba invisible.
     *
     * @return array<int, array<string, mixed>>
     */
    private function detailActivity(Ticket $ticket, int $limit = 6): array
    {
        return $ticket->items()
            ->with('author')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($item): array {
                $isCustomer = ($item->author_id ?? null) === ($item->ticket->customer_id ?? null);

                return [
                    'type' => $item->type,
                    'who' => $this->personName($item->author) ?? 'Sistema',
                    'excerpt' => Str::limit(trim(strip_tags((string) ($item->body ?? ''))), 90),
                    'when' => $item->created_at?->diffForHumans(),
                    'is_customer' => $isCustomer,
                ];
            })
            ->values()
            ->all();
    }

    public function getDashboardData(): array
    {
        $stats = Cache::remember(ReportsCache::dashboardKey('ticket_stats'), 300, function () {
            $row = Ticket::query()
                ->selectRaw('
                    SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN DATE(closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as created_today,
                    SUM(CASE WHEN sla_resolution_breached = 1 AND closed_at IS NULL THEN 1 ELSE 0 END) as sla_breached,
                    SUM(CASE WHEN assignee_id IS NULL AND closed_at IS NULL THEN 1 ELSE 0 END) as unassigned,
                    SUM(CASE WHEN sla_resolution_due_at IS NOT NULL AND sla_resolution_due_at < NOW() AND closed_at IS NULL THEN 1 ELSE 0 END) as overdue
                ')
                ->first();

            return [
                'open' => (int) $row->open,
                'closed_today' => (int) $row->closed_today,
                'created_today' => (int) $row->created_today,
                'sla_breached' => (int) $row->sla_breached,
                'unassigned' => (int) $row->unassigned,
                'overdue' => (int) $row->overdue,
            ];
        });

        $topAgents = Cache::remember(ReportsCache::dashboardKey('agent_stats'), 300, function () {
            // User lives on the default connection while helpdesk_tickets may live
            // in a separate helpdesk database, so the joined table is fully
            // qualified to keep the aggregate working when the schemas differ.
            $ticketsTable = (new Ticket)->getConnection()->getDatabaseName().'.helpdesk_tickets';

            return User::select(['users.id', 'users.firstname', 'users.lastname'])
                ->selectRaw('COUNT(t.id) as open_tickets')
                ->selectRaw('SUM(CASE WHEN DATE(t.closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today')
                ->join("{$ticketsTable} as t", 't.assignee_id', '=', 'users.id')
                ->whereNull('t.closed_at')
                ->groupBy('users.id', 'users.firstname', 'users.lastname')
                ->orderByDesc('open_tickets')
                ->limit(5)
                ->get();
        });

        $recentBreaches = Cache::remember(ReportsCache::dashboardKey('recent_breaches'), 60, function () {
            return Ticket::query()
                ->where('sla_resolution_breached', true)
                ->whereNull('closed_at')
                ->with(['customer:id,name', 'assignee:id,firstname,lastname', 'status:id,name,color'])
                ->orderBy('sla_resolution_due_at')
                ->limit(5)
                ->get();
        });

        $recentTickets = Cache::remember(ReportsCache::dashboardKey('recent_tickets'), 60, function () {
            return Ticket::query()
                ->with(['customer:id,name', 'status:id,name,color', 'assignee:id,firstname,lastname'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        });

        $avgRating = round(
            Ticket::query()
                ->whereNotNull('rated_at')
                ->whereMonth('rated_at', now()->month)
                ->avg('rating') ?? 0,
            1
        );

        return compact('stats', 'topAgents', 'recentBreaches', 'recentTickets', 'avgRating');
    }

    public function getAgentDashboardData(int $agentId): array
    {
        $row = Ticket::where('assignee_id', $agentId)
            ->selectRaw('
                SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN MONTH(closed_at) = ? THEN 1 ELSE 0 END) as closed_this_month,
                SUM(CASE WHEN sla_resolution_breached = 1 AND closed_at IS NULL THEN 1 ELSE 0 END) as sla_breached,
                AVG(CASE WHEN rated_at IS NOT NULL THEN rating END) as avg_rating
            ', [now()->month])
            ->first();

        $stats = [
            'open' => (int) $row->open,
            'closed_this_month' => (int) $row->closed_this_month,
            'sla_breached' => (int) $row->sla_breached,
            'avg_rating' => round($row->avg_rating ?? 0, 1),
        ];

        $recentTickets = Ticket::where('assignee_id', $agentId)
            ->with(['status:id,name,color', 'customer:id,name'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return compact('stats', 'recentTickets');
    }
}
