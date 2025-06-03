<?php

namespace App\Console\Commands;

use App\Models\ProductReturnRule;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

class AuditReturnRules extends Command
{
    protected $signature = 'returns:audit-rules {--fix : Reparar conflictos automáticamente}';
    protected $description = 'Auditar reglas de devolución para detectar conflictos y problemas';

    public function handle()
    {
        $this->info('Iniciando auditoría de reglas de devolución...');

        $issues = [];

        // Verificar reglas duplicadas
        $duplicates = $this->checkDuplicateRules();
        if (!empty($duplicates)) {
            $issues['duplicates'] = $duplicates;
            $this->warn('Se encontraron reglas duplicadas: ' . count($duplicates));
        }

        // Verificar reglas huérfanas
        $orphans = $this->checkOrphanRules();
        if (!empty($orphans)) {
            $issues['orphans'] = $orphans;
            $this->warn('Se encontraron reglas huérfanas: ' . count($orphans));
        }

        // Verificar conflictos de prioridad
        $conflicts = $this->checkPriorityConflicts();
        if (!empty($conflicts)) {
            $issues['conflicts'] = $conflicts;
            $this->warn('Se encontraron conflictos de prioridad: ' . count($conflicts));
        }

        // Verificar reglas inconsistentes
        $inconsistent = $this->checkInconsistentRules();
        if (!empty($inconsistent)) {
            $issues['inconsistent'] = $inconsistent;
            $this->warn('Se encontraron reglas inconsistentes: ' . count($inconsistent));
        }

        if (empty($issues)) {
            $this->info('✅ No se encontraron problemas en las reglas de devolución.');
            return 0;
        }

        // Mostrar resumen de problemas
        $this->displayIssues($issues);

        if ($this->option('fix')) {
            $this->fixIssues($issues);
        } else {
            $this->info('Usa --fix para reparar automáticamente los problemas detectados.');
        }

        return 0;
    }

    protected function checkDuplicateRules(): array
    {
        $duplicates = [];

        // Verificar duplicados por categoría
        $categoryDuplicates = ProductReturnRule::where('rule_type', 'category')
            ->whereNotNull('category_id')
            ->where('is_active', true)
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($categoryDuplicates as $duplicate) {
            $rules = ProductReturnRule::where('category_id', $duplicate->category_id)
                ->where('is_active', true)
                ->get();
            $duplicates[] = [
                'type' => 'category',
                'id' => $duplicate->category_id,
                'rules' => $rules,
            ];
        }

        // Verificar duplicados por producto
        $productDuplicates = ProductReturnRule::where('rule_type', 'product')
            ->whereNotNull('product_id')
            ->where('is_active', true)
            ->selectRaw('product_id, COUNT(*) as count')
            ->groupBy('product_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($productDuplicates as $duplicate) {
            $rules = ProductReturnRule::where('product_id', $duplicate->product_id)
                ->where('is_active', true)
                ->get();
            $duplicates[] = [
                'type' => 'product',
                'id' => $duplicate->product_id,
                'rules' => $rules,
            ];
        }

        return $duplicates;
    }

    protected function checkOrphanRules(): array
    {
        $orphans = [];

        // Reglas de categoría sin categoría existente
        $categoryOrphans = ProductReturnRule::where('rule_type', 'category')
            ->whereNotNull('category_id')
            ->whereDoesntHave('category')
            ->get();

        foreach ($categoryOrphans as $orphan) {
            $orphans[] = [
                'rule_id' => $orphan->id,
                'type' => 'category',
                'missing_id' => $orphan->category_id,
            ];
        }

        // Reglas de producto sin producto existente
        $productOrphans = ProductReturnRule::where('rule_type', 'product')
            ->whereNotNull('product_id')
            ->whereDoesntHave('product')
            ->get();

        foreach ($productOrphans as $orphan) {
            $orphans[] = [
                'rule_id' => $orphan->id,
                'type' => 'product',
                'missing_id' => $orphan->product_id,
            ];
        }

        return $orphans;
    }

    protected function checkPriorityConflicts(): array
    {
        $conflicts = [];

        // Buscar reglas con la misma prioridad pero diferente alcance
        $samePriority = ProductReturnRule::where('is_active', true)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->having('count', '>', 1)
            ->where('priority', '>', 0)
            ->get();

        foreach ($samePriority as $priority) {
            $rules = ProductReturnRule::where('priority', $priority->priority)
                ->where('is_active', true)
                ->get();

            $conflicts[] = [
                'priority' => $priority->priority,
                'rules' => $rules,
            ];
        }

        return $conflicts;
    }

    protected function checkInconsistentRules(): array
    {
        $inconsistent = [];

        // Reglas que no permiten devolución pero tienen período de devolución
        $inconsistentReturnable = ProductReturnRule::where('is_returnable', false)
            ->whereNotNull('return_period_days')
            ->get();

        foreach ($inconsistentReturnable as $rule) {
            $inconsistent[] = [
                'rule_id' => $rule->id,
                'issue' => 'No retornable pero tiene período de devolución',
                'rule' => $rule,
            ];
        }

        // Reglas con porcentaje máximo mayor a 100
        $invalidPercentage = ProductReturnRule::where('max_return_percentage', '>', 100)
            ->get();

        foreach ($invalidPercentage as $rule) {
            $inconsistent[] = [
                'rule_id' => $rule->id,
                'issue' => 'Porcentaje máximo mayor a 100%',
                'rule' => $rule,
            ];
        }

        return $inconsistent;
    }

    protected function displayIssues(array $issues): void
    {
        $this->newLine();
        $this->error(' PROBLEMAS DETECTADOS:');
        $this->newLine();

        if (isset($issues['duplicates'])) {
            $this->line(' REGLAS DUPLICADAS:');
            foreach ($issues['duplicates'] as $duplicate) {
                $this->line("  - {$duplicate['type']} ID {$duplicate['id']}: {$duplicate['rules']->count()} reglas activas");
            }
            $this->newLine();
        }

        if (isset($issues['orphans'])) {
            $this->line(' REGLAS HUÉRFANAS:');
            foreach ($issues['orphans'] as $orphan) {
                $this->line("  - Regla {$orphan['rule_id']}: {$orphan['type']} {$orphan['missing_id']} no existe");
            }
            $this->newLine();
        }

        if (isset($issues['conflicts'])) {
            $this->line('⚡ CONFLICTOS DE PRIORIDAD:');
            foreach ($issues['conflicts'] as $conflict) {
                $this->line("  - Prioridad {$conflict['priority']}: {$conflict['rules']->count()} reglas");
            }
            $this->newLine();
        }

        if (isset($issues['inconsistent'])) {
            $this->line(' REGLAS INCONSISTENTES:');
            foreach ($issues['inconsistent'] as $inconsistent) {
                $this->line("  - Regla {$inconsistent['rule_id']}: {$inconsistent['issue']}");
            }
            $this->newLine();
        }
    }

    protected function fixIssues(array $issues): void
    {
        $this->info('Iniciando reparación automática...');

        // Reparar reglas huérfanas
        if (isset($issues['orphans'])) {
            foreach ($issues['orphans'] as $orphan) {
                ProductReturnRule::find($orphan['rule_id'])->update(['is_active' => false]);
                $this->line("✅ Desactivada regla huérfana {$orphan['rule_id']}");
            }
        }

        // Reparar reglas duplicadas (mantener la de mayor prioridad)
        if (isset($issues['duplicates'])) {
            foreach ($issues['duplicates'] as $duplicate) {
                $rules = $duplicate['rules']->sortByDesc('priority');
                $keepRule = $rules->first();

                foreach ($rules->skip(1) as $rule) {
                    $rule->update(['is_active' => false]);
                    $this->line("✅ Desactivada regla duplicada {$rule->id}");
                }

                $this->line("✅ Mantenida regla {$keepRule->id} (prioridad {$keepRule->priority})");
            }
        }

        // Reparar reglas inconsistentes
        if (isset($issues['inconsistent'])) {
            foreach ($issues['inconsistent'] as $inconsistent) {
                $rule = $inconsistent['rule'];

                if (str_contains($inconsistent['issue'], 'No retornable pero tiene período')) {
                    $rule->update(['return_period_days' => null]);
                    $this->line("✅ Eliminado período de devolución de regla {$rule->id}");
                }

                if (str_contains($inconsistent['issue'], 'Porcentaje máximo mayor')) {
                    $rule->update(['max_return_percentage' => 100.00]);
                    $this->line("✅ Ajustado porcentaje máximo de regla {$rule->id} a 100%");
                }
            }
        }

        $this->info('Reparación completada.');
    }
}
