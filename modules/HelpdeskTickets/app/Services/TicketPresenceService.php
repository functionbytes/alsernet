<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Presencia de agentes en un ticket (agent collision): registra quién está
 * mirando o respondiendo cada ticket con un heartbeat de TTL corto, para avisar
 * "otro agente está aquí" y evitar respuestas duplicadas. Best-effort sobre el
 * cache (no requiere Redis/pusher); el broadcast en tiempo real lo hace el
 * evento TicketViewing en paralelo cuando Reverb está disponible.
 */
class TicketPresenceService
{
    private const TTL_SECONDS = 60;

    private const STALE_SECONDS = 35;

    /**
     * Registra/renueva la presencia del agente y devuelve los OTROS agentes
     * activos en el ticket (excluye al propio y purga los caducados).
     *
     * @return array<int, array{user_id: int, name: string, action: string, at: int}>
     */
    public function heartbeat(int $ticketId, int $userId, string $userName, string $action, int $now): array
    {
        $viewers = $this->prune($this->all($ticketId), $now);

        $viewers[$userId] = [
            'user_id' => $userId,
            'name' => $userName,
            'action' => $action,
            'at' => $now,
        ];

        Cache::put($this->key($ticketId), $viewers, self::TTL_SECONDS);

        return array_values(array_filter(
            $viewers,
            fn (array $viewer) => $viewer['user_id'] !== $userId
        ));
    }

    /**
     * Elimina la presencia del agente (al cerrar el ticket / cambiar de pestaña).
     */
    public function leave(int $ticketId, int $userId, int $now): void
    {
        $viewers = $this->prune($this->all($ticketId), $now);
        unset($viewers[$userId]);

        if ($viewers === []) {
            Cache::forget($this->key($ticketId));

            return;
        }

        Cache::put($this->key($ticketId), $viewers, self::TTL_SECONDS);
    }

    /**
     * @return array<int, array{user_id: int, name: string, action: string, at: int}>
     */
    private function all(int $ticketId): array
    {
        return (array) Cache::get($this->key($ticketId), []);
    }

    /**
     * @param  array<int, array{user_id: int, name: string, action: string, at: int}>  $viewers
     * @return array<int, array{user_id: int, name: string, action: string, at: int}>
     */
    private function prune(array $viewers, int $now): array
    {
        return array_filter(
            $viewers,
            fn (array $viewer) => ($now - ($viewer['at'] ?? 0)) < self::STALE_SECONDS
        );
    }

    private function key(int $ticketId): string
    {
        return "helpdesk:ticket:{$ticketId}:viewers";
    }
}
