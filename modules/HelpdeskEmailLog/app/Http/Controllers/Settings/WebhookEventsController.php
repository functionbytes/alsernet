<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\HelpdeskEmailLog\Models\ProviderWebhookEvent;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookEventProcessor;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookSettingsRepository;
use Modules\HelpdeskEmailLog\Support\ParsedEmailEvent;

/**
 * Auditoría/depuración de los webhooks de proveedor ya recibidos (ver
 * EmailProviderWebhookController) — panel de salud por proveedor (último
 * evento, volumen del día, duplicados descartados, si el secreto está
 * configurado) y listado de eventos con acceso a su payload y a la opción
 * de reprocesar los que no llegaron a correlacionar con ningún email.
 */
class WebhookEventsController extends Controller
{
    /**
     * Los 4 adapters que soporta el conector (ver
     * EmailProviderWebhookController::adapters()) — se listan siempre los 4
     * en el panel de salud, aunque solo uno esté activo, porque puede haber
     * histórico de un proveedor que se usó antes de cambiar al actual.
     *
     * @var array<string, string>
     */
    private const PROVIDER_LABELS = [
        'mailrelay' => 'Mailrelay',
        'ses' => 'Amazon SES (SNS)',
        'postmark' => 'Postmark',
        'mailgun' => 'Mailgun',
    ];

    public function __construct(
        private readonly ProviderWebhookSettingsRepository $settingsRepo,
        private readonly ProviderWebhookEventProcessor $processor,
    ) {
        $this->middleware('can:helpdeskemaillog.settings.view')->only('index');
        $this->middleware('can:helpdeskemaillog.settings.update')->only('reprocess');
    }

    public function index(Request $request): View
    {
        $provider = $request->string('provider')->toString();
        $provider = in_array($provider, array_keys(self::PROVIDER_LABELS), true) ? $provider : null;

        $events = ProviderWebhookEvent::query()
            ->with('emailLog:id,uid,subject')
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('helpdeskemaillog::settings.webhook-events', [
            'events' => $events,
            'health' => $this->buildHealth(),
            'filterProvider' => $provider,
            'providerLabels' => self::PROVIDER_LABELS,
        ]);
    }

    /**
     * Reprocesa un evento que no llegó a correlacionar con ningún email
     * (email_log_id null) — reconstruye el ParsedEmailEvent a partir de
     * payload['parsed'] (ver EmailProviderWebhookController::buildStoredPayload())
     * y lo vuelve a pasar por el mismo despacho que un webhook en caliente
     * (ProviderWebhookEventProcessor), sin volver a pedirle nada al
     * proveedor. Es la razón de ser de guardar el payload: si el motivo era,
     * p.ej., que el email llegó tarde y no existía todavía, o que el bug ya
     * se corrigió, reprocesar ahora sí correlaciona.
     *
     * Solo eventos SIN correlacionar son reprocesables — uno ya
     * correlacionado podría duplicar efectos no idempotentes (una apertura
     * de proveedor inserta una fila nueva cada vez, ver
     * EmailDeliveryEventCorrelatorService::recordOpen()).
     */
    public function reprocess(ProviderWebhookEvent $event): RedirectResponse
    {
        if ($event->email_log_id !== null) {
            return back()->with('error', __('helpdeskemaillog::emaillog.webhook_events.already_correlated'));
        }

        $parsedEvent = $this->rebuildParsedEvent($event);

        if ($parsedEvent === null) {
            return back()->with('error', __('helpdeskemaillog::emaillog.webhook_events.cannot_reprocess'));
        }

        $config = $this->settingsRepo->get();

        if (! $this->processor->shouldProcess($parsedEvent, $config)) {
            return back()->with('warning', __('helpdeskemaillog::emaillog.webhook_events.type_disabled'));
        }

        $matchedLog = $this->processor->correlate($parsedEvent);

        $event->update(['email_log_id' => $matchedLog?->id, 'processed_at' => now()]);

        return back()->with(
            $matchedLog !== null ? 'success' : 'warning',
            __($matchedLog !== null
                ? 'helpdeskemaillog::emaillog.webhook_events.reprocessed_matched'
                : 'helpdeskemaillog::emaillog.webhook_events.reprocessed_unmatched')
        );
    }

    /**
     * Reconstruye el ParsedEmailEvent que se guardó en su momento — null si
     * la fila es anterior a esta migración (payload/event_type todavía no
     * existían) y por tanto no hay suficiente información para reprocesar.
     */
    private function rebuildParsedEvent(ProviderWebhookEvent $event): ?ParsedEmailEvent
    {
        $parsed = $event->payload['parsed'] ?? null;

        if ($event->event_type === null || ! is_array($parsed)) {
            return null;
        }

        return new ParsedEmailEvent(
            type: $event->event_type,
            messageId: $parsed['message_id'] ?? null,
            recipient: $parsed['recipient'] ?? null,
            isHard: (bool) ($parsed['is_hard'] ?? false),
            reason: (string) ($parsed['reason'] ?? ''),
            providerEventId: $event->provider_event_id,
            ip: $parsed['ip'] ?? null,
            userAgent: $parsed['user_agent'] ?? null,
        );
    }

    /**
     * @return list<array{key: string, label: string, is_active: bool, has_secret: bool, last_event_at: ?Carbon, today_count: int, duplicates_today: int}>
     */
    private function buildHealth(): array
    {
        $config = $this->settingsRepo->get();
        $today = now()->startOfDay();

        return Collection::make(self::PROVIDER_LABELS)
            ->map(function (string $label, string $key) use ($config, $today): array {
                $isActive = $config['provider'] === $key;
                $lastEventAt = ProviderWebhookEvent::query()->where('provider', $key)->max('created_at');

                return [
                    'key' => $key,
                    'label' => $label,
                    'is_active' => $isActive,
                    'has_secret' => $isActive && $config['secret'] !== '',
                    'last_event_at' => $lastEventAt ? Carbon::parse($lastEventAt) : null,
                    'today_count' => ProviderWebhookEvent::query()->where('provider', $key)->where('created_at', '>=', $today)->count(),
                    'duplicates_today' => (int) ProviderWebhookEvent::query()->where('provider', $key)->where('created_at', '>=', $today)->sum('duplicate_count'),
                ];
            })
            ->values()
            ->all();
    }
}
