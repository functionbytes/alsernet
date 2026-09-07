<?php

namespace Modules\HelpdeskErp\Tests\Unit;

use Modules\HelpdeskErp\Console\Commands\WarmErpCacheCommand;
use Modules\HelpdeskErp\Jobs\WarmErpCacheJob;
use Tests\TestCase;

/**
 * Bug real (4-sep-2026): con el manager ERP caído, cada email de un lote de
 * WarmErpCacheJob cuelga hasta helpdeskErp.http_timeout antes de que el
 * circuit breaker intervenga. Con un chunk de 5 y http_timeout=15s, el peor
 * caso (5×15=75s) superaba el propio timeout del job (60s) — reventaba por
 * su propio timeout ANTES de que el breaker llegara a abrirse, y lo mismo le
 * pasaba a la ventana del breaker (circuit_open_seconds) frente al tiempo
 * real que tarda en acumular circuit_failure_threshold fallos.
 *
 * Estos tests no reproducen el timing real (sería una prueba de 75s+); son
 * guardas aritméticas sobre la configuración, para que nadie vuelva a subir
 * http_timeout/circuit_failure_threshold sin also revisar el chunk size y la
 * ventana del breaker.
 */
class WarmErpCacheTimingTest extends TestCase
{
    public function test_el_chunk_no_puede_superar_el_timeout_del_job_en_el_peor_caso(): void
    {
        $chunkSize = $this->chunkSize();
        $httpTimeout = (int) config('helpdeskErp.http_timeout', 15);
        $jobTimeout = (new WarmErpCacheJob([]))->timeout;

        $worstCase = $chunkSize * $httpTimeout;

        $this->assertLessThan(
            $jobTimeout,
            $worstCase,
            "chunk({$chunkSize}) × http_timeout({$httpTimeout}s) = {$worstCase}s debe quedar por debajo del timeout del job ({$jobTimeout}s), o cada lote entero de fallos revienta el job por su propio timeout antes de que el circuit breaker pueda abrirse."
        );
    }

    public function test_la_ventana_del_breaker_cubre_el_peor_caso_de_acumular_el_umbral(): void
    {
        $httpTimeout = (int) config('helpdeskErp.http_timeout', 15);
        $threshold = (int) config('helpdeskErp.circuit_failure_threshold', 5);
        $openSeconds = (int) config('helpdeskErp.circuit_open_seconds', 120);

        // Cache::add() fija la ventana solo en el PRIMER fallo (HasCircuitBreaker);
        // si expira antes de llegar al umbral, el contador vuelve a cero y el
        // breaker nunca llega a abrirse con una caída real (solo en tests con
        // Http::fake(), que fallan al instante).
        $worstCaseToAccumulate = $threshold * $httpTimeout;

        $this->assertGreaterThanOrEqual(
            $worstCaseToAccumulate,
            $openSeconds,
            "circuit_open_seconds({$openSeconds}s) debe cubrir threshold({$threshold}) × http_timeout({$httpTimeout}s) = {$worstCaseToAccumulate}s, o la clave de caché expira antes de acumular el umbral y el breaker nunca se abre de verdad."
        );
    }

    private function chunkSize(): int
    {
        $ref = new \ReflectionClassConstant(WarmErpCacheCommand::class, 'CHUNK_SIZE');

        return (int) $ref->getValue();
    }
}
