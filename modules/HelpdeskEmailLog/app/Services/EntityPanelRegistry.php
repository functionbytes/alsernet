<?php

namespace Modules\HelpdeskEmailLog\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailLog\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Throwable;

/**
 * Registro central de EmailLogEntityPanelRenderer — permite que módulos
 * satélite (HelpdeskTickets, etc.) inyecten un panel HTML propio en el
 * detalle de un email sin que HelpdeskEmailLog dependa de ellos. Ver el
 * docblock de la interfaz para el contrato completo.
 *
 * Se registra como singleton en HelpdeskEmailLogServiceProvider::register()
 * (no boot()) a propósito: en Laravel TODOS los register() de TODOS los
 * providers corren antes que CUALQUIER boot(), así que un módulo satélite
 * puede resolver este singleton y llamar a register() desde su propio
 * boot() sin preocuparse de si su ServiceProvider carga antes o después
 * que el de HelpdeskEmailLog — el orden de módulos en config/modules.php
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
                Log::warning('HelpdeskEmailLog: EmailLogEntityPanelRenderer falló al renderizar el panel de entidad.', [
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
