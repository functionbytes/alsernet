<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\SettingsUpdateRequest;
use Modules\HelpdeskPrestashop\Services\Ext\SettingsService;

/**
 * «Ajustes del chat» (Ajustes → Helpdesk · PrestaShop): límites de vales y
 * reembolsos, instrucciones de retorno y respuestas rápidas, editables sin
 * despliegue. Los valores se aplican sobre config() en el boot del provider
 * (SettingsOverrides::apply), así que el resto del módulo sigue leyendo
 * config('helpdeskprestashop.*') sin saber de esta pantalla.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('helpdeskprestashop.settings.manage'), 403);

        $current = $this->settings->current();
        $defaults = $this->settings->defaults();
        $last = $this->settings->lastUpdate();
        $updater = $last['by'] ? User::query()->find($last['by']) : null;

        $overridden = $this->settings->overriddenKeys();
        $sectionOverridden = [];
        foreach (SettingsService::SECTIONS as $section => $keys) {
            $sectionOverridden[$section] = array_values(array_intersect($keys, $overridden)) !== [];
        }

        return view('helpdeskprestashop::ext.settings.index', [
            'ready' => $this->settings->ready(),
            'current' => $current,
            'defaults' => $defaults,
            'sectionOverridden' => $sectionOverridden,
            'variables' => SettingsService::REPLY_VARIABLES,
            'voucherCap' => SettingsUpdateRequest::VOUCHER_HARD_CAP,
            'updatedAt' => $last['at'],
            'updatedBy' => $updater ? ($updater->fullName() ?: $updater->email) : null,
        ]);
    }

    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        if (! $this->settings->ready()) {
            return back()->withInput()->with('error', 'Falta crear la tabla de ajustes del chat (migración pendiente). No se ha guardado nada.');
        }

        $user = $request->user();
        $diff = $this->settings->save($request->settings(), $user?->getAuthIdentifier());

        if ($diff['changed'] === []) {
            return redirect()
                ->route('manager.helpdesk.ps.ext.settings.index')
                ->with('success', 'No había cambios que guardar.');
        }

        $this->log($user, $diff, null);

        return redirect()
            ->route('manager.helpdesk.ps.ext.settings.index')
            ->with('success', 'Ajustes del chat guardados. Se aplican ya, sin despliegue.');
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('helpdeskprestashop.settings.manage'), 403);

        $data = $request->validate([
            'section' => ['required', 'string', Rule::in(array_keys(SettingsService::SECTIONS))],
        ]);

        if (! $this->settings->ready()) {
            return back()->with('error', 'Falta crear la tabla de ajustes del chat (migración pendiente).');
        }

        $user = $request->user();
        $diff = $this->settings->reset($data['section'], $user?->getAuthIdentifier());

        if ($diff['changed'] !== []) {
            $this->log($user, $diff, $data['section']);
        }

        return redirect()
            ->route('manager.helpdesk.ps.ext.settings.index')
            ->with('success', '«'.SettingsService::SECTION_LABELS[$data['section']].'» vuelve a los valores de configuración.');
    }

    /**
     * @param  array{changed: array<string, array{from: mixed, to: mixed}>}  $diff
     */
    private function log($user, array $diff, ?string $resetSection): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('helpdeskprestashop-config')
            ->causedBy($user)
            ->withProperties($diff + ['reset' => $resetSection])
            ->log('ps.settings.updated');
    }
}
