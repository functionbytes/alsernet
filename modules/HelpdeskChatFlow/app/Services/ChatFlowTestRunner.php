<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\HelpdeskChatFlow\Models\ChatFlow;

/**
 * Runs a saved test scenario (a sequence of customer inputs and the text each
 * bot reply is expected to contain) against a flow, using the stateless
 * simulator. Powers regression testing — re-run scenarios after editing a flow.
 */
class ChatFlowTestRunner
{
    public function __construct(
        private readonly ChatFlowTestSimulator $simulator,
    ) {}

    /**
     * @param  array<int, array{input?: string, expect_contains?: string}>  $steps
     * @return array{passed: bool, error: string|null, steps: array<int, array{input: string, expect: string, matched: bool, got: string}>}
     */
    public function run(ChatFlow $flow, array $steps): array
    {
        $session = $this->simulator->start($flow->nodes ?? []);

        if (isset($session['error'])) {
            return ['passed' => false, 'error' => $session['error'], 'steps' => []];
        }

        $key = $session['session_key'];
        $results = [];
        $passed = true;

        foreach ($steps as $step) {
            $input = (string) ($step['input'] ?? '');
            $expect = trim((string) ($step['expect_contains'] ?? ''));

            $reply = $this->simulator->reply($key, $input);
            $got = $this->collectText($reply['messages'] ?? []);

            $matched = $expect === '' || mb_stripos($got, $expect) !== false;
            if (! $matched) {
                $passed = false;
            }

            $results[] = [
                'input' => $input,
                'expect' => $expect,
                'matched' => $matched,
                'got' => mb_substr(trim($got), 0, 200),
            ];
        }

        return ['passed' => $passed, 'error' => null, 'steps' => $results];
    }

    /**
     * @param  array<int, array<string,mixed>>  $messages
     */
    private function collectText(array $messages): string
    {
        return collect($messages)
            ->map(fn ($m) => $m['text'] ?? '')
            ->filter()
            ->implode(' ');
    }
}
