<?php

namespace Modules\HelpdeskAgents\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskAgents\Models\OncallRotation;

/**
 * QUAL-01: storeOncall() nunca calculaba next_handoff_at, así que no había
 * forma de saber cuándo tocaba rotar — este comando es el que efectivamente
 * avanza la guardia. Rota circularmente current_user_id sobre user_ids y
 * recalcula next_handoff_at sumando shift_duration_hours desde el handoff
 * anterior (no desde "ahora"), para ponerse al día sin perder el ritmo si el
 * scheduler se saltó una o varias ejecuciones.
 */
class AdvanceOncallRotations extends Command
{
    protected $signature = 'helpdesk:agents:advance-oncall';

    protected $description = 'Rota circularmente el agente de guardia de cada rotacion on-call vencida';

    public function handle(): int
    {
        $rotations = OncallRotation::query()
            ->where('is_active', true)
            ->whereNotNull('next_handoff_at')
            ->where('next_handoff_at', '<=', now())
            ->get();

        foreach ($rotations as $rotation) {
            $this->advance($rotation);
        }

        if ($rotations->isNotEmpty()) {
            $this->info("Rotadas {$rotations->count()} guardia(s).");
        }

        return self::SUCCESS;
    }

    private function advance(OncallRotation $rotation): void
    {
        $userIds = array_values($rotation->user_ids ?? []);

        if (empty($userIds)) {
            return;
        }

        $durationHours = max(1, (int) $rotation->shift_duration_hours);

        // Boundaries vencidos desde el último handoff registrado (incluido).
        // floor(x)+1 siempre da un entero > x, así que el próximo handoff
        // calculado queda estrictamente en el futuro y nunca se re-procesa
        // el mismo boundary en la siguiente ejecución.
        $elapsedHours = (int) $rotation->next_handoff_at->diffInHours(now());
        $periodsDue = intdiv($elapsedHours, $durationHours) + 1;

        $currentIndex = array_search($rotation->current_user_id, $userIds, true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;

        $nextIndex = ($currentIndex + $periodsDue) % count($userIds);

        $rotation->update([
            'current_user_id' => $userIds[$nextIndex],
            'next_handoff_at' => $rotation->next_handoff_at->copy()->addHours($periodsDue * $durationHours),
        ]);
    }
}
