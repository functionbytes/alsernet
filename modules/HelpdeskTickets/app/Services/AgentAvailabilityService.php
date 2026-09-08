<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\AgentSettings;

/**
 * Estado real de un agente para los selectores de asignación.
 *
 * Los desplegables de "Asignar a" listaban a todo el que tuviera el rol
 * helpdesk-agent y ya está: en esta instalación son 12 personas de las que 11
 * NO han entrado nunca al panel y no tienen ni fila de ajustes de agente. Un
 * responsable asignaba un ticket a alguien que en la práctica no lo iba a ver,
 * sin ninguna señal en pantalla.
 *
 * Aquí se resuelve, por agente, si está realmente operativo y por qué no lo
 * está. La presencia (online/ausente) sale de helpdesk_agent_settings, que es
 * lo que mantiene el heartbeat de la bandeja; el resto son hechos duros de la
 * cuenta (nunca ha entrado, de vacaciones, sin plaza libre).
 */
class AgentAvailabilityService
{
    /**
     * Minutos sin heartbeat tras los que se deja de considerar "conectado"
     * aunque presence_state siga diciendo lo contrario: un navegador cerrado
     * de golpe deja el estado congelado en 'available' para siempre.
     */
    private const HEARTBEAT_STALE_MINUTES = 10;

    /**
     * Añade el estado a una lista de agentes (modelos User).
     *
     * Devuelve un array por agente en vez de tocar el modelo: los selectores
     * solo necesitan pintar, y así el consumidor no arrastra otra query.
     *
     * @param  Collection<int, User>  $agents
     * @return Collection<int, array{id:int,name:string,email:?string,available:bool,status:string,status_label:string}>
     */
    public function describe(Collection $agents): Collection
    {
        if ($agents->isEmpty()) {
            return collect();
        }

        $settings = AgentSettings::query()
            ->whereIn('user_id', $agents->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return $agents->map(function (User $agent) use ($settings) {
            [$status, $label, $available] = $this->resolveStatus($agent, $settings->get($agent->id));

            return [
                'id' => $agent->id,
                'name' => trim($agent->firstname.' '.$agent->lastname),
                'email' => $agent->email ?? null,
                'available' => $available,
                'status' => $status,
                'status_label' => $label,
            ];
        })->values();
    }

    /**
     * @return array{0: string, 1: string, 2: bool} [slug, etiqueta, operativo]
     */
    private function resolveStatus(User $agent, ?AgentSettings $settings): array
    {
        // Orden deliberado: primero los hechos que invalidan al agente pase lo
        // que pase, y solo al final la presencia en vivo. Un agente "conectado"
        // pero de vacaciones no está disponible, y decir "Disponible" porque el
        // heartbeat llegó hace un minuto sería justo el estado engañoso que se
        // quiere quitar de en medio.
        if ($agent->deleted_at) {
            return ['suspended', 'Cuenta suspendida', false];
        }

        if (! $agent->last_login_at) {
            return ['never_logged', 'No ha entrado nunca', false];
        }

        if ($settings?->vacation_until && $settings->vacation_until->isFuture()) {
            return ['vacation', 'De vacaciones hasta '.$settings->vacation_until->format('d/m'), false];
        }

        if (! $settings) {
            return ['no_settings', 'Sin configurar como agente', false];
        }

        if (! $settings->is_available || ! $settings->accepts_conversations) {
            return ['unavailable', 'No acepta asignaciones', false];
        }

        $max = (int) ($settings->max_concurrent_conversations ?: 0);
        $abiertos = (int) ($settings->current_open_count ?: 0);

        if ($max > 0 && $abiertos >= $max) {
            return ['full', "Sin plaza ({$abiertos}/{$max})", false];
        }

        $latido = $settings->last_heartbeat_at;
        $vivo = $latido && $latido->diffInMinutes(now()) < self::HEARTBEAT_STALE_MINUTES;

        if ($vivo && $settings->presence_state === AgentSettings::PRESENCE_AVAILABLE) {
            return ['online', 'Conectado', true];
        }

        if ($vivo && $settings->presence_state === AgentSettings::PRESENCE_BUSY) {
            return ['busy', 'Ocupado', true];
        }

        if ($vivo && $settings->presence_state === AgentSettings::PRESENCE_AWAY) {
            return ['away', 'Ausente', true];
        }

        // Desconectado pero con la cuenta en orden: se puede asignar (lo verá
        // al volver), y se dice desde cuándo no aparece.
        return [
            'offline',
            'Desconectado · última vez '.$agent->last_login_at->diffForHumans(),
            true,
        ];
    }
}
