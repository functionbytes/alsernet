<?php

namespace Modules\HelpdeskBirthday\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskBirthday\Mail\BirthdayCouponMailable;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Support\BirthdayMailRenderer;
use Modules\Queue\Jobs\BaseJob;
use Throwable;

/**
 * Envía la felicitación a un destinatario.
 *
 * El escalonado de verdad lo hace el reparto de scheduled_at; el throttle de
 * aquí es el segundo cinturón, por si algo reencola muchos jobs de golpe (un
 * reintento masivo, una campaña reanudada tras horas de pausa).
 *
 * Se usa el middleware de Spatie y no Illuminate\Queue\Middleware\RateLimited a
 * propósito: el de Illuminate necesita un limiter registrado con
 * RateLimiter::for() y, si no existe, deja pasar los jobs sin limitar nada —
 * que es justo lo que le pasa hoy al 'helpdesk-meta-outbound' de Helpdesk.
 */
class SendBirthdayEmailJob extends BaseJob
{
    public $tries = 3;

    public $maxExceptions = 3;

    /** Sin tipo: BaseJob la declara sin él y PHP no deja añadirlo al heredar. */
    public $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $recipientId,
    ) {
        $this->onQueue((string) config('helpdeskbirthday.queue', 'birthdays'));
    }

    public function middleware(): array
    {
        return [
            $this->throttle(
                'birthday-emails',
                maxJobs: (int) config('helpdeskbirthday.throttle.max_jobs', 30),
                perSeconds: (int) config('helpdeskbirthday.throttle.per_seconds', 60),
            ),
        ];
    }

    public function handle(): void
    {
        $recipient = BirthdayRecipient::query()->with('campaign')->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        // dispatch-due lo dejó en 'sending'. Cualquier otro estado significa que
        // ya se resolvió (o que la campaña se canceló mientras estaba en cola):
        // salir sin enviar es lo que evita el doble correo.
        if ($recipient->status !== BirthdayRecipient::STATUS_SENDING) {
            return;
        }

        $campaign = $recipient->campaign;

        if (! $campaign instanceof BirthdayCampaign || ! $campaign->isActive()) {
            $recipient->update(['status' => BirthdayRecipient::STATUS_PENDING]);

            return;
        }

        // Un correo de cumpleaños sin cupón es peor que no mandarlo: el cliente
        // recibe una felicitación con un hueco donde debería estar su regalo, y
        // ese correo ya no se puede repetir. Se devuelve a pendiente para que se
        // reintente cuando el bono esté generado.
        if (($recipient->coupon_code ?: $campaign->coupon_code) === null
            || trim((string) ($recipient->coupon_code ?: $campaign->coupon_code)) === '') {
            $recipient->update([
                'status' => BirthdayRecipient::STATUS_PENDING,
                'error_message' => 'Sin cupón asignado: no se envía hasta que Gestión genere el bono.',
            ]);

            return;
        }

        [$subject, $html] = BirthdayMailRenderer::render($campaign, $recipient);

        Mail::to($recipient->email)->send(
            new BirthdayCouponMailable($recipient, $subject, $html)
        );

        $recipient->forceFill([
            'status' => BirthdayRecipient::STATUS_SENT,
            'sent_at' => now(),
            'attempts' => $recipient->attempts + 1,
            'error_message' => null,
        ])->save();

        $campaign->increment('sent_count');
    }

    /**
     * Solo tras agotar los reintentos. Antes de eso el job vuelve a la cola con
     * el destinatario en 'sending', y el siguiente intento lo recoge.
     */
    public function failed(Throwable $e): void
    {
        $recipient = BirthdayRecipient::query()->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        $recipient->forceFill([
            'status' => BirthdayRecipient::STATUS_FAILED,
            'attempts' => $recipient->attempts + 1,
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
        ])->save();

        $recipient->campaign?->increment('failed_count');

        Log::error('[HelpdeskBirthday] Falló el envío de una felicitación', [
            'recipient_id' => $this->recipientId,
            'error' => $e->getMessage(),
        ]);
    }
}
