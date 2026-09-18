<?php

namespace Modules\HelpdeskEmailActivity\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntitySummaryProvider;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Throwable;

/**
 * Registro central de EmailLogEntityPanelRenderer — permite que módulos
 * satélite (HelpdeskTickets, etc.) inyecten un panel HTML propio en el
 * detalle de un email sin que HelpdeskEmailActivity dependa de ellos. Ver el
 * docblock de la interfaz para el contrato completo.
 *
 * Se registra como singleton en HelpdeskEmailActivityServiceProvider::register()
 * (no boot()) a propósito: en Laravel TODOS los register() de TODOS los
 * providers corren antes que CUALQUIER boot(), así que un módulo satélite
 * puede resolver este singleton y llamar a register() desde su propio
 * boot() sin preocuparse de si su ServiceProvider carga antes o después
 * que el de HelpdeskEmailActivity — el orden de módulos en config/modules.php
 * (o el que decida nwidart/laravel-modules) deja de importar para esto.
 */
class EntityPanelRegistry
{
    /** @var list<EmailLogEntityPanelRenderer> */
    private array $renderers = [];

    public function register(EmailLogEntityPanelRenderer $renderer): void
    {
        $this->renderers[] = $renderer;
    }

    /**
     * Devuelve el HTML del primer renderer registrado cuyo supports() case
     * con el entity_type del log, o null si no hay entidad vinculada o
     * ningún renderer la reclama.
     *
     * Un renderer roto de un módulo satélite (excepción en render(), p. ej.
     * por una consulta a una tabla que ya no existe tras un rollback de
     * migración) nunca debe tumbar la vista de detalle de un email — de ahí
     * el try/catch: se loguea como warning y se sigue como si ese renderer
     * no existiera, igual que EmailLog::entity_url ya hace con las rutas.
     */
    public function renderFor(EmailLog $emailLog): ?string
    {
        if ($emailLog->entity_type === null) {
            return null;
        }

        foreach ($this->renderers as $renderer) {
            if (! $renderer->supports($emailLog->entity_type)) {
                continue;
            }

            try {
                return $renderer->render($emailLog);
            } catch (Throwable $e) {
                Log::warning('HelpdeskEmailActivity: EmailLogEntityPanelRenderer falló al renderizar el panel de entidad.', [
                    'renderer' => $renderer::class,
                    'entity_type' => $emailLog->entity_type,
                    'entity_id' => $emailLog->entity_id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }

    /**
     * Ficha corta de la entidad para la tarjeta "Entidad relacionada" (ver
     * EmailLogEntitySummaryProvider). Misma elección de renderer y mismo
     * try/catch que renderFor(): si el satélite falla, la tarjeta cae al
     * tipo + ID genéricos en vez de tumbar el detalle entero.
     *
     * El instanceof es lo que hace opcional el añadido: un renderer que solo
     * implemente el contrato de panel se salta sin ruido.
     *
     * @return array{icon?: string, title: string, badge?: ?string, subtitle?: ?string, rows?: list<array{label: string, value: string}>}|null
     */
    public function summaryFor(EmailLog $emailLog): ?array
    {
        if ($emailLog->entity_type === null) {
            return null;
        }

        foreach ($this->renderers as $renderer) {
            if (! $renderer->supports($emailLog->entity_type) || ! $renderer instanceof EmailLogEntitySummaryProvider) {
                continue;
            }

            try {
                return $renderer->summary($emailLog);
            } catch (Throwable $e) {
                Log::warning('HelpdeskEmailActivity: EmailLogEntityPanelRenderer falló al resumir la entidad.', [
                    'renderer' => $renderer::class,
                    'entity_type' => $emailLog->entity_type,
                    'entity_id' => $emailLog->entity_id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }
}
