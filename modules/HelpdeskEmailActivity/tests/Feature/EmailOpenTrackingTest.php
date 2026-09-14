<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailLogOpen;
use Tests\TestCase;

/**
 * Pixel de apertura — pantalla "Emails enviados" de HelpdeskTickets.
 */
class EmailOpenTrackingTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog vive en la conexión default de la app
    // (mysql en este entorno, no mariadb/helpdesk) — sin declararla, cada
    // EmailLog::create() de este archivo escribía una fila REAL sin rollback
    // (mismo gotcha ya documentado y corregido en EmailLogControllerTest;
    // confirmado en vivo: decenas de filas "Test"/cliente@example.com
    // huérfanas en el listado real de /panel/helpdeskemailactivity).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    public function test_pixel_route_records_an_open_and_returns_a_gif(): void
    {
        $log = EmailLog::create([
            'from_address' => 'soporte@alvarez.mx',
            'to_addresses' => ['cliente@example.com'],
            'subject' => 'Test',
            'message_id' => 'pixel-test@alvarez.mx',
            'status' => 'sent',
        ]);

        $response = $this->get(route('helpdeskemailactivity.pixel', $log));

        $response->assertOk();
        $this->assertSame('image/gif', $response->headers->get('Content-Type'));

        $this->assertDatabaseHas('email_log_opens', [
            'email_log_id' => $log->id,
        ]);
    }

    public function test_pixel_route_requires_no_authentication(): void
    {
        // A propósito: lo abre el cliente de correo del destinatario, nunca
        // un usuario logueado del panel.
        $log = EmailLog::create([
            'from_address' => 'soporte@alvarez.mx',
            'to_addresses' => ['cliente@example.com'],
            'subject' => 'Test',
            'message_id' => 'pixel-anon@alvarez.mx',
            'status' => 'sent',
        ]);

        $this->assertGuest();

        $this->get(route('helpdeskemailactivity.pixel', $log))->assertOk();
    }

    public function test_multiple_hits_record_multiple_opens(): void
    {
        $log = EmailLog::create([
            'from_address' => 'soporte@alvarez.mx',
            'to_addresses' => ['cliente@example.com'],
            'subject' => 'Test',
            'message_id' => 'pixel-multi@alvarez.mx',
            'status' => 'sent',
        ]);

        $this->get(route('helpdeskemailactivity.pixel', $log));
        $this->get(route('helpdeskemailactivity.pixel', $log));

        $this->assertSame(2, EmailLogOpen::where('email_log_id', $log->id)->count());
    }

    // La inyección real del pixel en LogEmailQueued (scopeada a module ===
    // 'HelpdeskTickets') se verificó manualmente end-to-end: envío real vía
    // Mailpit, `<img src=".../e/{uid}.gif">` presente en el HTML capturado,
    // hit real a la ruta registrando la apertura en email_log_opens. No hay
    // un test automatizado aquí porque el transporte real de correo de este
    // contenedor está roto de forma preexistente (setting `mail_mailer` en
    // BD apunta a sendmail, binario ausente) — afecta también a
    // EmailTrackingTest::test_internal_tracking_headers_do_not_leak_to_the_recipient,
    // un test hermano que ya usaba Mail::to()->send() antes de este cambio.
}
