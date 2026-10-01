<?php

namespace Modules\HelpdeskMedia\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Concerns\BroadcastsToWidgetConversation;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * Un mensaje ya publicado ha cambiado de adjuntos (imagen optimizada, bloqueada
 * por antivirus) o tiene transcripción. Helpdesk no tiene evento de "mensaje
 * actualizado" (MessageReceived haría que la bandeja pintara otra burbuja), así
 * que este lleva solo lo que cambió.
 */
class ConversationItemMediaProcessed implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, BroadcastsToWidgetConversation, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ConversationItem $item) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('helpdesk.conversation.'.$this->item->conversation_id)];

        if (! $this->item->is_internal) {
            $this->item->loadMissing('conversation');
            $widget = $this->item->conversation ? $this->widgetConversationChannel($this->item->conversation) : null;

            if ($widget !== null) {
                $channels[] = $widget;
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'item.media_processed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $media = is_array($this->item->metadata['media'] ?? null) ? $this->item->metadata['media'] : [];

        // La firma del virus es solo para agentes (va en la nota interna).
        $media = array_map(static function ($meta) {
            unset($meta['scan_signature']);

            return $meta;
        }, $media);

        $attachments = [];

        foreach ($this->item->attachment_urls as $index => $entry) {
            $attachments[] = is_array($entry)
                ? [
                    'id' => $index,
                    'name' => (string) ($entry['name'] ?? basename((string) ($entry['url'] ?? ''))),
                    'url' => (string) ($entry['url'] ?? ''),
                    'size' => (int) ($entry['size'] ?? 0),
                    'mime_type' => (string) ($entry['mime_type'] ?? ''),
                ]
                : ['id' => $index, 'name' => basename((string) $entry), 'url' => (string) $entry, 'size' => 0, 'mime_type' => ''];
        }

        return [
            'item_id' => $this->item->id,
            'conversation_id' => $this->item->conversation_id,
            'attachments' => $attachments,
            'media' => (object) $media,
        ];
    }
}
