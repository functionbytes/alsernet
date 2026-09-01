<?php

namespace Modules\HelpdeskEmailLog\Tests\Unit\Services;

use Modules\HelpdeskEmailLog\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Services\EntityPanelRegistry;
use Tests\TestCase;
use Throwable;

/**
 * No usa DatabaseTransactions: EmailLog aquí solo se instancia en memoria
 * (nunca se guarda), así que no toca ninguna conexión real. Extiende
 * Tests\TestCase (no PHPUnit\Framework\TestCase puro) porque
 * EntityPanelRegistry::renderFor() llama a Log::warning() en el caso de
 * excepción, y esa facade necesita el contenedor de Laravel arrancado —
 * ver DomainAuthenticationCheckerTest.php para el mismo patrón con Cache.
 */
class EntityPanelRegistryTest extends TestCase
{
    private function emailLogWithEntityType(?string $entityType): EmailLog
    {
        return EmailLog::make(['entity_type' => $entityType]);
    }

    private function rendererFor(string $entityType, ?string $html): EmailLogEntityPanelRenderer
    {
        return new class($entityType, $html) implements EmailLogEntityPanelRenderer
        {
            public function __construct(private readonly string $entityType, private readonly ?string $html) {}

            public function supports(string $entityType): bool
            {
                return $entityType === $this->entityType;
            }

            public function render(EmailLog $emailLog): ?string
            {
                return $this->html;
            }
        };
    }

    public function test_renders_html_from_the_renderer_that_supports_the_entity_type(): void
    {
        $registry = new EntityPanelRegistry;
        $registry->register($this->rendererFor('ticket', '<div>Hilo del ticket</div>'));

        $result = $registry->renderFor($this->emailLogWithEntityType('ticket'));

        $this->assertSame('<div>Hilo del ticket</div>', $result);
    }

    public function test_returns_null_when_no_renderer_supports_the_entity_type(): void
    {
        $registry = new EntityPanelRegistry;
        $registry->register($this->rendererFor('ticket', '<div>Hilo del ticket</div>'));

        $result = $registry->renderFor($this->emailLogWithEntityType('order'));

        $this->assertNull($result);
    }

    public function test_returns_null_without_calling_any_renderer_when_entity_type_is_null(): void
    {
        $registry = new EntityPanelRegistry;

        // Renderer centinela: si renderFor() lo invocase pese a entity_type
        // ser null, la excepción lanzada aquí haría fallar el test (o, si
        // el catch de renderFor() la tragase, el resultado seguiría siendo
        // null y el test pasaría igual "por casualidad" — de ahí el segundo
        // assert explícito de que jamás se llamó a render()).
        $called = false;
        $registry->register(new class($called) implements EmailLogEntityPanelRenderer
        {
            public function __construct(private bool &$called) {}

            public function supports(string $entityType): bool
            {
                return true;
            }

            public function render(EmailLog $emailLog): ?string
            {
                $this->called = true;

                return '<div>no debería llegar aquí</div>';
            }
        });

        $result = $registry->renderFor($this->emailLogWithEntityType(null));

        $this->assertNull($result);
        $this->assertFalse($called);
    }

    public function test_swallows_exceptions_from_a_broken_renderer_and_returns_null(): void
    {
        $registry = new EntityPanelRegistry;
        $registry->register(new class implements EmailLogEntityPanelRenderer
        {
            public function supports(string $entityType): bool
            {
                return $entityType === 'ticket';
            }

            public function render(EmailLog $emailLog): ?string
            {
                throw new \RuntimeException('renderer roto de un módulo satélite');
            }
        });

        try {
            $result = $registry->renderFor($this->emailLogWithEntityType('ticket'));
        } catch (Throwable $e) {
            $this->fail('renderFor() no debe propagar la excepción del renderer, la lanzó: '.$e->getMessage());
        }

        $this->assertNull($result);
    }
}
