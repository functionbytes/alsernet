<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailLogClick;
use Modules\HelpdeskEmailLog\Models\EmailLogLink;
use Tests\TestCase;

/**
 * Redirección de clic — mismo patrón que EmailOpenTrackingTest para el píxel
 * de apertura, pero registrando en email_log_clicks y devolviendo un 302 al
 * destino real en vez de un gif.
 */
class EmailClickTrackingTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog (y por FK, EmailLogLink/EmailLogClick)
    // vive en la conexión default de la app (mysql en este entorno, no
    // mariadb/helpdesk) — sin declararla, cada fila creada aquí escribe REAL
    // sin rollback (mismo gotcha documentado en EmailLogControllerTest, que
    // llegó a dejar 45 filas fixture huérfanas antes de corregirse).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private function makeLog(): EmailLog
    {
        return EmailLog::create([
            'from_address' => 'soporte@alvarez.mx',
            'to_addresses' => ['cliente@example.com'],
            'subject' => 'Test',
            'message_id' => 'click-test-'.Str::random(12).'@alvarez.mx',
            'status' => 'sent',
        ]);
    }

    private function makeLink(?EmailLog $log = null, string $url = 'https://example.com/ticket/42'): EmailLogLink
    {
        return EmailLogLink::create([
            'email_log_id' => ($log ?? $this->makeLog())->id,
            'token' => Str::random(40),
            'url' => $url,
            'created_at' => now(),
        ]);
    }

    public function test_click_route_records_a_click_and_redirects_to_the_real_url(): void
    {
        $link = $this->makeLink();

        $response = $this->get(route('helpdeskemaillog.click', ['emailLog' => $link->emailLog, 'token' => $link->token]));

        $response->assertRedirect($link->url);

        $this->assertDatabaseHas('email_log_clicks', [
            'email_log_link_id' => $link->id,
        ]);
    }

    public function test_click_route_requires_no_authentication(): void
    {
        // A propósito: lo sigue el navegador/cliente de correo del
        // destinatario, nunca un usuario logueado del panel.
        $link = $this->makeLink();

        $this->assertGuest();

        $this->get(route('helpdeskemaillog.click', ['emailLog' => $link->emailLog, 'token' => $link->token]))
            ->assertRedirect($link->url);
    }

    public function test_multiple_hits_record_multiple_clicks(): void
    {
        $link = $this->makeLink();

        $this->get(route('helpdeskemaillog.click', ['emailLog' => $link->emailLog, 'token' => $link->token]));
        $this->get(route('helpdeskemaillog.click', ['emailLog' => $link->emailLog, 'token' => $link->token]));

        $this->assertSame(2, EmailLogClick::where('email_log_link_id', $link->id)->count());
    }

    public function test_unknown_token_returns_404(): void
    {
        $log = $this->makeLog();

        $this->get(route('helpdeskemaillog.click', ['emailLog' => $log, 'token' => 'doesnotexist12345']))
            ->assertNotFound();
    }

    public function test_a_link_belonging_to_a_different_email_log_is_rejected(): void
    {
        // El token es único globalmente, pero igual se exige que pertenezca
        // al {emailLog} de la URL — defensa en profundidad, ver
        // EmailClickTrackingController::redirect().
        $link = $this->makeLink();
        $otherLog = $this->makeLog();

        $this->get(route('helpdeskemaillog.click', ['emailLog' => $otherLog, 'token' => $link->token]))
            ->assertNotFound();
    }

    // La reescritura real de <a href> en LogEmailQueued (scopeada a module ===
    // 'HelpdeskTickets', igual que el píxel de apertura) se verificó
    // manualmente end-to-end por el mismo motivo documentado en
    // EmailOpenTrackingTest: el transporte real de correo de este contenedor
    // está roto de forma preexistente (setting mail_mailer en BD apunta a
    // sendmail, binario ausente).
}
