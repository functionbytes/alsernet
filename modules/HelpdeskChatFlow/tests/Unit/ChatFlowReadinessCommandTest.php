<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Console\Commands\ChatFlowReadinessCommand;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ChatFlowReadinessCommandTest extends TestCase
{
    private function make(array $overrides = []): ChatFlowReadinessCommand
    {
        $results = $overrides + [
            'moduleEnabled' => ['OK', 'ok'],
            'killSwitch' => ['OK', 'ok'],
            'openAiKey' => ['OK', 'ok'],
            'queue' => ['OK', 'ok'],
            'reverb' => ['OK', 'ok'],
            'promptLibrary' => ['OK', 'ok'],
            'actionCatalog' => ['OK', 'ok'],
            'aiFlows' => ['OK', 'ok'],
            'procedures' => ['OK', 'ok'],
            'bridge' => ['OK', 'ok'],
            'regressionTests' => ['OK', 'ok'],
        ];

        return $this->prepare(new class($results) extends ChatFlowReadinessCommand
        {
            public function __construct(private array $results)
            {
                parent::__construct();
            }

            protected function moduleEnabled(): array
            {
                return $this->results['moduleEnabled'];
            }

            protected function killSwitch(): array
            {
                return $this->results['killSwitch'];
            }

            protected function openAiKey(): array
            {
                return $this->results['openAiKey'];
            }

            protected function queue(): array
            {
                return $this->results['queue'];
            }

            protected function reverb(): array
            {
                return $this->results['reverb'];
            }

            protected function promptLibrary(): array
            {
                return $this->results['promptLibrary'];
            }

            protected function actionCatalog(): array
            {
                return $this->results['actionCatalog'];
            }

            protected function aiFlows(): array
            {
                return $this->results['aiFlows'];
            }

            protected function procedures(): array
            {
                return $this->results['procedures'];
            }

            protected function bridge(): array
            {
                return $this->results['bridge'];
            }

            protected function regressionTests(): array
            {
                return $this->results['regressionTests'];
            }
        });
    }

    private function prepare(ChatFlowReadinessCommand $command): ChatFlowReadinessCommand
    {
        $command->setLaravel($this->app);

        return $command;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function execute(ChatFlowReadinessCommand $command): array
    {
        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        return [$code, $tester->getDisplay()];
    }

    public function test_exit_code_is_success_when_everything_is_ok(): void
    {
        [$code, $output] = $this->execute($this->make());

        $this->assertSame(0, $code);
        $this->assertStringContainsString('listo para el piloto', $output);
    }

    public function test_blocking_ko_makes_the_command_fail_with_advice(): void
    {
        [$code, $output] = $this->execute($this->make(['queue' => ['KO', 'Horizon no esta en ejecucion', 'Arrancar horizon']]));

        $this->assertSame(1, $code);
        $this->assertStringContainsString('[KO]', $output);
        $this->assertStringContainsString('Arrancar horizon', $output);
    }

    public function test_warnings_do_not_block(): void
    {
        [$code, $output] = $this->execute($this->make(['actionCatalog' => ['AVISO', 'ninguna accion activa', 'Ejecutar seeder']]));

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Ejecutar seeder', $output);
    }

    public function test_openai_key_is_never_printed(): void
    {
        config(['services.openai.key' => 'sk-secret-value-123']);

        [, $output] = $this->execute($this->prepare(new class extends ChatFlowReadinessCommand
        {
            protected function moduleEnabled(): array
            {
                return ['OK', 'ok'];
            }

            protected function killSwitch(): array
            {
                return ['OK', 'ok'];
            }

            protected function queue(): array
            {
                return ['OK', 'ok'];
            }

            protected function promptLibrary(): array
            {
                return ['OK', 'ok'];
            }

            protected function actionCatalog(): array
            {
                return ['OK', 'ok'];
            }

            protected function aiFlows(): array
            {
                return ['OK', 'ok'];
            }

            protected function procedures(): array
            {
                return ['OK', 'ok'];
            }

            protected function bridge(): array
            {
                return ['OK', 'ok'];
            }

            protected function regressionTests(): array
            {
                return ['OK', 'ok'];
            }
        }));

        $this->assertStringContainsString('[OK]    OPENAI_API_KEY configurada', $output);
        $this->assertStringNotContainsString('sk-secret-value-123', $output);
    }

    public function test_exception_in_a_check_is_reported_as_blocking_ko(): void
    {
        $command = $this->prepare(new class extends ChatFlowReadinessCommand
        {
            protected function moduleEnabled(): array
            {
                throw new \RuntimeException('boom');
            }
        });

        [$code, $output] = $this->execute($command);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Error al comprobar: boom', $output);
    }
}
