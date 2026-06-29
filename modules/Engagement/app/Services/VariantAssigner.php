<?php

namespace Modules\Engagement\Services;

use Illuminate\Support\Collection;

class VariantAssigner
{
    /**
     * Pick exactly one rule per variant_group based on a deterministic hash
     * of (group_name + sessionToken). Rules without variant_group always pass.
     *
     * @param  Collection  $rules  Collection of TriggerRule|PersonalizationRule
     */
    public function filter(Collection $rules, string $sessionToken): Collection
    {
        $grouped = $rules->groupBy('variant_group');
        $picked = collect();

        foreach ($grouped as $groupName => $groupRules) {
            if (! $groupName) {
                $picked = $picked->concat($groupRules);

                continue;
            }

            $winner = $this->pickFromGroup($groupName, $groupRules, $sessionToken);
            if ($winner) {
                $picked->push($winner);
            }
        }

        return $picked->values();
    }

    private function pickFromGroup(string $group, Collection $rules, string $sessionToken): mixed
    {
        $totalWeight = (int) $rules->sum(fn ($r) => $r->variant_weight ?? 50);
        if ($totalWeight <= 0) {
            return $rules->first();
        }

        // Deterministic: same session+group always picks the same variant
        $hash = crc32($group.':'.$sessionToken);
        $bucket = $hash % $totalWeight;

        $accumulated = 0;
        foreach ($rules as $rule) {
            $accumulated += (int) ($rule->variant_weight ?? 50);
            if ($bucket < $accumulated) {
                return $rule;
            }
        }

        return $rules->last();
    }
}
