<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

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
                        $authorName = trim(($item->user->firstname ?? '').' '.($item->user->lastname ?? '')) ?: 'Agente';
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

    public function activity(Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $events = $conversation->events()->with(['author', 'user'])->latest()->limit(20)->get();

        $html = view('helpdesk::helpdesk.inbox.partials.right-panel-tabs.activity', [
            'rpEvents' => $events,
        ])->render();

        return response($html);
    }
}
