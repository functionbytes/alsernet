<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketOutboundTranslator;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

class TicketOutboundTranslatorTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketOutboundTranslator $translator;

    /**
     * Modules\Helpdesk\Models\Setting cachea en Redis fuera de la
     * transacción de BD — ver reference_setting_cache_escapes_db_transaction.
     * Otro test (p.ej. TranslateIncomingTicketMessageTest) puede dejar
     * "helpdesktranslate.default_target" contaminado aunque su fila en BD
     * se revierta; estos tests asumen que cae al default 'es' del config.
     */
    private const DEFAULT_TARGET_KEY = 'helpdesk:setting:helpdesktranslate.default_target';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(self::DEFAULT_TARGET_KEY);

        $this->translator = new TicketOutboundTranslator;

        TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    protected function tearDown(): void
    {
        Cache::forget(self::DEFAULT_TARGET_KEY);

        parent::tearDown();
    }

    private function makeTicket(?string $customerLanguage): Ticket
    {
        // 'language' es NOT NULL en helpdesk_customers — un null explícito
        // rompe la constraint (el default 'es' de la columna solo aplica si
        // se omite del INSERT, no si se manda null). "Idioma desconocido"
        // para TicketOutboundTranslator es string vacío, no ausencia de fila.
        $customer = Customer::factory()->create(['language' => $customerLanguage ?? '']);

        return Ticket::create([
            'subject' => 'Test',
            'description' => 'Test description.',
            'customer_id' => $customer->id,
            'status_id' => TicketStatus::where('slug', 'open')->first()->id,
            'priority' => 'normal',
            'source' => 'email',
        ]);
    }

    public function test_returns_text_unchanged_when_customer_language_is_unknown(): void
    {
        $ticket = $this->makeTicket(null);

        $result = $this->translator->translateForCustomer($ticket, 'Hola, gracias por tu mensaje.');

        $this->assertSame('Hola, gracias por tu mensaje.', $result);
    }

    public function test_returns_text_unchanged_when_text_is_too_short(): void
    {
        $ticket = $this->makeTicket('en');

        $result = $this->translator->translateForCustomer($ticket, 'Ok');

        $this->assertSame('Ok', $result);
    }

    public function test_returns_text_unchanged_when_customer_language_matches_agent_locale(): void
    {
        // helpdesktranslate.default_target cae a 'es' si no hay Setting —
        // mismo idioma que el cliente, no debería llamar al traductor.
        $ticket = $this->makeTicket('es');

        $result = $this->translator->translateForCustomer($ticket, 'Hola, gracias por tu mensaje.');

        $this->assertSame('Hola, gracias por tu mensaje.', $result);
    }

    public function test_translates_when_customer_language_differs_from_agent_locale(): void
    {
        if (! class_exists(CachedTranslator::class)) {
            $this->markTestSkipped('HelpdeskTranslate no está instalado en este entorno.');
        }

        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('Hola, gracias por tu mensaje.', 'en', 'es', 'auto_outgoing')
                ->andReturn('Hello, thanks for your message.');
        });

        $ticket = $this->makeTicket('en');

        $result = $this->translator->translateForCustomer($ticket, 'Hola, gracias por tu mensaje.');

        $this->assertSame('Hello, thanks for your message.', $result);
    }

    public function test_falls_back_to_original_text_when_translator_returns_null(): void
    {
        if (! class_exists(CachedTranslator::class)) {
            $this->markTestSkipped('HelpdeskTranslate no está instalado en este entorno.');
        }

        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldReceive('translate')->once()->andReturn(null);
        });

        $ticket = $this->makeTicket('en');

        $result = $this->translator->translateForCustomer($ticket, 'Hola, gracias por tu mensaje.');

        $this->assertSame('Hola, gracias por tu mensaje.', $result);
    }
}
