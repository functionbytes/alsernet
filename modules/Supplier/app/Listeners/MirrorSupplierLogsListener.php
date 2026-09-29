<?php

namespace Modules\Supplier\Listeners;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;

/**
 * 29-sep-2026: copia al canal propio 'supplier_automation'
 * (storage/logs/supplier-automation-*.log) los mensajes de log emitidos desde
 * código del módulo Supplier. El visor de logs de automatización lee SOLO ese
 * fichero; antes servía laravel.log entero (datos de clientes, tokens y errores
 * de todos los módulos) a cualquiera con permiso de "ver automatizaciones".
 */
class MirrorSupplierLogsListener
{
    public const CHANNEL = 'supplier_automation';

    private static bool $writing = false;

    public function handle(MessageLogged $event): void
    {
        // El propio write al canal dispara otro MessageLogged.
        if (self::$writing || ! $this->comesFromSupplierModule()) {
            return;
        }

        self::$writing = true;

        try {
            Log::channel(self::CHANNEL)->log($event->level, $event->message, $event->context);
        } catch (\Throwable) {
            // Nunca romper la petición por no poder escribir la copia.
        } finally {
            self::$writing = false;
        }
    }

    private function comesFromSupplierModule(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 40) as $frame) {
            $file = $frame['file'] ?? '';

            if ($file !== ''
                && str_contains($file, DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'Supplier'.DIRECTORY_SEPARATOR)
                && ! str_ends_with($file, 'MirrorSupplierLogsListener.php')) {
                return true;
            }
        }

        return false;
    }
}
