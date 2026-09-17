<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\Customer360Service;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Carga perezosa de las pestañas "pesadas" del panel derecho del inbox
 * (Archivos, Anteriores, Actividad) — antes se consultaban de forma eager en
 * CADA render de right-panel.blade.php (cada apertura/cambio de conversación),
 * aunque el agente nunca llegara a abrir esas pestañas. Cada método aquí
 * devuelve el mismo fragmento HTML que antes se generaba inline, servido bajo
 * demanda al hacer click en la pestaña (ver JS en right-panel.blade.php).
 *
 * "Tecnología" y "Pantalla" (asistencia en vivo) se quedan fuera de esta
 * conversión a propósito: ya están acotadas a conversaciones de canal web con
 * sesión de widget activa (una minoría del volumen real de un inbox
 * multicanal) y "Pantalla" es una superficie de control en vivo (live view /
 * screen share), no un listado de datos históricos — convertirla al mismo
 * patrón de fetch-on-click cambiaría su semántica en tiempo real.
 */
class RightPanelTabController extends Controller
{
    public function files(Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $customer = $conversation->customer;
        $files = collect();

        if ($customer) {
            $convIds = Conversation::where('customer_id', $customer->id)->pluck('id');
            $items = ConversationItem::query()
                ->whereIn('conversation_id', $convIds)
                ->whereNotNull('attachment_urls')
                ->with(['user:id,firstname,lastname'])
                ->latest('created_at')
                ->limit(60)
                ->get();

            foreach ($items as $item) {
                $urls = $item->attachment_urls ?? [];
                $metas = $item->metadata['attachments'] ?? [];
                foreach ($urls as $idx => $url) {
                    $urlEntry = is_array($url) ? $url : ['url' => $url];
                    $url = $urlEntry['url'] ?? $url;
                    $meta = $metas[$idx] ?? [];
                    $mimeMain = isset($urlEntry['mime_type']) ? explode('/', $urlEntry['mime_type'])[0] : null;
                    $meta = array_merge([
                        'name' => $urlEntry['name'] ?? null,
                        'size' => $urlEntry['size'] ?? null,
                        'type' => $mimeMain && in_array($mimeMain, ['image', 'video', 'audio']) ? $mimeMain : null,
                    ], $meta ?: []);
                    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $type = $meta['type'] ?? (
                        in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'tif', 'heic', 'heif', 'avif', 'ico', 'jfif']) ? 'image'
                        : (in_array($ext, ['mp4', 'mov', 'webm', 'avi', 'mkv', 'ogv', '3gp', 'flv', 'wmv', 'm4v']) ? 'video'
                        : (in_array($ext, ['mp3', 'ogg', 'wav', 'oga', 'm4a', 'aac', 'flac', 'opus', 'wma', 'aiff']) ? 'audio'
                        : 'document'))
                    );

                    $authorName = 'Sistema';
                    $authorIsAgent = false;
                    if ($item->user) {
                        $authorName = $item->user->fullName() ?: 'Agente';
                        $authorIsAgent = true;
                    } elseif ($item->author_id && $customer && $item->author_id === $customer->id) {
                        $authorName = $customer->name ?? 'Cliente';
                    }

                    $files->push((object) [
                        'url' => $url,
                        'name' => $meta['name'] ?? basename(parse_url($url, PHP_URL_PATH)),
                        'size' => $meta['size'] ?? null,
                        'type' => $type,
                        'ext' => $ext,
                        'created_at' => $item->created_at,
                        'conversation_id' => $item->conversation_id,
                        'author_name' => $authorName,
                        'author_is_agent' => $authorIsAgent,
                    ]);
                }
            }
        }

        $fileCounts = [
            'all' => $files->count(),
            'image' => $files->where('type', 'image')->count(),
            'audio' => $files->where('type', 'audio')->count(),
            'video' => $files->where('type', 'video')->count(),
            'document' => $files->where('type', 'document')->count(),
        ];
        $fileSizes = [
            'all' => $files->sum('size'),
            'image' => $files->where('type', 'image')->sum('size'),
            'audio' => $files->where('type', 'audio')->sum('size'),
            'video' => $files->where('type', 'video')->sum('size'),
            'document' => $files->where('type', 'document')->sum('size'),
        ];

        $html = view('helpdesk::helpdesk.inbox.partials.right-panel-tabs.files', [
            'rpFiles' => $files,
            'rpFileCounts' => $fileCounts,
            'rpFileSizes' => $fileSizes,
        ])->render();

        return response($html);
    }

    public function previous(Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $customer = $conversation->customer;
        $previous = collect();

        if ($customer) {
            $previous = Conversation::where('customer_id', $customer->id)
                ->where('id', '!=', $conversation->id)
                ->with(['status', 'assignee', 'inbox', 'lastMessage'])
                ->withCount(['items as messages_count'])
                ->latest('last_message_at')
                ->limit(20)
                ->get();
        }

        $html = view('helpdesk::helpdesk.inbox.partials.right-panel-tabs.previous', [
            'rpPrevious' => $previous,
            'rpCust' => $customer,
        ])->render();

        return response($html);
    }

    /**
     * Ícono + tono por tipo de evento. La clave real de la mayoría de eventos
     * vive en `activity_type` (assigned/status_changed/priority_changed/...),
     * NO en `type` (que para esos eventos siempre vale el literal 'activity'
     * — ver ActivityMessageService::createActivity()). Antes esta tabla se
     * indexaba por `type`, así que nunca hacía match y todo caía en el
     * ícono genérico. 'email_sent'/'internal_note'/'contact'/'location' sí
     * usan `type` directamente (no pasan por ActivityMessageService), y el
     * resto son tipos legacy que ya no se generan pero podrían existir en
     * datos antiguos.
     */
    private const EVENT_ICON_MAP = [
        // activity_type (eventos vía ActivityMessageService)
        'assigned' => ['icon' => 'fa-user-check', 'tone' => 'pos'],
        'unassigned' => ['icon' => 'fa-user-minus', 'tone' => 'neutral'],
        'status_changed' => ['icon' => 'fa-circle-dot', 'tone' => 'neutral'],
        'priority_changed' => ['icon' => 'fa-flag', 'tone' => 'strong'],
        'label_added' => ['icon' => 'fa-tag', 'tone' => 'pos'],
        'label_removed' => ['icon' => 'fa-tag', 'tone' => 'neutral'],
        'team_assigned' => ['icon' => 'fa-people-group', 'tone' => 'pos'],
        'snoozed' => ['icon' => 'fa-clock', 'tone' => 'neutral'],
        'unsnoozed' => ['icon' => 'fa-clock-rotate-left', 'tone' => 'pos'],
        'muted' => ['icon' => 'fa-bell-slash', 'tone' => 'neutral'],
        // type directo
        'email_sent' => ['icon' => 'fa-envelope', 'tone' => 'pos'],
        'internal_note' => ['icon' => 'fa-note-sticky', 'tone' => 'neutral'],
        'contact' => ['icon' => 'fa-address-card', 'tone' => 'neutral'],
        'location' => ['icon' => 'fa-location-dot', 'tone' => 'neutral'],
        // legacy (type directo, ya no se generan pero pueden existir en datos viejos)
        'status_change' => ['icon' => 'fa-circle-dot', 'tone' => 'neutral'],
        'closed' => ['icon' => 'fa-circle-xmark', 'tone' => 'neutral'],
        'reopened' => ['icon' => 'fa-rotate-left', 'tone' => 'pos'],
        'archived' => ['icon' => 'fa-box-archive', 'tone' => 'neutral'],
        'unarchived' => ['icon' => 'fa-box-open', 'tone' => 'neutral'],
        'attachment_added' => ['icon' => 'fa-paperclip', 'tone' => 'neutral'],
        'customer_replied' => ['icon' => 'fa-reply', 'tone' => 'pos'],
    ];

    public function activity(Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing('customer');

        $events = $conversation->events()->with(['author', 'user'])->latest()->limit(20)->get();

        $logMap = [];
        if (class_exists(EmailLog::class)) {
            EmailLog::query()
                ->select(EmailLog::LIST_COLUMNS)
                ->forEntity(Conversation::class, $conversation->id)
                ->get()
                ->each(function ($log) use (&$logMap) {
                    $logMap[$log->external_id ?? $log->uid] = $log;
                });
        }

        $formatted = $events->map(fn (ConversationItem $event) => $this->formatActivityEvent($event, $conversation, $logMap));

        $grouped = $formatted->groupBy('day_label');

        $html = view('helpdesk::helpdesk.inbox.partials.right-panel-tabs.activity', [
            'rpEventGroups' => $grouped,
            'rpEventsCount' => $formatted->count(),
        ])->render();

        return response($html);
    }

    /**
     * @param  array<string, EmailLog>  $logMap
     * @return array{icon: string, tone: string, title: string, subtitle: string, day_label: string, email: array{subject: string, delivered: bool, status_label: string}|null}
     */
    private function formatActivityEvent(ConversationItem $event, Conversation $conversation, array $logMap): array
    {
        $kind = $event->type === 'activity' ? ($event->activity_type ?? 'activity') : $event->type;
        $iconDef = self::EVENT_ICON_MAP[$kind] ?? ['icon' => 'fa-circle-info', 'tone' => 'neutral'];

        $email = null;
        if ($event->type === 'email_sent') {
            $customerName = $conversation->customer?->name ?? 'el cliente';
            $title = "Email enviado a {$customerName}";

            $log = $logMap[$event->external_id ?? ''] ?? null;
            $status = $log?->status ?? EmailStatus::Queued;
            $subject = $event->metadata['subject'] ?? '(sin asunto)';

            $email = [
                'subject' => $subject,
                'delivered' => $status === EmailStatus::Sent,
                'status_label' => $status->label(),
            ];
        } else {
            $title = $event->type === 'activity' ? $event->body : $event->event_label;
        }

        $subtitle = $event->created_at?->diffForHumans() ?? '';
        if ($event->sender_name !== 'Sistema') {
            $subtitle .= ' · '.$event->sender_name;
        }

        return [
            'icon' => $iconDef['icon'],
            'tone' => $iconDef['tone'],
            'title' => $title,
            'subtitle' => $subtitle,
            'day_label' => $this->dateLabelForItem($event->created_at),
            'email' => $email,
        ];
    }

    private function dateLabelForItem(?Carbon $dt): string
    {
        if (! $dt) {
            return 'Sin fecha';
        }

        if ($dt->isToday()) {
            return 'Hoy';
        }

        if ($dt->isYesterday()) {
            return 'Ayer';
        }

        return $dt->translatedFormat('D, d M');
    }

    public function customer360(Request $request, Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $customer = $conversation->customer;

        $c360 = $customer
            ? app(Customer360Service::class)->aggregate($customer, $request->boolean('force'))
            : null;

        $html = view('helpdesk::helpdesk.inbox.partials.right-panel-tabs.customer-360', [
            'c360' => $c360,
        ])->render();

        return response($html);
    }
}
