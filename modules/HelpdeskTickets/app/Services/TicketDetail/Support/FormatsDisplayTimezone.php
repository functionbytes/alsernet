<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail\Support;

use Carbon\CarbonInterface;
use Modules\Core\Models\Setting;

/**
 * Zona horaria de visualización para las horas/fechas "humanas" del panel de
 * detalle (hilo, correo, seguimientos…). La app corre en UTC
 * (config('app.timezone')) pero el listado de tickets se formatea en el
 * NAVEGADOR del agente, en su hora local — en esta instalación siempre
 * Europe/Madrid (única zona operativa de la empresa; mismo criterio que
 * ContactAggregatorService::EXTERNAL_TIMEZONE y los defaults de
 * Helpdesk/HelpdeskSla/HelpdeskBirthday). Sin esto, el hilo mostraba 11:52
 * mientras el listado mostraba 13:52 para el MISMO mensaje — 2h de
 * diferencia, exactamente el offset de CEST (detectado 28-sep-2026).
 *
 * Respeta Settings → Localización (Setting::get('timezone'), pantalla ya
 * existente en el módulo System) si un admin llega a configurar algo ahí. Si
 * no hay nada guardado (comprobado en vivo: la clave no existe todavía en
 * ningún entorno), cae en Europe/Madrid y NO en config('app.timezone') —
 * ese config es UTC y volver a él habría dejado el bug intacto para todo el
 * mundo, que es justo lo que se está arreglando.
 *
 * diffForHumans() no pasa por aquí a propósito: es relativo ("hace 3
 * horas"), no una hora de reloj absoluta, así que el timezone de origen no
 * cambia lo que se lee.
 */
trait FormatsDisplayTimezone
{
    private function displayTimezone(): string
    {
        $configured = trim((string) Setting::get('timezone', ''));

        return $configured !== '' ? $configured : 'Europe/Madrid';
    }

    /**
     * Copia la fecha (nunca muta el original: created_at/sent_at siguen
     * viajando en UTC por su lado, p.ej. como ISO-8601) a la zona de
     * visualización, lista para format()/translatedFormat().
     */
    private function inDisplayTz(?CarbonInterface $date): ?CarbonInterface
    {
        return $date?->copy()->setTimezone($this->displayTimezone());
    }
}
