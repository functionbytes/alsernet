<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\TicketDetail\Support\FormatsDisplayTimezone;
use Modules\HelpdeskTickets\Services\TicketDetail\Support\FormatsFileSize;
use Throwable;

/**
 * Pestaña Hilo del panel de detalle: búsqueda/filtros/paginación de
 * TicketItem y su mapeo al contrato JSON. Extraído de TicketDetailDataService
 * (30-sep-2026) al trocear ese servicio por sección de panel — ver el
 * docblock de la clase façade para el porqué de la separación.
 */
class ThreadBuilder
{
    use FormatsDisplayTimezone;
    use FormatsFileSize;

    private ?bool $threadFullTextAvailable = null;

    /**
     * Consulta, filtra y pagina el hilo del ticket.
     *
     * La búsqueda del hilo vive en el servidor para que un ticket con
     * cientos de mensajes no tenga que descargarse entero. La misma
     * consulta soporta filtros avanzados y páginas de mensajes antiguos.
     *
     * @return array{items: Collection<int, TicketItem>, search: ?string, page: int, per_page: int, total: int, has_more: bool, filters: array<string, ?string>}
     */
    public function paginate(Ticket $ticket): array
    {
        $threadSearch = trim((string) request()->query('thread_search', ''));
        $threadSender = trim((string) request()->query('thread_sender', ''));
        $threadType = (string) request()->query('thread_type', 'all');
        $threadFrom = (string) request()->query('thread_from', '');
        $threadTo = (string) request()->query('thread_to', '');
        $threadChannel = trim((string) request()->query('thread_channel', ''));
        $threadPage = max(1, (int) request()->query('thread_page', 1));
        $threadPerPage = min(50, max(10, (int) request()->query('thread_per_page', 30)));

        $threadQuery = TicketItem::query()
            ->where('ticket_id', $ticket->id)
            ->with(['user', 'author']);

        if ($threadSearch !== '') {
            $like = '%'.$threadSearch.'%';
            if ($this->threadFullTextAvailable() && mb_strlen($threadSearch) >= 3) {
                $threadQuery->whereRaw(
                    '(MATCH(body, html_body) AGAINST (? IN NATURAL LANGUAGE MODE) > 0 OR body LIKE ? OR html_body LIKE ?)',
                    [$threadSearch, $like, $like]
                );
            } else {
                $threadQuery->where(function ($query) use ($like): void {
                    $query->where('body', 'like', $like)
                        ->orWhere('html_body', 'like', $like);
                });
            }
        }

        if ($threadType === 'customer') {
            $threadQuery->where('type', 'message')->whereNotNull('author_id')->whereNull('user_id');
        } elseif ($threadType === 'agent') {
            $threadQuery->where('type', 'message')->whereNotNull('user_id')->where('is_internal', false);
        } elseif ($threadType === 'note') {
            $threadQuery->where('is_internal', true);
        } elseif ($threadType === 'event') {
            $threadQuery->where('type', '!=', 'message');
        }

        // El canal se hereda del ticket, no del item. Aun así se filtra aquí
        // para que el control avanzado tenga semántica clara y no descargue
        // mensajes cuando el canal solicitado no coincide.
        if ($threadChannel !== '') {
            // `formulario` es el nombre visible del canal, mientras que
            // algunos tickets antiguos guardan `form` o `web_form` en
            // source. Normalizamos ambos lados para que el filtro no parezca
            // roto en esos tickets históricos.
            $channelAliases = [
                'formulario' => 'form',
                'web_form' => 'form',
            ];
            $requestedChannel = $channelAliases[strtolower($threadChannel)] ?? strtolower($threadChannel);
            $ticketChannel = $channelAliases[strtolower((string) $ticket->sourceSlug())] ?? strtolower((string) $ticket->sourceSlug());
            if ($requestedChannel !== $ticketChannel) {
                $threadQuery->whereRaw('1 = 0');
            }
        }

        if ($threadFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $threadFrom)) {
            $threadQuery->whereDate('created_at', '>=', $threadFrom);
        }
        if ($threadTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $threadTo)) {
            $threadQuery->whereDate('created_at', '<=', $threadTo);
        }

        if ($threadSender !== '') {
            $likeSender = '%'.$threadSender.'%';
            $userIds = User::query()
                ->where(function ($query) use ($likeSender): void {
                    $query->where('firstname', 'like', $likeSender)
                        ->orWhere('lastname', 'like', $likeSender)
                        ->orWhere('email', 'like', $likeSender);
                })->pluck('id');
            $customerIds = Customer::query()
                ->where(function ($query) use ($likeSender): void {
                    $query->where('name', 'like', $likeSender)
                        ->orWhere('email', 'like', $likeSender);
                })->pluck('id');

            $threadQuery->where(function ($query) use ($userIds, $customerIds): void {
                $query->whereIn('user_id', $userIds->isEmpty() ? [-1] : $userIds)
                    ->orWhereIn('author_id', $customerIds->isEmpty() ? [-1] : $customerIds);
            });
        }

        $threadTotal = (clone $threadQuery)->reorder()->count();
        $threadItems = $threadQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($threadPage, $threadPerPage)
            ->get()
            ->sortBy(fn (TicketItem $item) => sprintf('%s-%020d', $item->created_at?->format('YmdHis.u') ?? '', $item->id))
            ->values();

        // Mensaje sintético con la description del ticket cuando el hilo no
        // tiene NINGÚN mensaje real — ver descriptionAsInitialMessage(). Se
        // cuenta en el total y se ubica en la página que le corresponde
        // cronológicamente (la más antigua, es decir la ÚLTIMA en esta
        // paginación DESC) antes de calcular has_more, igual que si fuera
        // una fila real de BD.
        $synthetic = $this->descriptionAsInitialMessage(
            $ticket, $threadSearch, $threadType, $threadChannel, $threadFrom, $threadTo, $threadSender
        );
        if ($synthetic !== null) {
            $threadTotal++;
        }
        $lastPage = max(1, (int) ceil($threadTotal / $threadPerPage));
        $syntheticForThisPage = ($synthetic !== null && $threadPage === $lastPage) ? $synthetic : null;

        return [
            'items' => $threadItems,
            'synthetic_first' => $syntheticForThisPage,
            'search' => $threadSearch !== '' ? $threadSearch : null,
            'page' => $threadPage,
            'per_page' => $threadPerPage,
            'total' => $threadTotal,
            'has_more' => ($threadPage * $threadPerPage) < $threadTotal,
            'filters' => [
                'sender' => $threadSender !== '' ? $threadSender : null,
                'type' => $threadType,
                'from' => $threadFrom !== '' ? $threadFrom : null,
                'to' => $threadTo !== '' ? $threadTo : null,
                'channel' => $threadChannel !== '' ? $threadChannel : null,
            ],
        ];
    }

    /**
     * Mensaje sintético con la descripción del ticket, para cuando el hilo
     * no tiene NINGÚN mensaje real (ni de cliente ni de agente) pero el
     * ticket sí guardó una descripción al crearse. Auditoría 28-sep-2026: 30
     * tickets source=recurring + 1 web sin un solo TicketItem de tipo
     * mensaje (p.ej. #11804, description "x") — el hilo se veía vacío ("no
     * tiene mensajes") aunque el ticket traía contenido real desde el
     * origen.
     *
     * No se inserta un TicketItem en BD (nunca se escribió uno, y crearlo
     * ahora inventaría un autor/fecha que no existieron): se construye a
     * mano el mismo array que mapItems() produce para un item real, con
     * fecha = creación del ticket, y se filtra con el MISMO criterio que la
     * consulta SQL de paginate() — si un item real equivalente no pasaría
     * el filtro activo, el sintético tampoco se muestra ni cuenta en el
     * total.
     *
     * @return array<string, mixed>|null
     */
    private function descriptionAsInitialMessage(
        Ticket $ticket,
        string $search,
        string $type,
        string $channel,
        string $from,
        string $to,
        string $sender,
    ): ?array {
        $description = trim((string) $ticket->description);
        if ($description === '') {
            return null;
        }

        $hasRealMessage = TicketItem::query()
            ->where('ticket_id', $ticket->id)
            ->where('type', 'message')
            ->exists();
        if ($hasRealMessage) {
            return null;
        }

        // 'note'/'event'/'agent' no pueden mostrar nunca la description: es
        // siempre un mensaje entrante, no interno.
        if (in_array($type, ['note', 'event', 'agent'], true)) {
            return null;
        }

        if ($search !== '' && ! str_contains(mb_strtolower($description), mb_strtolower($search))) {
            return null;
        }

        if ($channel !== '') {
            $channelAliases = ['formulario' => 'form', 'web_form' => 'form'];
            $requestedChannel = $channelAliases[strtolower($channel)] ?? strtolower($channel);
            $ticketChannel = $channelAliases[strtolower((string) $ticket->sourceSlug())] ?? strtolower((string) $ticket->sourceSlug());
            if ($requestedChannel !== $ticketChannel) {
                return null;
            }
        }

        $createdDate = $ticket->created_at?->toDateString();
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && $createdDate !== null && $createdDate < $from) {
            return null;
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) && $createdDate !== null && $createdDate > $to) {
            return null;
        }

        // El origen "recurring" (tareas programadas, sin cliente escribiendo)
        // se atribuye al sistema; el resto de orígenes (web, formulario,
        // email…) son la voz del propio cliente.
        $isSystemOrigin = $ticket->source === 'recurring';
        $role = $isSystemOrigin
            ? __('helpdesktickets::helpdesktickets.thread.role_system')
            : __('helpdesktickets::helpdesktickets.thread.role_customer');
        $senderName = $isSystemOrigin ? $role : ($ticket->customer?->name ?: $role);

        if ($sender !== '') {
            $haystack = mb_strtolower($senderName.' '.($ticket->customer?->email ?? ''));
            if (! str_contains($haystack, mb_strtolower($sender))) {
                return null;
            }
        }

        return [
            'id' => -$ticket->id,
            'type' => 'message',
            'is_internal' => false,
            'sender_name' => $senderName,
            'from_agent' => false,
            'body' => $description,
            'is_html' => false,
            'translated_body' => null,
            'source_language_name' => null,
            'is_auto' => false,
            'attachment_count' => 0,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'created_at_human' => $ticket->created_at?->diffForHumans(),
            'time' => $this->inDisplayTz($ticket->created_at)?->format('H:i'),
            'role' => $role,
            'channel' => $ticket->source ?: null,
            'direction' => 'inbound',
            'attachments' => [],
            'mail_id' => null,
            'delivery' => null,
        ];
    }

    /**
     * Mapea la página de items ya paginada al contrato JSON del hilo.
     *
     * @param  Collection<int, TicketItem>  $items
     * @param  array<string, mixed>|null  $syntheticFirst  Ver
     *                                                     descriptionAsInitialMessage() — ya viene en la forma JSON
     *                                                     final (no es un TicketItem), así que se antepone DESPUÉS del
     *                                                     map() en vez de mezclarse con $items.
     * @return Collection<int, array<string, mixed>>
     */
    public function mapItems(Ticket $ticket, Collection $items, ?array $syntheticFirst = null): Collection
    {
        // Correo asociado a cada item, en UNA consulta: los botones "Reenviar"
        // y "Ver original" del hilo operan sobre TicketMail, no sobre el item,
        // y sin este mapa habría que consultarlo fila a fila.
        $mailsByItem = $ticket->mails()
            ->whereNotNull('ticket_item_id')
            ->get(['id', 'ticket_item_id', 'status', 'delivered_at', 'sent_at', 'delivery_error'])
            ->keyBy('ticket_item_id');

        $mapped = $items->map(fn ($item) => [
            'id' => $item->id,
            'type' => $item->type,
            'is_internal' => (bool) $item->is_internal,
            'sender_name' => $item->sender_name,
            'from_agent' => $item->isFromAgent(),
            // Antes: $item->content, que devuelve html_body SIN PURIFICAR
            // cuando existe -- "seguro" solo porque el JS del panel siempre
            // hacía escapeHtml() de esto, así que un mensaje con html_body
            // real (cualquier correo entrante en HTML) salía con las
            // etiquetas literales en pantalla (detectado 3-sep-2026,
            // TCK-2026-00093, respuesta real de Gmail). Con html_body se
            // manda ya purificado (mismo saneador que safeHtmlBody(), el que
            // usa la "ficha completa") + is_html=true para que el JS lo
            // inyecte tal cual en vez de escaparlo; is_html=false sigue
            // escapándose como texto plano, exactamente igual que antes.
            'body' => $item->html_body ? $item->safeHtmlBody() : $item->body,
            'is_html' => (bool) $item->html_body,
            // TranslateIncomingTicketMessage ya calcula translated_body/
            // source_locale para cada mensaje del cliente en un idioma
            // distinto al del agente, pero este endpoint (el que alimenta
            // el panel de /panel/helpdesk/tickets — la única vista de
            // detalle desde que se retiró la "ficha completa" show-full
            // el 8-sep-2026) nunca los exponía -- el agente no se enteraba
            // de que había una traducción disponible (detectado 3-sep-2026
            // probando el flujo real con un mensaje en inglés).
            'translated_body' => $item->translated_body,
            'source_language_name' => $item->source_language_name,
            // Solo AutoResponseTicketCommand marca este flag hoy; macros,
            // respuestas programadas y automatizaciones no dejan ningún
            // rastro en metadata que las distinga de una respuesta manual
            // del agente — no se inventa una heurística para esos casos.
            'is_auto' => (bool) data_get($item->metadata, 'auto_response', false),
            'attachment_count' => $item->attachment_count,
            'created_at' => $item->created_at?->toIso8601String(),
            'created_at_human' => $item->created_at?->diffForHumans(),
            // --- Campos del hilo del mockup ---
            // Hora corta: el hilo agrupa por día con su separador, así que
            // dentro de cada grupo la fecha sobra y "hace 3 semanas" no deja
            // ordenar mentalmente dos mensajes del mismo día.
            'time' => $this->inDisplayTz($item->created_at)?->format('H:i'),
            // Rol de quien escribe: es lo que distingue de un vistazo la
            // columna del cliente de la del agente.
            //
            // Antes: isFromAgent() ? ($item->user_id ? role_agent :
            // role_system) : role_customer. isFromAgent() ES user_id!==null,
            // así que la rama role_system era inalcanzable — un item de
            // sistema sin user_id ni author_id (p.ej. la auto-respuesta de
            // AutoResponseTicketCommand) caía en el "else" final y salía
            // etiquetado "Cliente" aunque sender_name ya mostrara "Sistema"
            // correctamente (detectado 14-sep-2026 rediseñando el hilo:
            // burbuja "Sistema" con chip "Cliente"). Misma terna de 3 vías
            // que getSenderNameAttribute()/isFromCustomer().
            'role' => $item->isFromCustomer()
                ? __('helpdesktickets::helpdesktickets.thread.role_customer')
                : ($item->isFromAgent()
                    ? __('helpdesktickets::helpdesktickets.thread.role_agent')
                    : __('helpdesktickets::helpdesktickets.thread.role_system')),
            // Canal por el que entró/salió el mensaje. Se hereda del ticket:
            // el item no guarda origen propio, y todos los de un ticket
            // comparten el suyo salvo los eventos del sistema.
            'channel' => $item->type === 'message' ? ($ticket->source ?: null) : null,
            'direction' => $item->type === 'message'
                ? ($item->isFromAgent() ? 'outbound' : 'inbound')
                : null,
            // Ficheros con nombre y peso, no solo el recuento: el mockup los
            // lista como chips con su tamaño para poder decidir si abrirlos.
            'attachments' => $this->threadAttachments($item),
            // Correo real detrás de este mensaje, si lo hubo. Es lo que permite
            // reenviarlo o ver su fuente: un mensaje del hilo que nunca salió
            // por correo (nota interna, evento, mensaje de widget) no tiene
            // nada que reenviar, y sus botones no deben ofrecerse.
            'mail_id' => $mailsByItem->get($item->id)?->id,
            'delivery' => $this->threadDelivery($mailsByItem->get($item->id)),
        ])->values();

        // Cronológicamente es el más antiguo del hilo (fecha de creación
        // del ticket): en la página que le corresponde (la última, ver
        // paginate()) siempre va primero.
        return $syntheticFirst !== null ? $mapped->prepend($syntheticFirst) : $mapped;
    }

    /**
     * Adjuntos de un item del hilo, con nombre y tamaño legibles.
     *
     * attachment_urls guarda rutas dentro del disco público. El tamaño/mime
     * se consultan al disco y no se cachean: son pocos ficheros por mensaje y
     * un dato desactualizado sería peor que uno ausente. Si el fichero ya no
     * está (purgado, movido), se devuelve sin tamaño/mime en vez de romper la
     * fila entera.
     *
     * 'bytes' (tamaño crudo) va ADEMÁS de 'size' (ya formateado, "27 KB",
     * que sigue usando el chip del hilo tal cual) porque
     * openFilePreviewModal() — el modal de previsualización que ya existía
     * para la pestaña Adjuntos, mockup "ve-file-preview" — espera bytes
     * crudos para formatearlos él mismo con formatFileSize().
     *
     * @return list<array{name: string, size: ?string, bytes: ?int, mime: ?string, url: string}>
     */
    private function threadAttachments(TicketItem $item): array
    {
        $paths = $item->attachment_urls ?? [];

        if (! is_array($paths) || $paths === []) {
            return [];
        }

        $disk = Storage::disk('public');

        return collect($paths)->map(function ($path) use ($disk): array {
            $path = (string) $path;
            $bytes = null;
            $size = null;
            $mime = null;

            try {
                if ($disk->exists($path)) {
                    $bytes = $disk->size($path);
                    $size = $this->humanSize($bytes);
                    $mime = $disk->mimeType($path) ?: null;
                }
            } catch (Throwable) {
                // Disco no disponible o ruta inválida: se informa del fichero
                // igualmente, solo que sin peso/mime.
            }

            return [
                'name' => basename($path),
                'size' => $size,
                'bytes' => $bytes,
                'mime' => $mime,
                'url' => $disk->url($path),
            ];
        })->values()->all();
    }

    /**
     * Traza de entrega de un mensaje saliente, para la línea
     * "entregado 10:42:11" del mockup.
     *
     * Devuelve null cuando no hay correo y ninguno de los tres estados
     * conocidos (entregado/enviado/fallido) aplica todavía (p.ej. en cola):
     * el mockup enseña siempre la traza porque su ejemplo está entregado,
     * pero afirmar una entrega que el proveedor no ha confirmado sería
     * inventarla.
     *
     * El caso 'failed'/'bounced' antes devolvía null igual que "sin traza
     * todavía" — la burbuja de un correo que rebotó se veía IDÉNTICA a una
     * sin ninguna confirmación de envío, y el agente solo se enteraba del
     * rebote si abría la pestaña Correo y pulsaba "Ver rebote" (detectado
     * 14-sep-2026 rediseñando el hilo). delivery_error ya se guardaba en
     * TicketMail (ver TicketMail::markFailed()) pero nunca llegaba aquí.
     *
     * @return array{label: string, at: ?string, error: ?string, failed: bool}|null
     */
    private function threadDelivery(?TicketMail $mail): ?array
    {
        if (! $mail) {
            return null;
        }

        if (in_array($mail->status, ['failed', 'bounced'], true)) {
            return [
                'label' => $mail->status === 'bounced' ? 'rebotado' : 'no se pudo enviar',
                'at' => $this->inDisplayTz($mail->sent_at)?->format('H:i:s'),
                'error' => $mail->delivery_error,
                'failed' => true,
            ];
        }

        if ($mail->delivered_at) {
            return ['label' => 'entregado', 'at' => $this->inDisplayTz($mail->delivered_at)->format('H:i:s'), 'error' => null, 'failed' => false];
        }

        // Aceptado por el servidor pero sin confirmación de entrega: se dice
        // exactamente eso, que no es lo mismo.
        if ($mail->sent_at && $mail->status === 'sent') {
            return ['label' => 'enviado', 'at' => $this->inDisplayTz($mail->sent_at)->format('H:i:s'), 'error' => null, 'failed' => false];
        }

        return null;
    }

    /**
     * Use the indexed search when the optional migration has been applied;
     * keep LIKE as a safe fallback for rolling deployments and SQLite tests.
     */
    private function threadFullTextAvailable(): bool
    {
        if ($this->threadFullTextAvailable !== null) {
            return $this->threadFullTextAvailable;
        }

        try {
            $connection = DB::connection('helpdesk');
            $this->threadFullTextAvailable = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
                && $connection->select(
                    'SHOW INDEX FROM `helpdesk_ticket_items` WHERE Key_name = ?',
                    ['helpdesk_ticket_items_body_fulltext']
                ) !== [];
        } catch (Throwable) {
            $this->threadFullTextAvailable = false;
        }

        return $this->threadFullTextAvailable;
    }
}
