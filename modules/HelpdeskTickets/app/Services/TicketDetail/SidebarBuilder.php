<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\Setting;
use Modules\Helpdesk\Services\AgentPresenceService;
use Modules\HelpdeskTickets\Models\Ticket;
use Throwable;

/**
 * Card "SLA", bloque "Idioma y traducción" y estado de presencia del agente
 * asignado, en el panel derecho del detalle. Extraído de
 * TicketDetailDataService (30-sep-2026) al trocear ese servicio por sección
 * de panel.
 */
class SidebarBuilder
{
    /**
     * Resumen del SLA para el panel derecho. El porcentaje es el consumido
     * del plazo de RESOLUCIÓN (desde que se creó el ticket hasta que vence),
     * acotado a 0-100: es lo que la barra del mockup representa. Devuelve
     * null cuando el ticket no tiene ninguna política aplicada, en cuyo caso
     * la card no se pinta en vez de enseñar una barra vacía sin significado.
     *
     * @return array{state: string, label: string, percent: int|null, resolution: string, first_response: string}|null
     */
    public function slaSummary(Ticket $ticket): ?array
    {
        $due = $ticket->sla_resolution_due_at;
        $kind = $ticket->slaRowKind();

        // Sin política aplicada y sin ningún plazo no hay SLA que resumir.
        // Antes bastaba con que el ticket hubiera tenido primera respuesta para
        // colar una tarjeta que decía "En plazo": afirmaba cumplir un plazo que
        // no existía y, de paso, tapaba el aviso de que no hay política.
        if (! $ticket->sla_policy_id && ! $due && ! $ticket->sla_first_response_due_at) {
            return null;
        }

        $percent = null;
        if ($due && $ticket->created_at) {
            $total = $ticket->created_at->diffInSeconds($due, false);
            if ($total > 0) {
                $elapsed = $ticket->created_at->diffInSeconds(now(), false);
                $percent = (int) max(0, min(100, round($elapsed / $total * 100)));
            }
        }

        // "cumplida en 42 min": tiempo real que se tardó en dar la primera
        // respuesta, no el plazo que había.
        $firstResponse = '—';
        if ($ticket->first_response_at && $ticket->created_at) {
            $firstResponse = 'cumplida en '.$ticket->created_at->diffForHumans($ticket->first_response_at, [
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                'parts' => 1,
            ]);
        } elseif ($ticket->sla_first_response_breached) {
            $firstResponse = 'incumplida';
        } elseif ($ticket->sla_first_response_due_at) {
            $firstResponse = 'pendiente';
        }

        return [
            'state' => $kind,
            'label' => match ($kind) {
                'breach' => 'Vencido',
                'warn' => 'En riesgo',
                default => 'En plazo',
            },
            'percent' => $percent,
            'resolution' => $ticket->slaRowText() ?: '—',
            'first_response' => $firstResponse,
        ];
    }

    /**
     * Estado de la traducción automática, si el módulo está activo.
     *
     * @return array{enabled: bool, incoming: bool, outgoing: bool, target: ?string, url_settings: ?string}|null
     */
    public function translationSettings(): ?array
    {
        // Antes solo miraba si el módulo estaba INSTALADO — un admin que
        // apagaba la integración en Settings → Integraciones (el toggle que
        // helpdesk_translate_enabled() sí respeta) seguía viendo este resumen
        // en la card Correo, como si la traducción automática siguiera activa
        // (detectado 14-sep-2026, auditoría de funcionalidades de tickets).
        if (! helpdesk_translate_enabled()) {
            return null;
        }

        return [
            'enabled' => true,
            'incoming' => (bool) (Setting::get('helpdesktranslate.auto_translate_incoming')
                ?? config('helpdesktranslate.auto_translate_incoming', false)),
            'outgoing' => (bool) (Setting::get('helpdesktranslate.auto_translate_outgoing')
                ?? config('helpdesktranslate.auto_translate_outgoing', false)),
            'target' => Setting::get('helpdesktranslate.default_target')
                ?? config('helpdesktranslate.default_target'),
            'url_settings' => Route::has('settings.helpdesk-translate.index')
                ? route('settings.helpdesk-translate.index')
                : null,
        ];
    }

    /**
     * available|busy|away|offline, o null si no hay asignado o el módulo de
     * presencia no está disponible (sin Redis el panel no debe romperse).
     */
    public function agentPresence(?int $userId): ?string
    {
        if (! $userId || ! class_exists(AgentPresenceService::class)) {
            return null;
        }

        try {
            return app(AgentPresenceService::class)->getState($userId);
        } catch (Throwable) {
            return null;
        }
    }
}
