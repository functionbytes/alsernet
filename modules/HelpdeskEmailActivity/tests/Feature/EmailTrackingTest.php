<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Listeners\LogEmailSent;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Tests\Fixtures\TrackedTestMail;
use Tests\TestCase;

class EmailTrackingTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog vive en la conexión default de la app
    // (mysql en este entorno, no mariadb/helpdesk) — mismo gotcha ya
    // documentado y corregido en EmailLogControllerTest/EmailOpenTrackingTest.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml fuerza MAIL_MAILER=array (force="true"), pero dentro de
        // Docker getenv()/env() ignoran ese force y siguen devolviendo 'smtp'
        // real, así que config('mail.default') caía en 'smtp' y Mail::to()->send()
        // usaba el transporte SMTP real en vez del fake 'array' durante el test.
        config(['mail.default' => 'array']);

        // Mismo problema, mismo mecanismo, otra variable: phpunit.xml también
        // fuerza QUEUE_CONNECTION=sync, pero getenv('QUEUE_CONNECTION') sigue
        // devolviendo 'redis' real en Docker. LogEmailSent implementa
        // ShouldQueue (queue 'emails'), así que sin esto el listener se apila
        // en el Redis real en vez de ejecutarse en línea, y la aserción sobre
        // EmailStatus::Sent corre antes de que nada lo haya procesado —
        // confirmado viendo el job encolado en la conexión por defecto.
        config(['queue.default' => 'sync']);
    }

    /**
     * Setting::get() cachea 10 min en el store de caché real (fuera de la
     * transacción de BD de este test) — sin este forget(), un pixel_tracking_
     * enabled='0' de prueba quedaría cacheado tras el rollback de la fila,
     * afectando lecturas posteriores. Mismo gotcha ya documentado en
     * BodyRedactionAndTruncationTest.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Cache::forget('setting_helpdeskemailactivity.pixel_tracking_enabled');
        Cache::forget('setting_helpdeskemailactivity.store_body');
    }

    public function test_sent_listener_is_queued_off_the_request(): void
    {
        $listener = new LogEmailSent;

        $this->assertInstanceOf(ShouldQueue::class, $listener);
        $this->assertSame('emails', $listener->queue);
        $this->assertSame(3, $listener->tries);
        $this->assertSame(10, $listener->backoff);
    }

    public function test_sent_listener_is_registered_for_message_sent(): void
    {
        $raw = app('events')->getRawListeners()[MessageSent::class] ?? [];

        $this->assertContains(LogEmailSent::class, $raw);
    }

    public function test_sending_an_email_records_a_sent_log_entry(): void
    {
        Mail::to('person@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(EmailStatus::Sent, $log->status);
        $this->assertSame('Tracked test email', $log->subject);
        $this->assertSame(['person@example.test'], $log->to_addresses);
        $this->assertNotNull($log->sent_at);
        $this->assertNotNull($log->message_id);
    }

    public function test_tracking_context_is_captured_from_the_mailable(): void
    {
        Mail::to('person@example.test')->send(new TrackedTestMail(99));

        $log = EmailLog::query()->latest('id')->first();

        $this->assertSame('HelpdeskTickets', $log->module);
        $this->assertSame('Ticket', $log->entity_type);
        $this->assertSame(99, $log->entity_id);
        $this->assertSame(TrackedTestMail::class, $log->mailable_class);
    }

    public function test_internal_tracking_headers_do_not_leak_to_the_recipient(): void
    {
        Mail::to('person@example.test')->send(new TrackedTestMail);

        $sent = Mail::getSymfonyTransport()->messages();

        $this->assertCount(1, $sent);

        $headers = $sent->first()->getOriginalMessage()->getHeaders();

        foreach (['X-Email-Module', 'X-Entity-Type', 'X-Entity-Id', 'X-Mailable-Class'] as $header) {
            $this->assertFalse($headers->has($header), "Header {$header} should have been stripped.");
        }

        $this->assertTrue($headers->has('Message-ID'));
    }

    public function test_only_one_log_row_is_kept_per_email(): void
    {
        // Delta y no total global: robusto a filas residuales en la BD compartida.
        $before = EmailLog::query()->count();

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $this->assertSame($before + 1, EmailLog::query()->count());
    }

    public function test_body_is_not_stored_when_disabled(): void
    {
        // El listener lee Setting::get() ANTES que config(): tocar solo la
        // config no desactivaba nada, y este test pasaba únicamente porque el
        // ajuste estaba en '0' en la base de datos de este entorno. Al
        // activarlo quedó al descubierto. Se escribe la fila real (la
        // transacción del test la revierte) y tearDown() limpia la caché de
        // Setting::get(), que vive fuera de esa transacción.
        Setting::set('helpdeskemailactivity.store_body', '0');
        Cache::forget('setting_helpdeskemailactivity.store_body');

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNull($log->body_html);
        $this->assertNull($log->body_text);
    }

    public function test_open_tracking_pixel_is_injected_by_default_for_helpdesktickets_module(): void
    {
        Mail::to('person@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();
        $html = Mail::getSymfonyTransport()->messages()->first()->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString(route('helpdeskemailactivity.pixel', $log), $html);
        $this->assertTrue($log->hasOpenTracking());
    }

    /**
     * La lista de módulos con seguimiento es configurable
     * (helpdeskemailactivity.tracked_modules). Antes era una comparación literal
     * contra 'HelpdeskTickets', así que cualquier otro módulo que midiera
     * aperturas mostraba 0% para siempre.
     */
    public function test_tracking_follows_the_configured_module_list(): void
    {
        config(['helpdeskemailactivity.tracked_modules' => ['OtroModulo']]);

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        // TrackedTestMail declara el módulo HelpdeskTickets: fuera de la lista,
        // ya no lleva píxel.
        $this->assertFalse($log->hasOpenTracking());

        config(['helpdeskemailactivity.tracked_modules' => ['HelpdeskTickets', 'OtroModulo']]);

        Mail::to('otra@example.test')->send(new TrackedTestMail);

        $this->assertTrue(EmailLog::query()->latest('id')->first()->hasOpenTracking());
    }

    public function test_open_tracking_pixel_is_not_injected_when_setting_disabled(): void
    {
        // Setting::set() escribe vía la conexión default (mysql en este
        // entorno, ya declarada en $connectionsToTransact), así que el rollback
        // de la transacción del test limpia la fila; tearDown() se encarga de
        // limpiar la caché aparte (ver comentario en tearDown()).
        Setting::set('helpdeskemailactivity.pixel_tracking_enabled', '0');

        Mail::to('person@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();
        $html = Mail::getSymfonyTransport()->messages()->first()->getOriginalMessage()->getHtmlBody();

        $this->assertStringNotContainsString(route('helpdeskemailactivity.pixel', $log), $html);
        $this->assertFalse($log->hasOpenTracking());
    }
}
