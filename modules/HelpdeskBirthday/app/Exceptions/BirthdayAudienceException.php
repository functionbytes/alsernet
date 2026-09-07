<?php

namespace Modules\HelpdeskBirthday\Exceptions;

use RuntimeException;

/**
 * Algo impide construir la audiencia del día con garantías. Siempre aborta la
 * campaña: preferimos no enviar nada a enviar a quien no toca.
 */
class BirthdayAudienceException extends RuntimeException
{
    public static function managerNotConfigured(): self
    {
        return new self(
            'No hay URL del manager ERP configurada (ERP_MANAGER_URL): '.
            'sin ella no se puede saber quién cumple años.'
        );
    }

    public static function requestFailed(int $status, string $body): self
    {
        return new self(
            "El manager ERP respondió {$status} al pedir los cumpleañeros: ".
            mb_substr($body, 0, 300)
        );
    }

    public static function tooManyRecipients(int $found, int $max): self
    {
        return new self(
            "El ERP devolvió {$found} cumpleañeros, por encima del máximo de {$max}. ".
            'Campaña abortada por seguridad: revisa que el filtro birthday esté '.
            'desplegado en el manager antes de subir el límite.'
        );
    }

    /**
     * El manager aceptó el parámetro pero devolvió gente que no cumple años hoy:
     * señal inequívoca de que la versión desplegada todavía no soporta el filtro
     * y lo está ignorando en silencio.
     */
    public static function filterNotApplied(int $mismatched, int $sampled): self
    {
        return new self(
            "El manager devolvió {$mismatched} de {$sampled} clientes cuya fecha de ".
            'nacimiento no coincide con el día pedido: el filtro "birthday" no está '.
            'desplegado en el manager. Campaña abortada.'
        );
    }
}
