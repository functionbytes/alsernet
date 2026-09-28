<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail\Support;

/**
 * Compartido por ThreadBuilder (chips de adjuntos del hilo) y FormBuilder
 * (adjuntos del formulario de origen) — mismo formato "142 KB" que el
 * mockup usa en ambos sitios.
 */
trait FormatsFileSize
{
    /** Tamaño en la unidad más legible (el mockup los enseña como "142 KB"). */
    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $kb = $bytes / 1024;

        return $kb < 1024
            ? round($kb).' KB'
            : round($kb / 1024, 1).' MB';
    }
}
