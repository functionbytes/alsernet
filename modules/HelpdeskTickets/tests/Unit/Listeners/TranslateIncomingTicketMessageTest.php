<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Listeners\TranslateIncomingTicketMessage;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

class TranslateIncomingTicketMessageTest extends TestCase
{
    use SharesHelpdeskPdo;

    /**
     * Modules\Helpdesk\Models\Setting::get()/set() cachean en Redis fuera de
     * la transacción de BD (ver reference_setting_cache_escapes_db_transaction)
     * — Setting::set() de un test deja un valor contaminado ~5 min aunque la
     * fila se revierta, rompiendo cualquier otro test/tráfico real que llame
     * a Setting::get() con la misma key después.
     */
    private const DEFAULT_TARGET_KEY = 'helpdesk:setting:helpdesktranslate.default_target';

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(CachedTranslator::class)) {
            $this->markTestSkipped('HelpdeskTranslate no está instalado en este entorno.');
        }

        Cache::forget(self::DEFAULT_TARGET_KEY);

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

    private function makeTicketWithMessage(string $body, ?string $customerLanguage = null, bool $fromAgent = false, bool $internal = false): array
    {
        // 'language' es NOT NULL en helpdesk_customers — 'es' es el mismo
        // valor de siembra real por defecto, que el listener trata como
        // "desconocido" (dispara detección) salvo que el test pida otro.
        $customer = Customer::factory()->create(['language' => $customerLanguage ?? 'es']);
        $ticket = Ticket::create([
            'subject' => 'Test',
            'description' => 'Test description.',
            'customer_id' => $customer->id,
            'status_id' => TicketStatus::where('slug', 'open')->first()->id,
            'priority' => 'normal',
            'source' => 'email',
        ]);

        $item = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $customer->id,
            'user_id' => $fromAgent ? User::factory()->create()->id : null,
            'body' => $body,
            'is_internal' => $internal,
        ]);

        return [$ticket, $customer, $item];
    }

    public function test_detects_language_and_translates_customer_message(): void
    {
        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldReceive('detectLanguage')->once()->andReturn('en');
            $mock->shouldReceive('translate')
                ->once()
                ->with('Hello, I need help.', 'es', 'en', 'auto_incoming')
                ->andReturn('Hola, necesito ayuda.');
        });

        [, $customer, $item] = $this->makeTicketWithMessage('Hello, I need help.');

        app(TranslateIncomingTicketMessage::class)->handle(new MessageAdded($item));

        $item->refresh();
        $customer->refresh();

        $this->assertSame('en', $customer->language);
        $this->assertSame('en', $item->source_locale);
        $this->assertSame('Hola, necesito ayuda.', $item->translated_body);
    }

    public function test_skips_agent_messages(): void
    {
        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldNotReceive('detectLanguage');
            $mock->shouldNotReceive('translate');
        });

        [, , $item] = $this->makeTicketWithMessage('Hola, aquí tenés la respuesta.', fromAgent: true);

        app(TranslateIncomingTicketMessage::class)->handle(new MessageAdded($item));

        $this->assertNull($item->fresh()->translated_body);
    }

    public function test_skips_internal_notes(): void
    {
        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldNotReceive('detectLanguage');
            $mock->shouldNotReceive('translate');
        });

        [, , $item] = $this->makeTicketWithMessage('Nota interna.', internal: true);

        app(TranslateIncomingTicketMessage::class)->handle(new MessageAdded($item));

        $this->assertNull($item->fresh()->translated_body);
    }

    public function test_skips_when_stored_language_already_matches_agent_locale(): void
    {
        // 'es' es el valor de siembra por defecto y se trata como
        // "desconocido" (dispara detección); para probar "ya conocido y =
        // agente" sin ese caso especial, se fuerza el locale del agente a
        // 'en' y el cliente ya tiene 'en' guardado — ninguno de los dos debe
        // llamar al traductor.
        Setting::set('helpdesktranslate.default_target', 'en');

        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldNotReceive('detectLanguage');
            $mock->shouldNotReceive('translate');
        });

        [, , $item] = $this->makeTicketWithMessage('Hello, I need help with my order.', customerLanguage: 'en');

        app(TranslateIncomingTicketMessage::class)->handle(new MessageAdded($item));

        $this->assertNull($item->fresh()->translated_body);
    }

    public function test_skips_when_body_too_short(): void
    {
        $this->mock(CachedTranslator::class, function ($mock) {
            $mock->shouldNotReceive('detectLanguage');
            $mock->shouldNotReceive('translate');
        });

        [, , $item] = $this->makeTicketWithMessage('Ok');

        app(TranslateIncomingTicketMessage::class)->handle(new MessageAdded($item));

        $this->assertNull($item->fresh()->translated_body);
    }
}
