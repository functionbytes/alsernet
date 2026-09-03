<?php

namespace Modules\HelpdeskEmailActivity\Contracts;

use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Añadido OPCIONAL a EmailLogEntityPanelRenderer: describe la entidad con
 * datos planos para la tarjeta "Entidad relacionada" del detalle de un email.
 *
 * Va en una interfaz aparte, y no como método de EmailLogEntityPanelRenderer,
 * porque ese contrato ya lo implementan varios módulos (Document, Helpdesk
 * ×2, HelpdeskTickets): añadirle un método obligatorio los rompería a todos
 * de golpe. Así cada satélite lo adopta cuando le interesa, y
 * EntityPanelRegistry comprueba con instanceof antes de llamar.
 *
 * A diferencia de render(), esto NO es HTML: son datos que HelpdeskEmailActivity
 * pinta con su propio CSS, de modo que la tarjeta se ve igual venga del
 * módulo que venga. El satélite decide QUÉ contar de su entidad; el aspecto
 * sigue siendo cosa de HelpdeskEmailActivity.
 */
interface EmailLogEntitySummaryProvider
{
    /**
     * Ficha corta de la entidad: título, estado y un par de datos con nombre.
     *
     * Devuelve null cuando la entidad ya no existe o no hay nada que resumir
     * — entonces la tarjeta cae al tipo + ID genéricos que HelpdeskEmailActivity
     * ya conoce por sí solo.
     *
     * 'rows' son pares etiqueta/valor ya traducidos y formateados por el
     * satélite (él sabe cómo se llaman sus cosas). HelpdeskEmailActivity añade
     * por su cuenta los datos que sí son suyos, como cuántos emails tiene
     * el hilo.
     *
     * @return array{icon?: string, title: string, badge?: ?string, subtitle?: ?string, rows?: list<array{label: string, value: string}>}|null
     */
    public function summary(EmailLog $emailLog): ?array;
}
