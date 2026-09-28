<?php

namespace Modules\Erp\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * Convierte una excepción en un mensaje apto para devolver al cliente de la
 * API ERP. El detalle completo se sigue enviando al log; aquí solo se evita
 * filtrar hosts, credenciales, SQL o clases internas en la respuesta JSON.
 */
final class ErpErrorSanitizer
{
    public const GENERIC = 'Error interno del servidor';

    public static function forClient(Throwable|string $error): string
    {
        if ($error instanceof ModelNotFoundException) {
            return 'Recurso no encontrado';
        }

        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        if (str_contains($message, 'ORA-00942')) {
            return 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.';
        }

        // Solo el código ORA y su texto estándar, sin el SQL ni el binding
        // que OCI/PDO añaden detrás.
        if (preg_match('/ORA-\d{5}:[^\n(]*/', $message, $m) === 1) {
            return trim($m[0]);
        }

        return config('app.debug') ? $message : self::GENERIC;
    }
}
