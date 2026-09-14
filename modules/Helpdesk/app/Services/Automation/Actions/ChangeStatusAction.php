<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class ChangeStatusAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'change_status';
    }

    public static function paramSchema(): array
    {
        return [
            'status' => [
                'type' => 'string',
                'required' => true,
                // 'snoozed' no es un estado de esta accion: posponer una
                // conversacion lo gestiona SnoozeConversationAction aparte
                // (su propio action type, no pasa por resolveStatus()).
                'enum' => ['open', 'pending', 'resolved', 'closed'],
                'description' => 'Nuevo estado de la conversación',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     */
    public function execute(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;

        if (! $conversation instanceof Conversation) {
            return;
        }

        $statusSlug = $params['status'] ?? null;

        if (! $statusSlug) {
            return;
        }

        $status = $this->resolveStatus($statusSlug);

        if (! $status) {
            return;
        }

        $previousStatusId = $conversation->status_id;

        $updates = ['status_id' => $status->id];

        if (! $status->is_open) {
            $updates['closed_at'] = now();
        } elseif ($previousStatusId !== $status->id) {
            $updates['closed_at'] = null;
        }

        $conversation->update($updates);

        event(new ConversationStatusChanged($conversation, $status, auth()->id()));
    }

    private function resolveStatus(string $slug): ?ConversationStatus
    {
        $cacheKey = "helpdesk:status-slug:{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            // 'open' no cambia: sigue tomando el primer estado is_open=true
            // por `order`, igual que siempre (instalaciones existentes ya
            // dependen de este comportamiento exacto).
            if ($slug === 'open') {
                return ConversationStatus::where('is_open', true)->orderBy('order')->first();
            }

            // 'pending'/'resolved'/'closed' se buscan PRIMERO por su propio
            // slug o nombre exacto — asi se distinguen entre si (antes
            // 'resolved' y 'closed' colapsaban en el mismo estado: "el
            // primer is_open=false por order", y 'pending' no resolvia a
            // nada porque ningun estado real se llamaba o tenia slug
            // 'pending'). Solo si la instalacion no tiene esos slugs
            // asignados todavia (ver la migracion que los rellena para
            // Esperando/Resuelto/Cerrado) resolved/closed caen al
            // heuristico generico anterior, para no romper otros entornos.
            $exact = ConversationStatus::where('slug', $slug)
                ->orWhere('name', $slug)
                ->first();

            if ($exact) {
                return $exact;
            }

            if (in_array($slug, ['resolved', 'closed'], true)) {
                return ConversationStatus::where('is_open', false)->orderBy('order')->first();
            }

            return null;
        });
    }
}
