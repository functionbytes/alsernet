<?php

namespace Modules\HelpdeskAgents\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAgents\Models\AiUsage;

/**
 * Registro fail-silent de uso LLM en helpdesk_ai_usage + límite de gasto
 * diario. Todo aquí es best-effort: un fallo del ledger NUNCA rompe una
 * llamada LLM (misma filosofía que AgentLlmService), y el límite diario
 * falla en abierto si la tabla no existe todavía.
 */
class AiUsageRecorder
{
    /**
     * Inserta una fila de uso. Barato (un INSERT) y fail-silent.
     */
    public function record(
        string $provider,
        ?string $model,
        string $feature,
        ?int $tokensIn,
        ?int $tokensOut,
        int $durationMs,
        bool $success,
        ?int $statusCode = null
    ): void {
        if (! config('helpdeskagents.ai_usage.enabled', true)) {
            return;
        }

        try {
            AiUsage::query()->create([
                'provider' => $provider,
                'model' => $model !== null ? mb_substr($model, 0, 128) : null,
                'feature' => mb_substr($feature, 0, 32),
                'tokens_in' => $tokensIn,
                'tokens_out' => $tokensOut,
                'duration_ms' => max(0, $durationMs),
                'success' => $success,
                'status_code' => $statusCode,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('AiUsageRecorder: failed to record usage', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ¿Se ha superado el presupuesto diario (nº de llamadas o tokens)?
     * 0 / null en config = sin límite. Falla en abierto ante errores de BD.
     */
    public function dailyBudgetExceeded(): bool
    {
        $maxCalls = (int) config('helpdeskagents.ai_usage.daily_max_calls', 0);
        $maxTokens = (int) config('helpdeskagents.ai_usage.daily_max_tokens', 0);

        if ($maxCalls <= 0 && $maxTokens <= 0) {
            return false;
        }

        $totals = $this->todayTotals();

        if ($totals === null) {
            return false;
        }

        if ($maxCalls > 0 && $totals['calls'] >= $maxCalls) {
            return true;
        }

        return $maxTokens > 0 && $totals['tokens'] >= $maxTokens;
    }

    /**
     * Totales de hoy: llamadas y tokens (in+out). Null si el ledger no está
     * disponible (p. ej. migración sin ejecutar).
     *
     * @return array{calls: int, tokens: int}|null
     */
    public function todayTotals(): ?array
    {
        try {
            $row = DB::connection('helpdesk')
                ->table('helpdesk_ai_usage')
                ->where('created_at', '>=', now()->startOfDay())
                ->selectRaw('COUNT(*) as calls, COALESCE(SUM(COALESCE(tokens_in,0) + COALESCE(tokens_out,0)), 0) as tokens')
                ->first();

            return [
                'calls' => (int) ($row->calls ?? 0),
                'tokens' => (int) ($row->tokens ?? 0),
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
