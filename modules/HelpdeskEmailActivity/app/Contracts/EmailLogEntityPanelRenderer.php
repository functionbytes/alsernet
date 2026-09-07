<?php

namespace Modules\HelpdeskEmailActivity\Contracts;

use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Punto de extensión para que un módulo satélite (p. ej. HelpdeskTickets)
 * añada su propio panel HTML al detalle de un email sin que
 * HelpdeskEmailActivity tenga que conocerlo ni depender de él — la dirección de
 * dependencia de este proyecto es siempre satélite → HelpdeskEmailActivity,
 * nunca al revés (ver EmailLogController::applyFilters(), que por el mismo
 * motivo tampoco conoce ningún FQCN de módulo concreto).
 *
 * El módulo dueño de la entidad implementa esta interfaz y se registra a sí
 * mismo en Modules\HelpdeskEmailActivity\Services\EntityPanelRegistry desde su
 * PROPIO boot() (nunca desde HelpdeskEmailActivity) — así ningún módulo hijo
 * necesita tocar una sola línea de este módulo para añadir su panel. Ver
 * el docblock de EntityPanelRegistry para el porqué del register()/boot().
 */
interface EmailLogEntityPanelRenderer
{
    /**
     * true si este renderer sabe pintar un panel para el entity_type dado
     * (p. ej. 'ticket'). EntityPanelRegistry usa esto para elegir, entre
     * todos los renderers registrados, cuál invocar.
     */
    public function supports(string $entityType): bool;

    /** @return string|null HTML ya renderizado, o null si no hay nada que mostrar */
    public function render(EmailLog $emailLog): ?string;
}
