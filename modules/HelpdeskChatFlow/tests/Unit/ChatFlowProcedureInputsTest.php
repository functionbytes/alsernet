<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowValidator;
use Modules\HelpdeskChatFlow\Services\Concerns\EvaluatesBranchConditions;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\ValidatesUserInput;
use Modules\HelpdeskChatFlow\Services\Support\BranchOperators;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;
use Modules\HelpdeskChatFlow\Services\Support\SafeRegex;
use Modules\HelpdeskChatFlow\Tests\TestCase;

/**
 * Phase A of the "procedures" plan: collect_input validations, branch
 * operators and dot-path variables. Pure logic, no DB.
 */
class ChatFlowProcedureInputsTest extends TestCase
{
    private object $input;

    private object $conditions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->input = new class
        {
            use ValidatesUserInput;

            public function passes(string $rule, string $value, array $data = []): bool
            {
                return $this->passesValidation($rule, $value, $data);
            }

            public function normalize(string $rule, string $value, array $data = []): string
            {
                return $this->normalizeInput($rule, $value, $data);
            }

            public function error(string $rule, array $data = []): string
            {
                return $this->validationError($rule, $data);
            }

            public function skip(array $data, array $context): bool
            {
                return $this->shouldSkipCollectInput($data, $context);
            }
        };

        $this->conditions = new class
        {
            use EvaluatesBranchConditions;

            public function eval(array $conditions, string $match, array $context): bool
            {
                return $this->evaluateConditions($conditions, $match, fn (string $v): mixed => ContextPath::get($context, $v));
            }
        };
    }

    private function cond(string $operator, mixed $value = '', array $extra = []): array
    {
        return ['variable' => 'x', 'operator' => $operator, 'value' => $value] + $extra;
    }

    private function holds(array $condition, mixed $actual): bool
    {
        return $this->conditions->eval([$condition], 'all', ['x' => $actual]);
    }

    // ─── order_ref ─────────────────────────────────────────────────────────────

    public function test_order_ref_accepts_numeric_id_and_nine_letter_reference(): void
    {
        foreach (['12345', '#987', 'ABCDEFGHI', 'abcdefghi', 'AbCdEfGhI'] as $ok) {
            $this->assertTrue($this->input->passes('order_ref', $ok), $ok);
        }
    }

    public function test_order_ref_rejects_other_shapes(): void
    {
        foreach (['ABCDEFGH', 'ABCDEFGHIJ', 'ABC DEFGHI', '12.5', 'ABCDEFGH1', '-5', '12345678901', 'hola'] as $bad) {
            $this->assertFalse($this->input->passes('order_ref', $bad), $bad);
        }
    }

    public function test_order_ref_is_normalized_to_uppercase(): void
    {
        $this->assertSame('ABCDEFGHI', $this->input->normalize('order_ref', ' abcdefghi '));
        $this->assertSame('123', $this->input->normalize('order_ref', '#123'));
    }

    // ─── regex ─────────────────────────────────────────────────────────────────

    public function test_regex_validation_uses_the_node_pattern(): void
    {
        $data = ['pattern' => '^[A-Z]{3}-\d{4}$'];

        $this->assertTrue($this->input->passes('regex', 'ABC-1234', $data));
        $this->assertFalse($this->input->passes('regex', 'abc-1234', $data));
        $this->assertFalse($this->input->passes('regex', 'ABC-1234', []));
    }

    public function test_regex_with_slash_in_pattern_works(): void
    {
        $this->assertTrue($this->input->passes('regex', 'a/b', ['pattern' => '^a/b$']));
        $this->assertTrue($this->input->passes('regex', 'a/b', ['pattern' => '^a\/b$']));
    }

    public function test_dangerous_patterns_are_rejected_and_never_hang(): void
    {
        foreach (['(a+)+$', '^(a|aa)+$', '^(\w+\s?)*$', '(.*a){20}', '(', str_repeat('a', 201), ''] as $pattern) {
            $this->assertNotNull(SafeRegex::problem($pattern), $pattern);
        }

        $started = microtime(true);
        $this->assertFalse($this->input->passes('regex', str_repeat('a', 40).'!', ['pattern' => '^(a+)+$']));
        $this->assertLessThan(2.0, microtime(true) - $started);
    }

    public function test_safe_patterns_are_accepted(): void
    {
        foreach (['^[A-Z]{3}-\d{4}$', '^\d{5}$', '[a-z]+@empresa\.com', '^(?:ES)?\d{9}$'] as $pattern) {
            $this->assertNull(SafeRegex::problem($pattern), $pattern);
        }
    }

    // ─── enum ──────────────────────────────────────────────────────────────────

    public function test_enum_ignores_case_and_accents_and_returns_canonical_value(): void
    {
        $data = ['allowed' => ['Devolución', 'Cambio', 'Reclamación']];

        $this->assertTrue($this->input->passes('enum', 'devolucion', $data));
        $this->assertTrue($this->input->passes('enum', ' RECLAMACION ', $data));
        $this->assertFalse($this->input->passes('enum', 'otra cosa', $data));
        $this->assertFalse($this->input->passes('enum', 'cambio', ['allowed' => []]));
        $this->assertSame('Devolución', $this->input->normalize('enum', 'DEVOLUCION', $data));
    }

    // ─── errors ────────────────────────────────────────────────────────────────

    public function test_each_new_rule_has_its_own_message(): void
    {
        $messages = array_map(fn ($r) => $this->input->error($r, ['allowed' => ['a', 'b']]), ['order_ref', 'regex', 'enum']);

        $this->assertCount(3, array_unique($messages));
        $this->assertStringContainsString('a, b', $messages[2]);
        $this->assertSame('Custom', $this->input->error('regex', ['error_message' => ' Custom ']));
    }

    // ─── skip_if_set ───────────────────────────────────────────────────────────

    public function test_skip_if_set_requires_flag_and_a_filled_variable(): void
    {
        $data = ['variable_name' => 'customer_email', 'skip_if_set' => true];

        $this->assertTrue($this->input->skip($data, ['customer_email' => 'a@b.es']));
        $this->assertFalse($this->input->skip($data, ['customer_email' => '  ']));
        $this->assertFalse($this->input->skip($data, []));
        $this->assertFalse($this->input->skip(['variable_name' => 'customer_email'], ['customer_email' => 'a@b.es']));
        $this->assertTrue($this->input->skip(['variable_name' => 'pedido.id', 'skip_if_set' => true], ['pedido' => ['id' => 5]]));
    }

    // ─── operators ─────────────────────────────────────────────────────────────

    public function test_all_fifteen_operators(): void
    {
        $this->assertCount(15, BranchOperators::all());

        $this->assertTrue($this->holds($this->cond('=', 'a'), 'a'));
        $this->assertTrue($this->holds($this->cond('!=', 'a'), 'b'));
        $this->assertTrue($this->holds($this->cond('>', '5'), '6'));
        $this->assertTrue($this->holds($this->cond('>=', '5'), '5'));
        $this->assertTrue($this->holds($this->cond('<', '5'), '4'));
        $this->assertTrue($this->holds($this->cond('<=', '5'), '5'));
        $this->assertTrue($this->holds($this->cond('between', ['10', '20']), '15'));
        $this->assertFalse($this->holds($this->cond('between', ['10', '20']), '25'));
        $this->assertTrue($this->holds($this->cond('contains', 'ol'), 'hola'));
        $this->assertTrue($this->holds($this->cond('starts_with', 'ho'), 'hola'));
        $this->assertTrue($this->holds($this->cond('ends_with', 'la'), 'hola'));
        $this->assertTrue($this->holds($this->cond('in', ['a', 'b']), 'b'));
        $this->assertFalse($this->holds($this->cond('in', ['a', 'b']), 'c'));
        $this->assertTrue($this->holds($this->cond('not_in', ['a', 'b']), 'c'));
        $this->assertTrue($this->holds($this->cond('is_empty'), ''));
        $this->assertTrue($this->holds($this->cond('not_empty'), 'x'));
        $this->assertTrue($this->holds($this->cond('regex', '^[A-Z]{9}$'), 'ABCDEFGHI'));
        $this->assertFalse($this->holds($this->cond('regex', '^(a+)+$'), str_repeat('a', 40).'!'));
    }

    public function test_any_and_all_modes(): void
    {
        $conds = [['variable' => 'a', 'operator' => '=', 'value' => '1'], ['variable' => 'b', 'operator' => '=', 'value' => '2']];

        $this->assertFalse($this->conditions->eval($conds, 'all', ['a' => '1', 'b' => '9']));
        $this->assertTrue($this->conditions->eval($conds, 'any', ['a' => '1', 'b' => '9']));
        $this->assertFalse($this->conditions->eval($conds, 'any', ['a' => '0', 'b' => '9']));
        $this->assertTrue($this->conditions->eval($conds, 'all', ['a' => '1', 'b' => '2']));
    }

    // ─── dot paths ─────────────────────────────────────────────────────────────

    public function test_dot_path_reads_nested_arrays_and_json_strings(): void
    {
        $context = [
            'pedido' => ['estado' => 'enviado', 'lineas' => [['sku' => 'A1']]],
            'resp' => '{"order":{"state":"paid","total":12.5}}',
            'flat.key' => 'plano',
            'nombre' => 'Ada',
        ];

        $this->assertSame('enviado', ContextPath::get($context, 'pedido.estado'));
        $this->assertSame('A1', ContextPath::get($context, 'pedido.lineas.0.sku'));
        $this->assertSame('paid', ContextPath::get($context, 'resp.order.state'));
        $this->assertSame('plano', ContextPath::get($context, 'flat.key'));
        $this->assertSame('Ada', ContextPath::get($context, 'nombre'));
        $this->assertNull(ContextPath::get($context, 'pedido.nada'));
        $this->assertNull(ContextPath::get($context, 'nombre.x'));
    }

    public function test_interpolation_supports_dot_paths_and_stays_backward_compatible(): void
    {
        $context = ['nombre' => 'Ada', 'pedido' => ['estado' => 'enviado'], 'resp' => '{"a":{"b":7}}'];

        $this->assertSame('Hola Ada', ContextPath::interpolate('Hola {{nombre}}', $context));
        $this->assertSame('Tu pedido: enviado', ContextPath::interpolate('Tu pedido: {{pedido.estado}}', $context));
        $this->assertSame('7', ContextPath::interpolate('{{resp.a.b}}', $context));
        $this->assertSame('{{pedido.nada}} {{otra}}', ContextPath::interpolate('{{pedido.nada}} {{otra}}', $context));

        $renderer = new class
        {
            use RendersNodeMessages;

            public function render(string $t, array $c): string
            {
                return $this->interpolateContext($t, $c);
            }
        };
        $this->assertSame('enviado', $renderer->render('{{pedido.estado}}', $context));
    }

    public function test_conditions_over_dot_paths(): void
    {
        $context = ['pedido' => ['estado' => 'enviado', 'total' => 80], 'resp' => '{"state":"paid"}'];

        $this->assertTrue($this->conditions->eval([['variable' => 'pedido.estado', 'operator' => '=', 'value' => 'enviado']], 'all', $context));
        $this->assertTrue($this->conditions->eval([['variable' => '{{pedido.total}}', 'operator' => '>', 'value' => '50']], 'all', $context));
        $this->assertTrue($this->conditions->eval([['variable' => 'resp.state', 'operator' => 'in', 'value' => ['paid', 'shipped']]], 'all', $context));
        $this->assertTrue($this->conditions->eval([['variable' => 'pedido.nada', 'operator' => 'is_empty']], 'all', $context));
    }

    // ─── validator ─────────────────────────────────────────────────────────────

    private function validate(array $nodes): array
    {
        $flow = new ChatFlow;
        $flow->nodes = $nodes;

        return (new ChatFlowValidator)->validate($flow);
    }

    private function n(string $id, string $type, ?string $parent, array $data = []): array
    {
        return ['id' => $id, 'type' => $type, 'parentId' => $parent, 'label' => $id, 'data' => $data];
    }

    public function test_validator_flags_bad_collect_input_config(): void
    {
        $base = fn (array $data) => $this->validate([
            $this->n('s', 'start', null),
            $this->n('c', 'collect_input', 's', $data + ['variable_name' => 'v']),
            $this->n('e', 'end', 'c'),
        ])['errors'];

        $this->assertSame([], $base(['validation' => 'order_ref']));
        $this->assertSame([], $base(['validation' => 'regex', 'pattern' => '^\d+$']));
        $this->assertNotEmpty($base(['validation' => 'regex', 'pattern' => '(a+)+$']));
        $this->assertNotEmpty($base(['validation' => 'regex', 'pattern' => '(']));
        $this->assertNotEmpty($base(['validation' => 'enum', 'allowed' => []]));
        $this->assertSame([], $base(['validation' => 'enum', 'allowed' => ['a']]));
        $this->assertNotEmpty($base(['validation' => 'inventada']));
        $this->assertNotEmpty($base(['skip_if_set' => true, 'variable_name' => '']));
    }

    public function test_validator_checks_operator_value_and_match(): void
    {
        $errors = fn (array $item) => $this->validate([
            $this->n('s', 'start', null),
            $this->n('b', 'branches', 's'),
            $this->n('i', 'branchItem', 'b', $item),
            $this->n('e', 'end', 'i'),
        ])['errors'];
        $c = fn (string $op, mixed $value = '') => ['conditions' => [['variable' => 'customer_name', 'operator' => $op, 'value' => $value]]];

        $this->assertSame([], $errors($c('is_empty')));
        $this->assertSame([], $errors($c('between', ['1', '5']) + ['match' => 'any']));
        $this->assertSame([], $errors($c('in', ['a', 'b'])));
        $this->assertNotEmpty($errors($c('between', ['9', '5'])));
        $this->assertNotEmpty($errors($c('between', ['1'])));
        $this->assertNotEmpty($errors($c('in', [])));
        $this->assertNotEmpty($errors($c('>', 'abc')));
        $this->assertNotEmpty($errors($c('=', '')));
        $this->assertNotEmpty($errors($c('regex', '(a+)+$')));
        $this->assertNotEmpty($errors($c('nope', 'x')));
        $this->assertNotEmpty($errors($c('=', 'x') + ['match' => 'maybe']));
    }

    public function test_validator_accepts_dot_paths_whose_root_is_defined(): void
    {
        $warnings = fn (string $text) => $this->validate([
            $this->n('s', 'start', null),
            $this->n('h', 'http_request', 's', ['save_to' => 'pedido']),
            $this->n('m', 'message', 'h', ['text' => $text]),
            $this->n('e', 'end', 'm'),
        ])['warnings'];

        $this->assertSame([], $warnings('Estado: {{pedido.estado}}'));
        $this->assertNotEmpty($warnings('Estado: {{otro.estado}}'));
    }

    public function test_regex_errors_helper_for_save_time(): void
    {
        $validator = new ChatFlowValidator;

        $this->assertSame([], $validator->regexErrors([$this->n('c', 'collect_input', null, ['validation' => 'regex', 'pattern' => '^\d+$'])]));
        $this->assertCount(1, $validator->regexErrors([$this->n('c', 'collect_input', null, ['validation' => 'regex', 'pattern' => '(a+)+$'])]));
        $this->assertCount(1, $validator->regexErrors([$this->n('i', 'branchItem', null, ['conditions' => [['variable' => 'x', 'operator' => 'regex', 'value' => '(a+)+$']]])]));
    }
}
