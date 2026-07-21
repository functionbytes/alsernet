<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Services\Automation\ConditionEvaluator;
use Tests\TestCase;

/**
 * Endurecimiento anti-ReDoS de las condiciones `regex` de automatizaciones:
 *
 * - Un patrón inválido no explota: la condición evalúa a false y se loguea.
 * - Un patrón catastrófico ((a+)+$) queda acotado por el backtrack_limit
 *   local y devuelve false en tiempo acotado en vez de quemar CPU.
 * - El patrón tiene límite de longitud (500) y el sujeto se trunca (10KB).
 */
class ConditionEvaluatorRegexTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new ConditionEvaluator;
    }

    private function evaluateRegex(string $pattern, string $subject): bool
    {
        $message = new \stdClass;
        $message->body = $subject;

        return $this->evaluator->evaluate([
            'operator' => 'AND',
            'rules' => [
                ['type' => 'content', 'operator' => 'regex', 'value' => $pattern],
            ],
        ], ['message' => $message]);
    }

    public function test_valid_pattern_matches(): void
    {
        $this->assertTrue($this->evaluateRegex('urgente', 'Esto es URGENTE por favor'));
        $this->assertTrue($this->evaluateRegex('/pedido\s+\d+/i', 'sobre el Pedido 12345'));
        $this->assertFalse($this->evaluateRegex('reembolso', 'consulta general'));
    }

    public function test_invalid_pattern_does_not_throw_and_evaluates_false(): void
    {
        // '[unclosed' se envuelve como /[unclosed/i → regex inválida.
        $this->assertFalse($this->evaluateRegex('[unclosed', 'cualquier texto'));

        // Con delimitador explícito e inválida igualmente.
        $this->assertFalse($this->evaluateRegex('/foo(/', 'foo'));
    }

    public function test_pattern_over_length_limit_is_rejected(): void
    {
        $tooLong = str_repeat('a', ConditionEvaluator::MAX_REGEX_PATTERN_LENGTH + 1);

        $this->assertFalse(ConditionEvaluator::isValidRegexPattern($tooLong));
        // El sujeto SÍ contiene el literal — se rechaza por longitud, no por no matchear.
        $this->assertFalse($this->evaluateRegex($tooLong, $tooLong));
    }

    public function test_catastrophic_pattern_is_bounded_by_backtrack_limit(): void
    {
        $subject = str_repeat('a', 5000).'b';

        $start = microtime(true);
        $result = $this->evaluateRegex('/(a+)+$/', $subject);
        $elapsed = microtime(true) - $start;

        $this->assertFalse($result);
        // Sin límite local de backtracking esto tardaría segundos/minutos.
        $this->assertLessThan(2.0, $elapsed, 'La regex catastrófica no quedó acotada por el backtrack_limit');
    }

    public function test_backtrack_limit_is_restored_after_evaluation(): void
    {
        $original = (string) ini_get('pcre.backtrack_limit');

        $this->evaluateRegex('/(a+)+$/', str_repeat('a', 1000).'b');

        $this->assertSame($original, (string) ini_get('pcre.backtrack_limit'));
    }

    public function test_subject_is_truncated_to_max_bytes(): void
    {
        $padding = str_repeat('x', ConditionEvaluator::MAX_REGEX_SUBJECT_BYTES);

        // La aguja está más allá del límite de 10KB → no se evalúa.
        $this->assertFalse($this->evaluateRegex('/NEEDLE/', $padding.'NEEDLE'));

        // Dentro del límite sí matchea.
        $this->assertTrue($this->evaluateRegex('/NEEDLE/', 'NEEDLE'.$padding));
    }

    public function test_is_valid_regex_pattern_helper(): void
    {
        $this->assertTrue(ConditionEvaluator::isValidRegexPattern('hola'));
        $this->assertTrue(ConditionEvaluator::isValidRegexPattern('/^pedido \d+$/i'));

        $this->assertFalse(ConditionEvaluator::isValidRegexPattern(''));
        $this->assertFalse(ConditionEvaluator::isValidRegexPattern('[unclosed'));
        $this->assertFalse(ConditionEvaluator::isValidRegexPattern(str_repeat('.', 501)));
    }

    public function test_first_invalid_regex_pattern_scans_rules(): void
    {
        $this->assertNull(ConditionEvaluator::firstInvalidRegexPattern([]));

        $this->assertNull(ConditionEvaluator::firstInvalidRegexPattern([
            'operator' => 'AND',
            'rules' => [
                ['type' => 'content', 'operator' => 'contains', 'value' => '[esto no es regex'],
                ['type' => 'content', 'operator' => 'regex', 'value' => '/ok/'],
            ],
        ]));

        $this->assertSame('[bad', ConditionEvaluator::firstInvalidRegexPattern([
            'operator' => 'OR',
            'rules' => [
                ['type' => 'content', 'operator' => 'regex', 'value' => '[bad'],
            ],
        ]));
    }
}
