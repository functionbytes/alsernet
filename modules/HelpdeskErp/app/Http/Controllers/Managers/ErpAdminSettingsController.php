<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminSettingsOverrides;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminSettingsService;

/**
 * «Ajustes de Gestión» (Ajustes → Helpdesk · Gestión (ERP)): cachés por
 * estado, resumen y avisos, vinculación automática, plantillas de
 * seguimiento y retención de métricas, sin despliegue. Los valores se
 * aplican sobre config() en el boot (ErpAdminSettingsOverrides::apply), así
 * que el resto del módulo sigue leyendo config('helpdeskErp.*').
 */
class ErpAdminSettingsController extends Controller
{
    public const PERMISSION = 'helpdeskerp.settings.manage';

    public function __construct(
        private readonly ErpAdminSettingsService $settings,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(self::PERMISSION), 403);

        $last = $this->settings->lastUpdate();
        $updater = $last['by'] ? User::query()->find($last['by']) : null;

        return view('helpdeskerp::admin.settings', [
            'ready' => $this->settings->ready(),
            'current' => $this->settings->current(),
            'defaults' => $this->settings->defaults(),
            'sectionOverridden' => $this->settings->overriddenSections(),
            'updatedAt' => $last['at'],
            'updatedBy' => $updater ? (trim((string) $updater->fullName()) ?: $updater->email) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can(self::PERMISSION), 403);

        $rules = [
            'ttl' => ['required', 'array'],
            'overview.orders_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'overview.expiry_days' => ['required', 'integer', 'min:1', 'max:90'],
            'alerts' => ['nullable', 'array'],
            'linking.auto' => ['nullable', 'boolean'],
            'tracking' => ['nullable', 'array', 'max:40'],
            'tracking.*.carrier' => ['nullable', 'string', 'max:60'],
            'tracking.*.url' => ['nullable', 'string', 'max:500'],
            'metrics.enabled' => ['nullable', 'boolean'],
            'metrics.retention_days' => ['required', 'integer', 'min:7', 'max:730'],
        ];
        foreach (ErpAdminSettingsOverrides::TTL_STATES as $state) {
            $rules['ttl.'.$state] = ['required', 'integer', 'min:0', 'max:86400'];
        }
        foreach (array_keys(ErpAdminSettingsOverrides::ALERT_CODES) as $type) {
            $rules['alerts.'.$type] = ['nullable', 'boolean'];
        }

        $messages = [
            'ttl.*.required' => 'Indica los segundos de caché.',
            'ttl.*.integer' => 'Los segundos de caché tienen que ser un número entero.',
            'ttl.*.min' => 'Los segundos de caché no pueden ser negativos.',
            'ttl.*.max' => 'La caché no puede durar más de un día (86.400 s).',
            'overview.orders_limit.*' => 'Los pedidos del resumen van de 1 a 100.',
            'overview.expiry_days.*' => 'Los días de aviso de caducidad van de 1 a 90.',
            'metrics.retention_days.*' => 'La retención de métricas va de 7 a 730 días.',
            'tracking.max' => 'Como mucho 40 transportistas.',
            'tracking.*.carrier.max' => 'El nombre del transportista admite hasta 60 caracteres.',
            'tracking.*.url.max' => 'La plantilla admite hasta 500 caracteres.',
        ];

        $validator = validator($request->all(), $rules, $messages);
        $validator->after(function (Validator $v) use ($request) {
            $seen = [];
            foreach ((array) $request->input('tracking', []) as $i => $row) {
                $carrier = trim((string) ($row['carrier'] ?? ''));
                $url = trim((string) ($row['url'] ?? ''));
                if ($carrier === '' && $url === '') {
                    continue;
                }
                if ($carrier === '') {
                    $v->errors()->add("tracking.$i.carrier", 'Falta el transportista de esta plantilla.');
                }
                if (! ErpAdminSettingsOverrides::validTrackingTemplate($url)) {
                    $v->errors()->add("tracking.$i.url", 'La plantilla tiene que empezar por https:// (o http://) e incluir {tracking}.');
                }
                $k = ErpAdminSettingsOverrides::carrierKey($carrier);
                if ($carrier !== '' && $k === '') {
                    $v->errors()->add("tracking.$i.carrier", 'La clave del transportista tiene que tener letras o números.');
                }
                if ($k !== '' && isset($seen[$k])) {
                    $v->errors()->add("tracking.$i.carrier", 'Este transportista ya tiene plantilla.');
                }
                $seen[$k] = true;
            }
        });
        $validator->validate();

        if (! $this->settings->ready()) {
            return back()->withInput()->with('error', 'Falta crear la tabla de ajustes de Gestión (migración pendiente). No se ha guardado nada.');
        }

        $user = $request->user();
        $diff = $this->settings->save($this->valuesFrom($request), $user?->getAuthIdentifier());

        if ($diff['changed'] === []) {
            return redirect()->route('manager.helpdesk.erp.admin.settings.index')->with('success', 'No había cambios que guardar.');
        }

        $this->log($user, $diff, null);

        return redirect()
            ->route('manager.helpdesk.erp.admin.settings.index')
            ->with('success', 'Ajustes de Gestión guardados. Se aplican ya, sin despliegue.');
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can(self::PERMISSION), 403);

        $data = $request->validate([
            'section' => ['required', 'string', Rule::in(array_keys(ErpAdminSettingsService::SECTIONS))],
        ]);

        if (! $this->settings->ready()) {
            return back()->with('error', 'Falta crear la tabla de ajustes de Gestión (migración pendiente).');
        }

        $user = $request->user();
        $diff = $this->settings->reset($data['section'], $user?->getAuthIdentifier());

        if ($diff['changed'] !== []) {
            $this->log($user, $diff, $data['section']);
        }

        return redirect()
            ->route('manager.helpdesk.erp.admin.settings.index')
            ->with('success', '«'.ErpAdminSettingsService::SECTION_LABELS[$data['section']].'» vuelve a los valores de configuración.');
    }

    /**
     * Formulario → claves lógicas.
     *
     * @return array<string, mixed>
     */
    private function valuesFrom(Request $request): array
    {
        $values = [];

        foreach (ErpAdminSettingsOverrides::TTL_STATES as $state) {
            $values['chat_ttl.'.$state] = (int) $request->input('ttl.'.$state);
        }

        $values['overview.orders_limit'] = (int) $request->input('overview.orders_limit');
        $values['alerts.expiry_days'] = (int) $request->input('overview.expiry_days');

        $alerts = [];
        foreach (array_keys(ErpAdminSettingsOverrides::ALERT_CODES) as $type) {
            $alerts[$type] = $request->boolean('alerts.'.$type);
        }
        $values['alerts.enabled'] = $alerts;

        $values['linking.auto'] = $request->boolean('linking.auto');

        $tracking = [];
        foreach ((array) $request->input('tracking', []) as $row) {
            $carrier = trim((string) ($row['carrier'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            $key = ErpAdminSettingsOverrides::carrierKey($carrier);
            if ($key !== '' && $url !== '') {
                $tracking[$key] = $url;
            }
        }
        $values['tracking.urls'] = $tracking;

        $values['metrics.enabled'] = $request->boolean('metrics.enabled');
        $values['metrics.retention_days'] = (int) $request->input('metrics.retention_days');

        return $values;
    }

    /**
     * @param  array{changed: array<string, array{from: mixed, to: mixed}>}  $diff
     */
    private function log($user, array $diff, ?string $resetSection): void
    {
        if (! function_exists('activity')) {
            return;
        }

        try {
            activity('helpdeskerp-config')
                ->causedBy($user)
                ->withProperties($diff + ['reset' => $resetSection])
                ->log($resetSection !== null ? 'erp.settings.reset' : 'erp.settings.updated');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
