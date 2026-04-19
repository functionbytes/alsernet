<?php

namespace Modules\Notification\Tests\Unit;

use Illuminate\Support\Facades\Log;
use Modules\Notification\Services\NotificationTypeRegistry;
use Tests\TestCase;

class NotificationTypeRegistryTest extends TestCase
{
    private NotificationTypeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new NotificationTypeRegistry;
    }

    // -------------------------------------------------------------------------
    // register / get
    // -------------------------------------------------------------------------

    public function test_register_stores_type_correctly(): void
    {
        $this->registry->register('order.created', 'Pedido creado', 'Se crea un pedido nuevo', ['mail', 'database']);

        $type = $this->registry->get('order.created');

        $this->assertNotNull($type);
        $this->assertSame('order.created', $type['key']);
        $this->assertSame('Pedido creado', $type['label']);
        $this->assertSame('Se crea un pedido nuevo', $type['description']);
        $this->assertSame(['mail', 'database'], $type['defaultChannels']);
    }

    public function test_get_returns_null_for_unknown_key(): void
    {
        $result = $this->registry->get('unknown.type');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // has
    // -------------------------------------------------------------------------

    public function test_has_returns_true_for_registered_type(): void
    {
        $this->registry->register('ticket.closed', 'Ticket cerrado');

        $this->assertTrue($this->registry->has('ticket.closed'));
    }

    public function test_has_returns_false_for_missing_type(): void
    {
        $this->assertFalse($this->registry->has('missing.type'));
    }

    // -------------------------------------------------------------------------
    // all
    // -------------------------------------------------------------------------

    public function test_all_returns_all_registered_types(): void
    {
        $this->registry->register('type.one', 'Type One');
        $this->registry->register('type.two', 'Type Two');
        $this->registry->register('type.three', 'Type Three');

        $all = $this->registry->all();

        $this->assertCount(3, $all);
        $this->assertArrayHasKey('type.one', $all);
        $this->assertArrayHasKey('type.two', $all);
        $this->assertArrayHasKey('type.three', $all);
    }

    // -------------------------------------------------------------------------
    // duplicate registration
    // -------------------------------------------------------------------------

    public function test_register_duplicate_logs_warning_and_overwrites(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'duplicado')
                    && $context['existing_label'] === 'Label Original'
                    && $context['new_label'] === 'Label Nuevo';
            });

        $this->registry->register('dup.key', 'Label Original');
        $this->registry->register('dup.key', 'Label Nuevo');

        $type = $this->registry->get('dup.key');

        $this->assertSame('Label Nuevo', $type['label']);
    }
}
