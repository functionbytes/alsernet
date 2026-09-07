<?php

namespace Modules\HelpdeskBirthday\Http\Controllers\Managers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayCampaignService;
use Modules\HelpdeskBirthday\Services\BirthdayCouponService;
use Modules\HelpdeskBirthday\Services\BirthdayDashboardService;
use Modules\HelpdeskBirthday\Services\BirthdayRedemptionService;
use Modules\HelpdeskBirthday\Support\BirthdayMailRenderer;
use Modules\HelpdeskEmailActivity\Enums\SuppressionReason;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\EmailDeliveryLookupService;
use Modules\HelpdeskEmailActivity\Services\EmailSuppressionService;

class BirthdayCampaignsController extends Controller
{
    public function __construct(
        private readonly BirthdayCampaignService $campaigns,
    ) {}

    public function index(Request $request, BirthdayDashboardService $dashboard): View
    {
        $campaigns = BirthdayCampaign::query()
            ->orderByDesc('campaign_date')
            ->paginate(25);

        $days = (int) $request->integer('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $overview = $dashboard->overview($days);

        return view('helpdeskbirthday::campaigns.index', [
            'campaigns' => $campaigns,
            'overview' => $overview,
            'funnel' => $dashboard->funnel($overview['campaigns'], $overview['delivery'], $overview['redemption']),
            'skipReasons' => $dashboard->skipReasons($days),
            'upcoming' => $dashboard->upcoming(),
            'days' => $days,
        ]);
    }

    public function show(
        Request $request,
        BirthdayCampaign $campaign,
        EmailDeliveryLookupService $lookup,
        BirthdayRedemptionService $redemptions,
    ): View {
        $recipients = $campaign->recipients()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('email', 'like', '%'.$request->string('search').'%'))
            // Los omitidos no tienen hora: al final, no encabezando la tabla.
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')
            ->paginate(50)
            ->withQueryString();

        // Estado real de entrega (entregado / abierto / clic / rebotado) de
        // los destinatarios de ESTA página, en una sola consulta al log de
        // correo. Es la misma API que puede usar cualquier otro módulo.
        $delivery = $lookup->forRecipients(
            $recipients->pluck('email')->all(),
            BirthdayDashboardService::MODULE,
        );

        // Los bonos de la campaña, en una sola consulta agregada: cuántos se
        // emitieron, cuántos faltan y qué concedió Gestión. Pedir cada cifra por
        // separado eran cinco consultas para pintar una tarjeta.
        $coupons = $campaign->recipients()
            ->selectRaw('COUNT(coupon_code) as issued')
            ->selectRaw('SUM(CASE WHEN coupon_code IS NULL AND status IN (?, ?) THEN 1 ELSE 0 END) as `without`', [
                BirthdayRecipient::STATUS_PENDING,
                BirthdayRecipient::STATUS_SKIPPED,
            ])
            ->selectRaw('MAX(coupon_amount) as amount')
            ->selectRaw('MAX(coupon_min_purchase) as min_purchase')
            ->selectRaw('MAX(coupon_valid_to) as valid_to')
            ->first();

        return view('helpdeskbirthday::campaigns.show', [
            'campaign' => $campaign,
            'recipients' => $recipients,
            'delivery' => $delivery,
            // Cuántos se quedaron sin bono: es lo que hay que resolver para que
            // la campaña pueda enviar, así que se cuenta aparte de los fallidos.
            'withoutCoupon' => $campaign->coupon_code ? 0 : (int) $coupons->without,
            // Resumen de los bonos emitidos: con uno por cliente, la tarjeta del
            // cupón único ya no dice nada.
            'coupons' => [
                'issued' => (int) $coupons->issued,
                'amount' => $coupons->amount,
                'valid_to' => $coupons->valid_to,
                'min_purchase' => $coupons->min_purchase,
            ],
            // Quién usó el cupón de verdad (PrestaShop). Vacío si esa BD no
            // está configurada: la columna se oculta en ese caso.
            'redeemers' => $redemptions->redeemersFor($campaign),
            'redemption' => $redemptions->forCampaign($campaign),
            'statuses' => [
                BirthdayRecipient::STATUS_PENDING,
                BirthdayRecipient::STATUS_SENDING,
                BirthdayRecipient::STATUS_SENT,
                BirthdayRecipient::STATUS_FAILED,
                BirthdayRecipient::STATUS_SKIPPED,
            ],
        ]);
    }

    /**
     * Previsualiza el correo tal y como lo recibiría el primer destinatario.
     */
    public function preview(BirthdayCampaign $campaign): Response
    {
        $recipient = $campaign->recipients()->orderBy('id')->first()
            ?? new BirthdayRecipient([
                'email' => 'ejemplo@cliente.test',
                'name' => __('helpdeskbirthday::messages.default_customer_name'),
            ]);

        // Sin id no se puede firmar la URL de baja, y el renderer la necesita.
        $recipient->id ??= 0;

        [$subject, $html] = BirthdayMailRenderer::render($campaign, $recipient);

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('X-Preview-Subject', $subject);
    }

    /**
     * El correo tal y como lo recibió ESTE destinatario.
     *
     * Prioriza el HTML guardado en el log de correo (que es literalmente lo
     * que salió, con su cupón y su enlace de baja) y solo re-renderiza la
     * plantilla si el log no lo conserva — el body se purga a los N días, y
     * para los pendientes todavía no existe.
     */
    public function recipientEmail(BirthdayCampaign $campaign, BirthdayRecipient $recipient): Response
    {
        abort_unless($recipient->campaign_id === $campaign->id, 404);

        $log = $recipient->email_log_id
            ? EmailLog::query()->find($recipient->email_log_id)
            : null;

        if ($log?->body_html) {
            return response($log->body_html)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        [, $html] = BirthdayMailRenderer::render($campaign, $recipient);

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Da de baja a un destinatario de futuras felicitaciones. La supresión se
     * acota al módulo: no corta los correos de sus tickets.
     */
    public function unsubscribeRecipient(
        BirthdayCampaign $campaign,
        BirthdayRecipient $recipient,
        EmailSuppressionService $suppressions,
    ): RedirectResponse {
        abort_unless($recipient->campaign_id === $campaign->id, 404);

        $suppressions->suppress(
            email: $recipient->email,
            reason: SuppressionReason::Unsubscribed,
            module: BirthdayDashboardService::MODULE,
            notes: 'Baja manual desde el panel de campañas de cumpleaños.',
        );

        // Si todavía no le había salido el correo, ya no sale.
        if ($recipient->status === BirthdayRecipient::STATUS_PENDING) {
            $recipient->update([
                'status' => BirthdayRecipient::STATUS_SKIPPED,
                'skip_reason' => BirthdayRecipient::SKIP_SUPPRESSED,
            ]);
            $campaign->increment('skipped_count');
        }

        return back()->with('success', __('helpdeskbirthday::messages.recipient_unsubscribed', ['email' => $recipient->email]));
    }

    /**
     * Devuelve un envío fallido a la cola. No reencola los que ya salieron:
     * eso sería enviar la felicitación dos veces.
     */
    public function retryRecipient(BirthdayCampaign $campaign, BirthdayRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->campaign_id === $campaign->id, 404);

        if ($recipient->status !== BirthdayRecipient::STATUS_FAILED) {
            return back()->with('error', __('helpdeskbirthday::messages.retry_not_allowed'));
        }

        $recipient->update([
            'status' => BirthdayRecipient::STATUS_PENDING,
            'scheduled_at' => now(),
            'error_message' => null,
        ]);

        if ($campaign->failed_count > 0) {
            $campaign->decrement('failed_count');
        }

        return back()->with('success', __('helpdeskbirthday::messages.recipient_requeued'));
    }

    /**
     * Validación de los canjes: qué pedido salió de cada cupón, en qué estado
     * está y qué contestó gestión al marcar el bono.
     */
    public function redemptions(BirthdayCampaign $campaign, BirthdayRedemptionService $redemptions): View
    {
        return view('helpdeskbirthday::campaigns.redemptions', [
            'campaign' => $campaign,
            'available' => $redemptions->isAvailable(),
            'summary' => $redemptions->forCampaign($campaign),
            'rows' => $redemptions->detailFor($campaign),
            // Cuántos bonos se emitieron: con uno por cliente, es el número que
            // da sentido a los canjes, y sustituye al código único de campaña.
            'couponCount' => $campaign->recipients()->withCoupon()->count(),
            // Marcar un bono escribe en el ERP: la acción solo se pinta a quien
            // puede gestionar campañas.
            'canManage' => request()->user()?->can('helpdeskbirthday.manage') ?? false,
        ]);
    }

    /**
     * Reintenta marcar en gestión un bono que el cliente ya gastó en la tienda.
     *
     * ESCRIBE EN EL ERP: hasta que el bono se marque, se puede volver a usar.
     * El importe que se envía es el del pedido, igual que hace PrestaShop.
     */
    public function markCouponUsed(
        Request $request,
        BirthdayCampaign $campaign,
        BirthdayCouponService $coupons,
        BirthdayRedemptionService $redemptions,
    ): RedirectResponse {
        $data = $request->validate([
            'sale_amount' => ['required', 'numeric', 'min:0'],
            // Con un bono por cliente hay que decir CUÁL se marca: el de la
            // campaña solo existe en las promociones de código único, y usarlo
            // aquí marcaba el bono equivocado o ninguno.
            'coupon_code' => ['nullable', 'string', 'max:60'],
        ]);

        $code = trim((string) ($data['coupon_code'] ?? '')) ?: (string) $campaign->coupon_code;

        if ($code === '') {
            return back()->with('error', __('helpdeskbirthday::messages.coupon_missing'));
        }

        // El código tiene que ser uno de esta campaña: sin esta comprobación,
        // un POST a mano podría consumir en Gestión el bono de cualquiera.
        if (! in_array($code, $redemptions->codesFor($campaign), true)) {
            return back()->with('error', __('helpdeskbirthday::messages.coupon_not_in_campaign'));
        }

        $result = $coupons->markAsUsed($code, (float) $data['sale_amount']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Devuelve a la cola todos los envíos fallidos de la campaña de una vez.
     * Uno a uno desde el menú de cada fila es inviable con cien fallos.
     */
    public function retryFailed(BirthdayCampaign $campaign): RedirectResponse
    {
        $requeued = $campaign->recipients()
            ->where('status', BirthdayRecipient::STATUS_FAILED)
            ->update([
                'status' => BirthdayRecipient::STATUS_PENDING,
                'scheduled_at' => now(),
                'error_message' => null,
                'updated_at' => now(),
            ]);

        if ($requeued === 0) {
            return back()->with('error', __('helpdeskbirthday::messages.no_failed_to_retry'));
        }

        // El contador vuelve a cero: esos envíos ya no cuentan como fallidos.
        $campaign->update([
            'failed_count' => max(0, $campaign->failed_count - $requeued),
            // Una campaña cerrada con fallos vuelve a estar activa para que
            // dispatch-due los recoja; si no, se quedarían en pending para siempre.
            'status' => $campaign->isActive() ? $campaign->status : BirthdayCampaign::STATUS_SCHEDULED,
            'finished_at' => null,
        ]);

        return back()->with('success', __('helpdeskbirthday::messages.failed_requeued', ['count' => $requeued]));
    }

    /**
     * Vuelve a pedir a Gestión los bonos que no llegó a emitir.
     *
     * Es lo que desatasca una campaña que se quedó sin poder enviar: quien
     * recupera su bono vuelve a la cola con hora de ahora. A quien ya lo tiene
     * no se le toca — regenerarlo le cambiaría un código que quizá ya está en
     * su buzón.
     */
    public function retryBonos(BirthdayCampaign $campaign): RedirectResponse
    {
        $result = $this->campaigns->retryBonos($campaign);

        if ($result['generated'] === 0 && $result['failed'] === 0) {
            return back()->with('error', __('helpdeskbirthday::messages.no_bonos_to_retry'));
        }

        return back()->with(
            $result['generated'] > 0 ? 'success' : 'error',
            __('helpdeskbirthday::messages.bonos_retried', [
                'generated' => $result['generated'],
                'failed' => $result['failed'],
            ])
        );
    }

    public function prepare(Request $request): RedirectResponse
    {
        $date = $request->filled('date')
            ? CarbonImmutable::parse($request->string('date')->toString())->startOfDay()
            : CarbonImmutable::today();

        $campaign = $this->campaigns->prepare($date);

        if ($campaign->status === BirthdayCampaign::STATUS_FAILED) {
            return back()->with('error', $campaign->error_message);
        }

        return redirect()
            ->route('helpdeskbirthday.campaigns.show', $campaign)
            ->with('success', __('helpdeskbirthday::messages.campaign_prepared'));
    }

    public function pause(BirthdayCampaign $campaign): RedirectResponse
    {
        return $this->transition(
            $this->campaigns->pause($campaign),
            $campaign,
            'campaign_paused'
        );
    }

    public function resume(BirthdayCampaign $campaign): RedirectResponse
    {
        return $this->transition(
            $this->campaigns->resume($campaign),
            $campaign,
            'campaign_resumed'
        );
    }

    public function cancel(BirthdayCampaign $campaign): RedirectResponse
    {
        return $this->transition(
            $this->campaigns->cancel($campaign),
            $campaign,
            'campaign_cancelled'
        );
    }

    private function transition(bool $done, BirthdayCampaign $campaign, string $messageKey): RedirectResponse
    {
        $redirect = redirect()->route('helpdeskbirthday.campaigns.show', $campaign);

        return $done
            ? $redirect->with('success', __("helpdeskbirthday::messages.{$messageKey}"))
            : $redirect->with('error', __('helpdeskbirthday::messages.transition_not_allowed'));
    }
}
