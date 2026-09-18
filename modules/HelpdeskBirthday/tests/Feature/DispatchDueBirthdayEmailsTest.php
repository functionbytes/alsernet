<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskBirthday\Jobs\SendBirthdayEmailJob;
use Modules\HelpdeskBirthday\Mail\BirthdayCouponMailable;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Tests\TestCase;

/**
 * El escalonado y la pausa: que solo salga lo que ya vencía y que pausar corte
 * el envío de verdad.
 */
class DispatchDueBirthdayEmailsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk', 'mysql', 'mariadb'];

    protected function setUp(): void
    {
        parent::setUp();

        // Estos tests crean la campaña de hoy y campaign_date es UNIQUE: si el
        // entorno ya tiene una (el scheduler la crea cada mañana), el insert
        // reventaría. El borrado va dentro de la transacción del test.
        BirthdayCampaign::whereDate('campaign_date', now()->toDateString())->delete();
    }

    public function test_solo_encola_a_quien_ya_le_tocaba(): void
    {
        Queue::fake();

        $campaign = $this->campaign();
        $due = $this->recipient($campaign, 'ya@ejemplo.test', now()->subMinute());
        $notYet = $this->recipient($campaign, 'luego@ejemplo.test', now()->addHour());

        $this->artisan('helpdeskbirthday:dispatch-due')->assertSuccessful();

        Queue::assertPushed(SendBirthdayEmailJob::class, 1);
        Queue::assertPushed(
            SendBirthdayEmailJob::class,
            fn (SendBirthdayEmailJob $job): bool => $job->recipientId === $due->id
        );

        $this->assertSame(BirthdayRecipient::STATUS_SENDING, $due->fresh()->status);
        $this->assertSame(BirthdayRecipient::STATUS_PENDING, $notYet->fresh()->status);
    }

    public function test_una_campana_pausada_no_envia_nada(): void
    {
        Queue::fake();

        $campaign = $this->campaign(BirthdayCampaign::STATUS_PAUSED);
        $recipient = $this->recipient($campaign, 'ana@ejemplo.test', now()->subHour());

        $this->artisan('helpdeskbirthday:dispatch-due')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertSame(BirthdayRecipient::STATUS_PENDING, $recipient->fresh()->status);
    }

    public function test_dos_pasadas_seguidas_no_encolan_dos_veces_al_mismo(): void
    {
        Queue::fake();

        $campaign = $this->campaign();
        $this->recipient($campaign, 'ana@ejemplo.test', now()->subMinute());

        $this->artisan('helpdeskbirthday:dispatch-due')->assertSuccessful();
        $this->artisan('helpdeskbirthday:dispatch-due')->assertSuccessful();

        Queue::assertPushed(SendBirthdayEmailJob::class, 1);
    }

    public function test_el_primer_envio_pasa_la_campana_a_enviando(): void
    {
        Queue::fake();

        $campaign = $this->campaign();
        $this->recipient($campaign, 'ana@ejemplo.test', now()->subMinute());

        $this->artisan('helpdeskbirthday:dispatch-due')->assertSuccessful();

        $campaign->refresh();
        $this->assertSame(BirthdayCampaign::STATUS_SENDING, $campaign->status);
        $this->assertNotNull($campaign->started_at);
    }

    public function test_el_job_envia_el_correo_y_marca_al_destinatario(): void
    {
        Mail::fake();

        $campaign = $this->campaign(BirthdayCampaign::STATUS_SENDING);
        $recipient = $this->recipient($campaign, 'ana@ejemplo.test', now()->subMinute());
        $recipient->update(['status' => BirthdayRecipient::STATUS_SENDING]);

        (new SendBirthdayEmailJob($recipient->id))->handle();

        Mail::assertSent(BirthdayCouponMailable::class, function (BirthdayCouponMailable $mail): bool {
            // Las cabeceras de email_logs son lo que hace que el envío quede
            // atribuido al módulo en el visor de correos.
            return $mail->getEmailLogModule() === 'HelpdeskBirthday'
                && $mail->hasTo('ana@ejemplo.test');
        });

        $recipient->refresh();
        $this->assertSame(BirthdayRecipient::STATUS_SENT, $recipient->status);
        $this->assertNotNull($recipient->sent_at);
        $this->assertSame(1, $campaign->fresh()->sent_count);
    }

    public function test_el_job_no_reenvia_si_el_destinatario_ya_no_esta_reservado(): void
    {
        Mail::fake();

        $campaign = $this->campaign(BirthdayCampaign::STATUS_SENDING);
        $recipient = $this->recipient($campaign, 'ana@ejemplo.test', now()->subMinute());
        $recipient->update(['status' => BirthdayRecipient::STATUS_SENT]);

        (new SendBirthdayEmailJob($recipient->id))->handle();

        Mail::assertNothingSent();
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function campaign(string $status = BirthdayCampaign::STATUS_SCHEDULED): BirthdayCampaign
    {
        return BirthdayCampaign::create([
            'campaign_date' => now()->toDateString(),
            'status' => $status,
            'coupon_code' => 'CUMPLE10-XYZ',
            'coupon_valid_from' => now()->toDateString(),
            'coupon_valid_to' => now()->addMonth()->toDateString(),
            'coupon_amount' => 10,
            'coupon_source' => BirthdayCampaign::SOURCE_ERP,
            'template_key' => 'birthday-coupon',
            'window_start' => '09:00:00',
            'window_end' => '14:00:00',
            'throttle_per_hour' => 600,
            'interval_seconds' => 60,
            'recipients_total' => 0,
        ]);
    }

    private function recipient(BirthdayCampaign $campaign, string $email, $scheduledAt): BirthdayRecipient
    {
        return BirthdayRecipient::create([
            'campaign_id' => $campaign->id,
            'erp_customer_id' => '1',
            'email' => $email,
            'name' => 'Ana',
            'birth_date' => '1990-01-01',
            'scheduled_at' => $scheduledAt,
            'status' => BirthdayRecipient::STATUS_PENDING,
        ]);
    }
}
