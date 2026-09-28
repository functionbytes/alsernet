<?php

namespace Modules\HelpdeskChatFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestRunner;

/**
 * CI-friendly regression runner: executes a flow's (or every active flow's)
 * test cases and exits non-zero if any fail, so a pipeline can block a deploy
 * that would break the chatbot.
 */
class RunChatFlowTestCasesCommand extends Command
{
    protected $signature = 'chatflow:test-cases
        {flow? : ID o UID del flow a comprobar}
        {--all : Ejecuta los escenarios de todos los flows activos}';

    protected $description = 'Run a chat flow\'s regression test cases and report pass/fail';

    public function __construct(
        private readonly ChatFlowTestRunner $runner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $flows = $this->resolveFlows();

        if ($flows === null) {
            return self::FAILURE;
        }

        if ($flows->isEmpty()) {
            $this->components->info('No hay flows que comprobar.');

            return self::SUCCESS;
        }

        [$rows, $anyFailed] = $this->runAll($flows);

        if (empty($rows)) {
            $this->components->info('Ninguno de los flows seleccionados tiene escenarios de prueba.');

            return self::SUCCESS;
        }

        $this->table(['Flow', 'Escenario', 'Resultado', 'Motivo'], $rows);

        return $anyFailed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: array<int, array<int, string>>, 1: bool}
     */
    private function runAll(Collection $flows): array
    {
        $rows = [];
        $anyFailed = false;

        foreach ($flows as $flow) {
            foreach ($flow->testCases as $testCase) {
                $result = $this->runner->run($flow, $testCase->steps ?? []);

                $testCase->update([
                    'last_result' => $result['passed'] ? 'passed' : 'failed',
                    'last_run_at' => now(),
                ]);

                $anyFailed = $anyFailed || ! $result['passed'];

                $rows[] = [
                    $flow->name,
                    $testCase->name,
                    $result['passed'] ? 'OK' : 'FALLO',
                    $result['passed'] ? '' : $this->failureReason($result),
                ];
            }
        }

        return [$rows, $anyFailed];
    }

    /**
     * @return Collection<int, ChatFlow>|null null when the given identifier didn't resolve to a flow
     */
    private function resolveFlows(): ?Collection
    {
        if ($this->option('all')) {
            return ChatFlow::query()->active()->with('testCases')->get();
        }

        $identifier = $this->argument('flow');

        if ($identifier === null) {
            $this->components->error('Indica el ID o UID del flow, o usa --all.');

            return null;
        }

        $flow = ChatFlow::query()
            ->where('id', $identifier)
            ->orWhere('uid', $identifier)
            ->with('testCases')
            ->first();

        if (! $flow) {
            $this->components->error("No se encontró ningún flow con el identificador «{$identifier}».");

            return null;
        }

        return collect([$flow]);
    }

    /**
     * @param  array{passed: bool, error: string|null, steps: array<int, array{input: string, expect: string, matched: bool, got: string}>}  $result
     */
    private function failureReason(array $result): string
    {
        if ($result['error']) {
            return $result['error'];
        }

        foreach ($result['steps'] as $step) {
            if (! $step['matched']) {
                return "esperaba «{$step['expect']}», obtuvo «{$step['got']}»";
            }
        }

        return '';
    }
}
