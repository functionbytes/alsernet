<?php

namespace Modules\Helpdesk\Concerns;

use App\Models\User;

/**
 * Nombre para mostrar de un agente a partir de nombre y apellido.
 *
 * Varios agentes reales de esta base tienen el mismo valor en firstname y
 * lastname ("Ángeles Ángeles", "Angeles Angeles", "Helena Helena"), así que
 * concatenar a ciegas duplicaba el nombre en pantalla. Si coinciden, uno
 * basta. Nació en SlaBreachesReportController y se repitió sin el arreglo en
 * CsatReportController (dos veces) y AgentPerformanceController — de ahí el
 * trait, para que un informe nuevo lo herede en vez de reinventarlo.
 */
trait FormatsAgentNames
{
    private function formatAgentName(?string $firstname, ?string $lastname): string
    {
        $first = trim((string) $firstname);
        $last = trim((string) $lastname);

        if ($last === '' || mb_strtolower($first) === mb_strtolower($last)) {
            return $first !== '' ? $first : ($last !== '' ? $last : 'Sin nombre');
        }

        return trim($first.' '.$last);
    }

    private function displayNameFor(?User $user, string $whenMissing): string
    {
        return $user ? $this->formatAgentName($user->firstname, $user->lastname) : $whenMissing;
    }
}
