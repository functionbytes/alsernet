<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskBirthday\Mail\BirthdayCouponMailable;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Support\BirthdayMailRenderer;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;
use Throwable;

/**
 * Envío de prueba: manda la felicitación a direcciones internas para ver cómo
 * queda de verdad en un cliente de correo antes de que salga a clientes.
 *
 * Nunca toca la campaña ni sus destinatarios — el destinatario de prueba es un
 * modelo en memoria, sin guardar. Así probar no altera contadores ni deja
 * filas raras en el histórico.
 */
class BirthdayTestSendService
{
    public function __construct(
        private readonly BirthdaySettings $settings,
    ) {}

    /**
     * @param  array<int, string>  $emails
     * @return array{sent: array<int, string>, failed: array<string, string>}
     */
    public function send(array $emails, ?BirthdayCampaign $campaign = null): array
    {
        $campaign ??= $this->draftCampaign();

        $sent = [];
        $failed = [];

        foreach ($emails as $email) {
            $email = mb_strtolower(trim($email));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed[$email] = 'Dirección inválida.';

                continue;
            }

            try {
                $recipient = $this->fakeRecipient($campaign, $email);
                [$subject, $html] = BirthdayMailRenderer::render($campaign, $recipient);

                Mail::to($email)->send(
                    new BirthdayCouponMailable($recipient, '[PRUEBA] '.$subject, $html)
                );

                $sent[] = $email;
            } catch (Throwable $e) {
                $failed[$email] = $e->getMessage();
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Campaña de hoy si existe; si no, una en memoria. Permite probar la
     * plantilla antes de que el scheduler prepare nada.
     */
    private function draftCampaign(): BirthdayCampaign
    {
        $today = BirthdayCampaign::query()
            ->whereDate('campaign_date', CarbonImmutable::today()->toDateString())
            ->first();

        if ($today) {
            return $today;
        }

        $campaign = new BirthdayCampaign;
        $campaign->campaign_date = CarbonImmutable::today();
        $campaign->template_key = (string) $this->settings->all()['template_key'];

        return $campaign;
    }

    /**
     * Destinatario en memoria, sin persistir. El id 0 basta para firmar la URL
     * de baja del correo de prueba.
     */
    private function fakeRecipient(BirthdayCampaign $campaign, string $email): BirthdayRecipient
    {
        // El bono va en el destinatario, que es donde vive de verdad: cada
        // cliente recibe el suyo. Aquí es de muestra —la gracia de la prueba es
        // ver la maqueta con un código dentro, no validar nada en Gestión.
        $recipient = new BirthdayRecipient([
            'campaign_id' => $campaign->id,
            'email' => $email,
            'name' => 'Prueba',
            'lang' => app(BirthdayLanguageResolver::class)->fallbackIso(),
            'birth_date' => CarbonImmutable::today()->subYears(30)->toDateString(),
            'status' => BirthdayRecipient::STATUS_PENDING,
            'coupon_code' => '900000000',
            'coupon_verification_code' => 'PRUEBA',
            'coupon_amount' => 5,
            'coupon_min_purchase' => 30,
            'coupon_valid_from' => CarbonImmutable::today()->toDateString(),
            'coupon_valid_to' => CarbonImmutable::today()->addMonth()->toDateString(),
        ]);

        $recipient->id = 0;

        return $recipient;
    }
}
