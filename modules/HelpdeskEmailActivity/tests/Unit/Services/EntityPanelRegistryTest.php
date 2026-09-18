<?php

namespace Modules\HelpdeskEmailActivity\Tests\Unit\Services;

use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntitySummaryProvider;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\EntityPanelRegistry;
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

    /**
     * Renderer que además sabe resumir su entidad — el caso de HelpdeskTickets.
     *
     * @param  array<string, mixed>|null  $summary
     */
    private function summaryRendererFor(string $entityType, ?array $summary): EmailLogEntityPanelRenderer
    {
        return new class($entityType, $summary) implements EmailLogEntityPanelRenderer, EmailLogEntitySummaryProvider
        {
            /** @param array<string, mixed>|null $summary */
            public function __construct(private readonly string $entityType, private readonly ?array $summary) {}

            public function supports(string $entityType): bool
            {
                return $entityType === $this->entityType;
            }

            public function render(EmailLog $emailLog): ?string
            {
                return null;
            }

            public function summary(EmailLog $emailLog): ?array
            {
                return $this->summary;
            }
        };
    }

    public function test_summary_comes_from_the_renderer_that_supports_the_entity_type(): void
    {
        $registry = new EntityPanelRegistry;
        $registry->register($this->summaryRendererFor('ticket', ['title' => 'TCK-1', 'badge' => 'Abierto']));

        $result = $registry->summaryFor($this->emailLogWithEntityType('ticket'));

        $this->assertSame(['title' => 'TCK-1', 'badge' => 'Abierto'], $result);
    }

    /**
     * El añadido es opcional: un renderer que solo implemente el contrato de
     * panel no debe romper summaryFor(), tiene que saltarse sin ruido.
     */
    public function test_summary_is_null_when_the_renderer_does_not_provide_one(): void
    {
        $registry = new EntityPanelRegistry;
        $registry->register($this->rendererFor('ticket', '<div>Hilo del ticket</div>'));

        $result = $registry->summaryFor($this->emailLogWithEntityType('ticket'));

        $this->assertNull($result);
    }

    public function test_summary_is_null_when_the_email_has_no_entity(): void
    {
        $registry = new EntityPanelRegistry;
        $registry->register($this->summaryRendererFor('ticket', ['title' => 'TCK-1']));

        $this->assertNull($registry->summaryFor($this->emailLogWithEntityType(null)));
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
