<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Http\Requests\Settings\UpdateEmailLogSettingsRequest;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookSettingsRepository;

class EmailLogSettingsController extends Controller
{
    private const PREFIX = 'helpdeskemaillog.';

    public function __construct(private readonly ProviderWebhookSettingsRepository $providerWebhookSettings)
    {
        $this->middleware('can:helpdeskemaillog.settings.view')->only('index');
        $this->middleware('can:helpdeskemaillog.settings.update')->only('update');
    }

    public function index(): View
    {

        $s = fn (string $key, mixed $default = null) => Setting::get(self::PREFIX.$key, $default ?? config('helpdeskemaillog.'.$key));
        $providerConfig = $this->providerWebhookSettings->get();

        return view('helpdeskemaillog::settings.index', [
            'storeBody' => (bool) $s('store_body', true),
            'maxBodyKb' => (int) round((int) $s('max_body_bytes', 524288) / 1024),
            'retentionDays' => (int) $s('retention_days', 90),
            'staleQueuedHours' => (int) $s('stale_queued_hours', 24),
            'perPage' => (int) $s('per_page', 25),
            'perPageOptions' => config('helpdeskemaillog.per_page_options', [10, 25, 50, 100]),
            'reputationDomainsText' => $this->domainsToText(),
            'reputationWindowDays' => (int) $s('reputation_window_days', 30),
            'bounceRateWarning' => (float) $s('bounce_rate_warning_pct', 2.0),
            'bounceRateCritical' => (float) $s('bounce_rate_critical_pct', 5.0),
            'complaintRateWarning' => (float) $s('complaint_rate_warning_pct', 0.05),
            'complaintRateCritical' => (float) $s('complaint_rate_critical_pct', 0.1),
            'providerWebhookProvider' => $providerConfig['provider'],
            'providerWebhookHasSecret' => $providerConfig['secret'] !== '',
            'providerWebhookProcessBounces' => (bool) $providerConfig['process_bounces'],
            'providerWebhookProcessComplaints' => (bool) $providerConfig['process_complaints'],
            'providerWebhookProcessDeliveries' => (bool) $providerConfig['process_deliveries'],
            'providerWebhookProcessOpens' => (bool) $providerConfig['process_opens'],
            'providerWebhookUrl' => $providerConfig['provider']
                ? route('helpdeskemaillog.webhooks.receive', ['provider' => $providerConfig['provider']])
                : null,
        ]);
    }

    public function update(UpdateEmailLogSettingsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $validated = $request->validated();

            Setting::set(self::PREFIX.'store_body', $validated['store_body']);
            Setting::set(self::PREFIX.'max_body_bytes', (int) $validated['max_body_bytes'] * 1024);
            Setting::set(self::PREFIX.'retention_days', $validated['retention_days']);
            Setting::set(self::PREFIX.'stale_queued_hours', $validated['stale_queued_hours']);
            Setting::set(self::PREFIX.'per_page', $validated['per_page']);
            Setting::set(self::PREFIX.'reputation_domains', $this->textToDomains($validated['reputation_domains'] ?? ''));
            Setting::set(self::PREFIX.'reputation_window_days', $validated['reputation_window_days']);
            Setting::set(self::PREFIX.'bounce_rate_warning_pct', $validated['bounce_rate_warning_pct']);
            Setting::set(self::PREFIX.'bounce_rate_critical_pct', $validated['bounce_rate_critical_pct']);
            Setting::set(self::PREFIX.'complaint_rate_warning_pct', $validated['complaint_rate_warning_pct']);
            Setting::set(self::PREFIX.'complaint_rate_critical_pct', $validated['complaint_rate_critical_pct']);

            $this->providerWebhookSettings->save([
                'provider' => $validated['provider_webhook_provider'] ?? null,
                'secret' => $validated['provider_webhook_secret'] ?? '',
                'process_bounces' => $validated['provider_webhook_process_bounces'] === '1',
                'process_complaints' => $validated['provider_webhook_process_complaints'] === '1',
                'process_deliveries' => $validated['provider_webhook_process_deliveries'] === '1',
                'process_opens' => $validated['provider_webhook_process_opens'] === '1',
            ]);
        });

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->back()->with('success', 'Configuración del log de emails actualizada.');
    }

    /**
     * "dominio.com" o "dominio.com:selector1,selector2" por línea — evita
     * una pantalla CRUD aparte para algo que no maneja credenciales (a
     * diferencia de los buzones de rebote, aquí no hay nada que cifrar).
     */
    private function domainsToText(): string
    {
        $domains = Setting::get(self::PREFIX.'reputation_domains', '[]');
        $data = is_string($domains) ? json_decode($domains, true) : $domains;

        if (! is_array($data)) {
            return '';
        }

        return collect($data)
            ->map(function (array $d) {
                $selectors = implode(',', $d['dkim_selectors'] ?? []);

                return $selectors !== '' ? "{$d['domain']}:{$selectors}" : $d['domain'];
            })
            ->implode("\n");
    }

    private function textToDomains(string $text): string
    {
        $domains = collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                [$domain, $selectors] = array_pad(explode(':', $line, 2), 2, '');

                return [
                    'domain' => mb_strtolower(trim($domain)),
                    'dkim_selectors' => collect(explode(',', $selectors))->map(fn ($s) => trim($s))->filter()->values()->all(),
                ];
            })
            ->filter(fn (array $d) => $d['domain'] !== '')
            ->values()
            ->all();

        return json_encode($domains);
    }
}
