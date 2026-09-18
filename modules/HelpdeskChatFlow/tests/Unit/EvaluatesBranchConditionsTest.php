<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers the rich condition operators and AND/OR grouping of
 * ChatFlowEngine::evaluateBranchItem helpers. The engine is built without its
 * constructor so these pure matchers can be exercised in isolation.
 */
class EvaluatesBranchConditionsTest extends TestCase
{
    private ChatFlowEngine $engine;

    private ReflectionMethod $matchesCondition;

    private ReflectionMethod $evaluateConditions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = (new ReflectionClass(ChatFlowEngine::class))->newInstanceWithoutConstructor();

        $this->matchesCondition = new ReflectionMethod(ChatFlowEngine::class, 'matchesCondition');
        $this->matchesCondition->setAccessible(true);

        $this->evaluateConditions = new ReflectionMethod(ChatFlowEngine::class, 'evaluateConditions');
        $this->evaluateConditions->setAccessible(true);
    }

    /**
     * @param  array<string, mixed>  $cond
     */
    private function match(mixed $actual, array $cond): bool
    {
        return $this->matchesCondition->invoke($this->engine, $actual, $cond);
    }

    /**
     * @param  array<int, array<string, mixed>>  $conditions
     * @param  array<string, mixed>  $context
     */
    private function evaluate(array $conditions, string $match, array $context): bool
    {
        return $this->evaluateConditions->invoke(
            $this->engine,
            $conditions,
            $match,
            fn (string $variable): mixed => $context[$variable] ?? null,
        );
    }

    public function test_legacy_operators_keep_their_behaviour(): void
    {
        $this->assertTrue($this->match('soporte', ['operator' => '=', 'value' => 'soporte']));
        $this->assertFalse($this->match('ventas', ['operator' => '=', 'value' => 'soporte']));
        $this->assertTrue($this->match('ventas', ['operator' => '!=', 'value' => 'soporte']));
        $this->assertTrue($this->match('10', ['operator' => '>', 'value' => '5']));
        $this->assertTrue($this->match('3', ['operator' => '<', 'value' => '5']));
        $this->assertTrue($this->match('hola mundo', ['operator' => 'contains', 'value' => 'mundo']));
        $this->assertFalse($this->match('x', ['operator' => 'unknown', 'value' => 'x']));
    }

    public function test_default_operator_is_equality(): void
    {
        $this->assertTrue($this->match('yes', ['value' => 'yes']));
    }

    public function test_in_and_not_in_operators(): void
    {
        $this->assertTrue($this->match('es', ['operator' => 'in', 'value' => 'es,en,fr']));
        $this->assertFalse($this->match('de', ['operator' => 'in', 'value' => 'es,en,fr']));
        $this->assertTrue($this->match('de', ['operator' => 'not_in', 'value' => 'es,en,fr']));
        $this->assertTrue($this->match('es', ['operator' => 'in', 'value' => ['es', 'en']]));
    }

    public function test_empty_operators(): void
    {
        $this->assertTrue($this->match(null, ['operator' => 'is_empty']));
        $this->assertTrue($this->match('', ['operator' => 'is_empty']));
        $this->assertTrue($this->match('   ', ['operator' => 'is_empty']));
        $this->assertTrue($this->match([], ['operator' => 'is_empty']));
        $this->assertFalse($this->match('x', ['operator' => 'is_empty']));
        $this->assertTrue($this->match('x', ['operator' => 'not_empty']));
        $this->assertFalse($this->match('', ['operator' => 'not_empty']));
    }

    public function test_starts_and_ends_with_operators(): void
    {
        $this->assertTrue($this->match('order-123', ['operator' => 'starts_with', 'value' => 'order-']));
        $this->assertFalse($this->match('order-123', ['operator' => 'starts_with', 'value' => 'inv-']));
        $this->assertTrue($this->match('file.pdf', ['operator' => 'ends_with', 'value' => '.pdf']));
        $this->assertFalse($this->match('anything', ['operator' => 'starts_with', 'value' => '']));
    }

    public function test_regex_operator_with_safe_guard(): void
    {
        $this->assertTrue($this->match('ABC123', ['operator' => 'regex', 'value' => '^[A-Z]+[0-9]+$']));
        $this->assertFalse($this->match('abc', ['operator' => 'regex', 'value' => '^[0-9]+$']));
        // Invalid pattern must be treated as no-match, never throw.
        $this->assertFalse($this->match('abc', ['operator' => 'regex', 'value' => '([a-z']));
        $this->assertFalse($this->match('abc', ['operator' => 'regex', 'value' => '']));
    }

    public function test_numeric_comparison_and_range_operators(): void
    {
        $this->assertTrue($this->match('5', ['operator' => '>=', 'value' => '5']));
        $this->assertTrue($this->match('5', ['operator' => '<=', 'value' => '5']));
        $this->assertTrue($this->match('7', ['operator' => 'between', 'value' => '5,10']));
        $this->assertFalse($this->match('12', ['operator' => 'between', 'value' => '5,10']));
        $this->assertTrue($this->match('7', ['operator' => 'between', 'value' => [5, 10]]));
        $this->assertTrue($this->match('7', ['operator' => 'between', 'value' => '5', 'value2' => '10']));
        $this->assertFalse($this->match('7', ['operator' => 'between', 'value' => '5']));
    }

    public function test_evaluate_conditions_defaults_to_and(): void
    {
        $conditions = [
            ['variable' => 'lang', 'operator' => '=', 'value' => 'es'],
            ['variable' => 'tier', 'operator' => '=', 'value' => 'gold'],
        ];

        $this->assertTrue($this->evaluate($conditions, 'all', ['lang' => 'es', 'tier' => 'gold']));
        $this->assertFalse($this->evaluate($conditions, 'all', ['lang' => 'es', 'tier' => 'silver']));
    }

    public function test_evaluate_conditions_supports_or_grouping(): void
    {
        $conditions = [
            ['variable' => 'lang', 'operator' => '=', 'value' => 'es'],
            ['variable' => 'tier', 'operator' => '=', 'value' => 'gold'],
        ];

        $this->assertTrue($this->evaluate($conditions, 'any', ['lang' => 'es', 'tier' => 'silver']));
        $this->assertFalse($this->evaluate($conditions, 'any', ['lang' => 'en', 'tier' => 'silver']));
    }

    public function test_empty_condition_set_always_matches(): void
    {
        $this->assertTrue($this->evaluate([], 'all', []));
        $this->assertTrue($this->evaluate([], 'any', []));
    }
}
