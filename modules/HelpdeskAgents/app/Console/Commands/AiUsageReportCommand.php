<?php

namespace Modules\HelpdeskAgents\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskAgents\Services\AiUsageRecorder;

/**
 * Vista simple del ledger helpdesk_ai_usage: uso LLM agregado por día y
 * feature (llamadas, fallos, tokens in/out, duración media) y estado del
 * presupuesto diario.
 */
class AiUsageReportCommand extends Command
{
    protected $signature = 'helpdesk:ai-usage {--days=7 : Días hacia atrás a mostrar}';

    protected $description = 'Muestra el uso LLM del helpdesk por día y feature (helpdesk_ai_usage)';

    public function handle(AiUsageRecorder $recorder): int
    {
        $days = max(1, (int) $this->option('days'));

        try {
            $rows = DB::connection('helpdesk')
                ->table('helpdesk_ai_usage')
                ->where('created_at', '>=', now()->subDays($days)->startOfDay())
                ->selectRaw('
                    DATE(created_at) as day,
                    feature,
                    COUNT(*) as calls,
                    SUM(CASE WHEN success = 1 THEN 0 ELSE 1 END) as failed,
                    COALESCE(SUM(tokens_in), 0) as tokens_in,
                    COALESCE(SUM(tokens_out), 0) as tokens_out,
                    ROUND(AVG(duration_ms)) as avg_ms
                ')
                ->groupBy('day', 'feature')
                ->orderByDesc('day')
                ->orderBy('feature')
                ->get();
        } catch (\Throwable $e) {
            $this->error('No se pudo leer helpdesk_ai_usage: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($rows->isEmpty()) {
            $this->info("Sin uso LLM registrado en los últimos {$days} días.");

            return self::SUCCESS;
        }

        $this->table(
            ['Día', 'Feature', 'Llamadas', 'Fallidas', 'Tokens in', 'Tokens out', 'Media ms'],
            $rows->map(fn ($r) => [
                $r->day,
                $r->feature,
                (int) $r->calls,
                (int) $r->failed,
                number_format((int) $r->tokens_in),
                number_format((int) $r->tokens_out),
                (int) $r->avg_ms,
            ])->all()
        );

        $totals = $recorder->todayTotals();
        $maxCalls = (int) config('helpdeskagents.ai_usage.daily_max_calls', 0);
        $maxTokens = (int) config('helpdeskagents.ai_usage.daily_max_tokens', 0);

        if ($totals !== null) {
            $this->newLine();
            $this->line(sprintf(
                'Hoy: %d llamadas%s, %s tokens%s.',
                $totals['calls'],
                $maxCalls > 0 ? " / límite {$maxCalls}" : '',
                number_format($totals['tokens']),
                $maxTokens > 0 ? ' / límite '.number_format($maxTokens) : ''
            ));

            if ($recorder->dailyBudgetExceeded()) {
                $this->warn('Presupuesto diario superado: AgentLlmService está devolviendo null (features de IA en pausa hasta mañana).');
            }
        }

        return self::SUCCESS;
    }
}
