<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\ToggleReactionRequest;
use Modules\Helpdesk\Jobs\SendOutboundMessageJob;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationItemsController extends Controller
{
    /**
     * Toggle an emoji reaction on a conversation item.
     * Adds the reaction if the user hasn't reacted with that emoji yet,
     * removes it if they have.
     */
    public function react(ToggleReactionRequest $request, ConversationItem $item): JsonResponse
    {
        // Reaccionar MUTA el ítem: exige permiso de escritura (update), no solo
        // lectura — consistente con destroy()/retrySend() del mismo controller.
        $this->authorize('update', $item->conversation);

        $emoji = $request->validated()['emoji'];
        $userId = auth()->id();

        $reactions = $item->metadata['reactions'] ?? [];

        $existingIndex = null;
        foreach ($reactions as $index => $reaction) {
            if ($reaction['user_id'] === $userId && $reaction['emoji'] === $emoji) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            array_splice($reactions, $existingIndex, 1);
        } else {
            $reactions[] = [
                'user_id' => $userId,
                'emoji' => $emoji,
                'at' => now()->toIso8601String(),
            ];
        }

        $item->metadata = array_merge($item->metadata ?? [], ['reactions' => $reactions]);
        $item->save();

        $grouped = $this->groupReactions($reactions);

        return response()->json([
            'success' => true,
            'reactions' => $grouped,
        ]);
    }

    /**
     * Soft-delete a conversation item (e.g. an internal note).
     */
    public function destroy(ConversationItem $item): JsonResponse
    {
        $this->authorize('update', $item->conversation);

        $item->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Reintenta el envío de un mensaje saliente marcado como "no entregado".
     * Un ítem con send_failed no llegó a entregarse (external_id nulo), así que se
     * reenvía completo; la idempotencia de SendOutboundMessageJob evita duplicar.
     */
    public function retrySend(ConversationItem $item): JsonResponse
    {
        $this->authorize('update', $item->conversation);

        if (empty($item->metadata['send_failed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Este mensaje no está marcado como no entregado.',
            ], 422);
        }

        // Limpiar la marca de fallo (optimista) antes de re-despachar el envío.
        $meta = is_array($item->metadata) ? $item->metadata : [];
        unset($meta['send_failed'], $meta['send_failed_at']);
        $item->metadata = $meta;
        $item->save();

        SendOutboundMessageJob::dispatch(
            $item->conversation_id,
            $item->id,
            $item->body,
            $this->attachmentsForRetry($item),
        );

        return response()->json(['success' => true]);
    }

    /**
     * Reconstruye el payload de adjuntos para el reintento a partir del ítem.
     *
     * @return array<int, array{type: string, url: string, name: string|null}>
     */
    private function attachmentsForRetry(ConversationItem $item): array
    {
        return collect($item->attachment_urls ?? [])
            ->map(function ($a): array {
                $entry = is_array($a) ? $a : ['url' => (string) $a];
                $mime = $entry['mime_type'] ?? null;

                return [
                    'type' => $mime ? explode('/', $mime)[0] : 'file',
                    'url' => (string) ($entry['url'] ?? ''),
                    'name' => $entry['name'] ?? null,
                ];
            })
            ->filter(fn (array $a) => filled($a['url']))
            ->values()
            ->all();
    }

    /**
     * Group reactions by emoji with counts and current user flag.
     */
    private function groupReactions(array $reactions): array
    {
        $userId = auth()->id();
        $grouped = [];

        foreach ($reactions as $reaction) {
            $emoji = $reaction['emoji'];

            if (! isset($grouped[$emoji])) {
                $grouped[$emoji] = ['emoji' => $emoji, 'count' => 0, 'reacted' => false];
            }

            $grouped[$emoji]['count']++;

            if ($reaction['user_id'] === $userId) {
                $grouped[$emoji]['reacted'] = true;
            }
        }

        return array_values($grouped);
    }
}
