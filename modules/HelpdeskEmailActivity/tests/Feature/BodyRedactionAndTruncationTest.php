<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Tests\Fixtures\RedactedTestMail;
use Modules\HelpdeskEmailActivity\Tests\Fixtures\TrackedTestMail;
use Tests\TestCase;

class BodyRedactionAndTruncationTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: InspectsMailMessage::bodyOf()/maxBodyBytes() leen
    // vía Modules\Core\Models\Setting (conexión default = mysql en este
    // entorno) — sin declararla, Setting::set() de este archivo escribiría una
    // fila REAL sin rollback (mismo gotcha documentado en otros tests del
    // módulo, p. ej. BounceMailboxesControllerTest).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml fuerza MAIL_MAILER=array (force="true"), pero dentro de
        // Docker getenv()/env() ignoran ese force y siguen devolviendo 'smtp'
        // real, así que config('mail.default') caía en 'smtp' y Mail::to()->send()
        // usaba el transporte SMTP real en vez del fake 'array' durante el test.
        config(['mail.default' => 'array']);

        // Mismo mismatch getenv()/Docker para QUEUE_CONNECTION (phpunit.xml
        // fuerza 'sync', pero env() real devuelve 'redis'): LogEmailSent
        // implementa ShouldQueue, así que sin este fix se encolaba en el
        // Redis REAL en vez de correr en línea — un worker real, en un
        // proceso totalmente distinto y sin la transacción de este test,
        // procesaba el job más tarde y dejaba filas nuevas sin rollback
        // (confirmado en vivo: 32 filas reales filtradas con los asuntos de
        // este archivo, ya limpiadas). Ver memoria del proyecto:
        // reference_phpunit_docker_getenv_mismatch_five_vars.
        config(['queue.default' => 'sync']);

        // Hallazgo real: este entorno compartido tiene una fila real
        // helpdeskemailactivity.store_body='0' en la tabla settings — bodyOf()
        // prioriza SIEMPRE esa fila sobre el config()->set() que hacían estos
        // tests (el config solo es fallback si NO existe fila), así que con
        // store_body='0' real, bodyOf() devolvía null ANTES de llegar siquiera
        // a evaluar redacción/truncado — los tests de redacción "pasaban" sin
        // ejercer de verdad esa lógica. Se fija aquí a '1' (vía Setting::set(),
        // envuelto en la transacción del test gracias a 'mysql' de arriba) para
        // que todos los tests de este archivo prueben lo que dicen probar.
        Setting::set('helpdeskemailactivity.store_body', '1');
    }

    /**
     * Setting::get() cachea 10 min en el store de caché REAL (CACHE_STORE
     * también cae en Redis real por el mismo mismatch getenv()/Docker que
     * MAIL_MAILER — ver memoria del proyecto), fuera de la transacción de BD
     * de este test — sin este forget(), un valor de prueba (store_body='1'
     * o max_body_bytes=64) quedaría cacheado hasta 10 min después de que este
     * test termine y su fila haga rollback, afectando lecturas reales
     * posteriores (otros tests, u otra sesión en este entorno compartido).
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Cache::forget('setting_helpdeskemailactivity.store_body');
        Cache::forget('setting_helpdeskemailactivity.max_body_bytes');
    }

    public function test_body_is_redacted_for_mailables_marked_as_sensitive(): void
    {
        Mail::to('user@example.test')->send(new RedactedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('Sensitive: your reset link', $log->subject);
        $this->assertNull($log->body_html);
        $this->assertNull($log->body_text);
        $this->assertTrue($log->metadata['redacted'] ?? false);
    }

    public function test_body_is_redacted_when_class_is_listed_in_config(): void
    {
        config()->set('helpdeskemailactivity.redact_body_for_classes', [TrackedTestMail::class]);

        Mail::to('user@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNull($log->body_html);
        $this->assertTrue($log->metadata['redacted'] ?? false);
    }

    public function test_body_is_redacted_when_module_is_listed_in_config(): void
    {
        config()->set('helpdeskemailactivity.redact_body_for_modules', ['HelpdeskTickets']);

        Mail::to('user@example.test')->send(new TrackedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNull($log->body_html);
        $this->assertTrue($log->metadata['redacted'] ?? false);
    }

    public function test_body_is_truncated_when_exceeding_max_bytes(): void
    {
        Setting::set('helpdeskemailactivity.max_body_bytes', 64);

        $log = EmailLog::factory()->create([
            'body_html' => str_repeat('A', 80),
        ]);

        // Save then re-fetch — truncation happens at write time via listeners,
        // so we exercise the helper through a fresh send here.
        Mail::raw(str_repeat('B', 300), function ($m) {
            $m->to('user@example.test')->subject('Big body over limit');
        });

        // latest('id')->first() sin acotar es inseguro en este entorno: la BD
        // de test se comparte con otras sesiones que insertan EmailLog reales
        // de forma concurrente — filtrar por el asunto único de este envío
        // evita capturar la fila de otra corrida ajena (confirmado en vivo:
        // este mismo test falló una vez por esta causa, pasó aislado).
        $fresh = EmailLog::query()->where('subject', 'Big body over limit')->latest('id')->first();

        $this->assertNotNull($fresh->body_text);
        $this->assertLessThanOrEqual(
            64 + strlen("\n<!-- [helpdeskemailactivity] contenido truncado -->"),
            strlen($fresh->body_text),
        );
        $this->assertStringContainsString('contenido truncado', $fresh->body_text);
        $this->assertNotNull($log->refresh()); // sanity: factory created row still exists
    }

    public function test_truncation_does_not_split_multibyte_characters(): void
    {
        // 63 bytes de límite sobre un cuerpo de 'é' (2 bytes cada uno): un
        // substr() binario cortaría el carácter nº 32 por la mitad dejando
        // UTF-8 inválido; mb_strcut debe retroceder al límite del carácter.
        Setting::set('helpdeskemailactivity.max_body_bytes', 63);

        Mail::raw(str_repeat('é', 40), function ($m) {
            $m->to('user@example.test')->subject('Multibyte body truncation');
        });

        // Ver comentario en test_body_is_truncated_when_exceeding_max_bytes:
        // acotar por asunto único evita capturar una fila de otra sesión.
        $log = EmailLog::query()->where('subject', 'Multibyte body truncation')->latest('id')->first();

        $this->assertNotNull($log->body_text);

        $stored = str_replace(EmailLog::TRUNCATION_MARKER, '', $log->body_text);

        $this->assertLessThanOrEqual(63, strlen($stored));
        $this->assertTrue(
            mb_check_encoding($stored, 'UTF-8'),
            'El cuerpo truncado no debe contener secuencias UTF-8 rotas.',
        );
        $this->assertSame(str_repeat('é', 31), $stored);
    }

    public function test_truncated_body_sets_metadata_flag_and_blocks_resend(): void
    {
        Setting::set('helpdeskemailactivity.max_body_bytes', 64);

        Mail::raw(str_repeat('B', 300), function ($m) {
            $m->to('user@example.test')->subject('Big body blocks resend');
        });

        // Ver comentario en test_body_is_truncated_when_exceeding_max_bytes.
        $log = EmailLog::query()->where('subject', 'Big body blocks resend')->latest('id')->first();

        $this->assertTrue($log->metadata['truncated'] ?? false);
        $this->assertTrue($log->isBodyTruncated());
        $this->assertFalse($log->isResendable());
    }

    public function test_legacy_truncated_body_is_detected_by_marker(): void
    {
        // Filas anteriores al flag metadata.truncated: solo llevan el marcador.
        $log = EmailLog::factory()->create([
            'body_html' => '<p>parcial</p>'.EmailLog::TRUNCATION_MARKER,
        ]);

        $this->assertTrue($log->isBodyTruncated());
        $this->assertFalse($log->isResendable());
    }

    public function test_redacted_log_is_not_resendable(): void
    {
        Mail::to('user@example.test')->send(new RedactedTestMail);

        $log = EmailLog::query()->latest('id')->first();

        $this->assertTrue($log->isBodyRedacted());
        $this->assertFalse($log->isResendable());
    }
}
