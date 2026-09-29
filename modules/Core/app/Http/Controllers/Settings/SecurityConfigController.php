<?php

namespace Modules\Core\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Core\Services\SecurityConfigService as Cfg;
use Modules\Core\Services\SecurityStatusService;

/**
 * Panel > Ajustes > Configuración de seguridad (29-sep-2026, H4). Solo super-admin
 * (Gate security.config.manage, también en la ruta). Ver SecurityConfigService.
 */
class SecurityConfigController extends Controller
{
    public const SECTIONS = ['access', 'headers', 'watch', 'erp', 'documents', 'helpdesk', 'ai', 'supplier'];

    public function __construct(
        private readonly SecurityStatusService $status,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('security.config.manage');

        $settings = [];
        foreach (Cfg::DEFINITIONS as $id => $def) {
            $settings[$id] = [
                'def' => $def,
                'value' => $def['type'] === 'secret' ? null : Cfg::effective($id),
                'configured' => $def['type'] === 'secret' ? filled(Cfg::effective($id)) : null,
                'display' => Cfg::display($id, Cfg::effective($id)),
                'env_display' => Cfg::display($id, Cfg::original($id)),
                'override' => Cfg::hasOverride($id),
            ];
        }

        $twoFactorRoles = (array) Cfg::effective('two_factor_roles');

        return view('core::settings.security-config.index', [
            'settings' => $settings,
            'ipFilter' => $this->status->ipFilter(),
            'policy' => $this->status->passwordPolicy(),
            'roles' => $this->status->roles(),
            'without2fa' => $this->status->usersWithout2fa($twoFactorRoles),
            'csp' => $this->status->cspReports(7),
            'watch' => $this->status->watchStatus(),
            'system' => $this->status->system(),
            'forceOff' => (bool) config('auth.auth-policy.staff_ip_filter.force_off', false),
            'generatedSecret' => $request->session()->pull('security_config_generated_secret'),
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        Gate::authorize('security.config.manage');
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $values = match ($section) {
            'access' => $this->access($request),
            'headers' => $this->headers($request),
            'watch' => $this->watch($request),
            'erp' => $this->erp($request),
            'documents' => $this->documents($request),
            'helpdesk' => $this->helpdesk($request),
            'ai' => $this->ai($request),
            'supplier' => $this->supplier($request),
        };

        $changes = Cfg::save($values, $request->user(), $request->ip());

        if ($changes === []) {
            return $this->back($section)->with('info', 'Sin cambios.');
        }

        $msg = 'Guardado: '.implode('; ', array_map(
            fn ($c) => Cfg::DEFINITIONS[$c['id']]['label'].': '.$c['old'].' → '.$c['new'],
            $changes
        )).'.';

        $warning = $this->afterSaveWarning($section, array_column($changes, 'id'));

        $redirect = $this->back($section)->with('success', $msg);

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }

    /** Borra el ajuste guardado de una clave: vuelve a mandar .env/config. */
    public function reset(Request $request, string $key): RedirectResponse
    {
        Gate::authorize('security.config.manage');
        abort_unless(isset(Cfg::DEFINITIONS[$key]), 404);

        $section = Cfg::DEFINITIONS[$key]['section'];

        if ($key === 'documents_prestashop_secret' && ! filled(Cfg::original($key)) && Cfg::effective('documents_require_signed')) {
            return $this->back($section)->withErrors(['documents_prestashop_secret' => 'Con la firma obligatoria activada no se puede quitar el secreto: la creación de documentos dejaría de funcionar.']);
        }

        $done = Cfg::reset($key, $request->user(), $request->ip());

        return $this->back($section)->with(
            $done ? 'success' : 'info',
            $done ? Cfg::DEFINITIONS[$key]['label'].': vuelve a usarse el valor de .env/config.' : 'No había ningún ajuste guardado.'
        );
    }

    /** Genera un secreto HMAC nuevo, lo guarda cifrado y lo muestra una sola vez. */
    public function generateSecret(Request $request): RedirectResponse
    {
        Gate::authorize('security.config.manage');

        $request->validate(['confirm' => ['accepted']], [
            'confirm.accepted' => 'Marca la casilla para confirmar que vas a actualizar el secreto también en la tienda.',
        ]);

        $secret = bin2hex(random_bytes(32));
        Cfg::save(['documents_prestashop_secret' => $secret], $request->user(), $request->ip());

        // Se entrega por flash (una lectura) y se borra al mostrarlo.
        $request->session()->flash('security_config_generated_secret', $secret);

        return $this->back('documents')->with('success', 'Secreto nuevo guardado (cifrado). Cópialo ahora: no se volverá a mostrar.');
    }

    // ------------------------------------------------------------------

    private function access(Request $request): array
    {
        $roles = $this->status->roles();
        $data = $request->validate([
            'two_factor_enabled' => ['required', 'boolean'],
            'two_factor_roles' => ['required', 'array', 'min:1'],
            'two_factor_roles.*' => ['string', Rule::in($roles)],
        ], [
            'two_factor_roles.required' => 'Elige al menos un rol.',
            'two_factor_roles.*.in' => 'Rol desconocido.',
        ]);

        $selected = array_values(array_intersect($roles, $data['two_factor_roles']));

        return [
            'two_factor_enabled' => (bool) $data['two_factor_enabled'],
            'two_factor_roles' => $selected,
        ];
    }

    private function headers(Request $request): array
    {
        $data = $request->validate([
            'csp_mode' => ['required', Rule::in(['off', 'report', 'enforce'])],
            'robots_tag' => ['required', 'boolean'],
        ]);

        $robots = null;
        if ($data['robots_tag']) {
            $original = Cfg::original('robots_tag');
            $robots = filled($original) ? (string) $original : Cfg::DEFAULT_ROBOTS_TAG;
        } elseif (! filled(Cfg::original('robots_tag'))) {
            // Mismo "apagado" que trae .env (null/''): no se crea fila.
            $robots = Cfg::original('robots_tag');
        }

        $values = [
            'csp_enabled' => $data['csp_mode'] !== 'off',
            'robots_tag' => $robots,
        ];
        // Con la CSP apagada el modo informe/bloqueo no se toca.
        if ($data['csp_mode'] !== 'off') {
            $values['csp_report_only'] = $data['csp_mode'] === 'report';
        }

        return $values;
    }

    private function watch(Request $request): array
    {
        $rules = [];
        foreach (Cfg::sectionIds('watch') as $id) {
            $def = Cfg::DEFINITIONS[$id];
            $rules[$id] = ['required', 'integer', 'min:'.$def['min'], 'max:'.$def['max']];
        }

        return array_map('intval', $request->validate($rules));
    }

    private function erp(Request $request): array
    {
        $request->validate(['erp_allowed_ips' => ['required', 'string', 'max:4000']]);

        $entries = Cfg::splitList($request->input('erp_allowed_ips'));
        $invalid = array_values(array_filter($entries, fn ($e) => ! Cfg::isValidIpOrCidr($e)));

        if ($entries === []) {
            throw ValidationException::withMessages(['erp_allowed_ips' => 'La lista no puede quedar vacía: nadie podría usar /api/erp sin token.']);
        }
        if ($invalid !== []) {
            throw ValidationException::withMessages(['erp_allowed_ips' => 'IP o rango CIDR no válido: '.implode(', ', $invalid)]);
        }
        if (count($entries) > 100) {
            throw ValidationException::withMessages(['erp_allowed_ips' => 'Máximo 100 entradas.']);
        }

        return ['erp_allowed_ips' => implode(',', $entries)];
    }

    private function documents(Request $request): array
    {
        $data = $request->validate([
            'documents_require_signed' => ['required', 'boolean'],
            'documents_signed_url_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            // Solo escritura: vacío = sin cambios.
            'documents_prestashop_secret' => ['nullable', 'string', 'min:32', 'max:255', 'regex:/^[\x21-\x7E]+$/'],
        ], [
            'documents_prestashop_secret.regex' => 'El secreto no puede tener espacios ni caracteres no imprimibles.',
        ]);

        $values = [
            'documents_require_signed' => (bool) $data['documents_require_signed'],
            'documents_signed_url_minutes' => (int) $data['documents_signed_url_minutes'],
        ];

        $secret = (string) ($data['documents_prestashop_secret'] ?? '');
        if ($secret !== '') {
            $values['documents_prestashop_secret'] = $secret;
        }

        // Firma obligatoria sin secreto = /api/documents responde 503 a la tienda.
        $willHaveSecret = $secret !== '' || filled(Cfg::effective('documents_prestashop_secret'));
        if ($values['documents_require_signed'] && ! $willHaveSecret) {
            throw ValidationException::withMessages(['documents_require_signed' => 'Configura antes el secreto HMAC: sin él se rechazarían todas las llamadas de la tienda.']);
        }

        return $values;
    }

    private function helpdesk(Request $request): array
    {
        $data = $request->validate([
            'helpdesk_attachments_disk' => ['required', Rule::in(Cfg::DEFINITIONS['helpdesk_attachments_disk']['options'])],
        ]);

        if ($data['helpdesk_attachments_disk'] === 'local' && Cfg::effective('helpdesk_attachments_disk') !== 'local') {
            $request->validate(['confirm_local' => ['accepted']], [
                'confirm_local.accepted' => 'Confirma que la APP_KEY se ha rotado antes de pasar los adjuntos al disco local.',
            ]);
        }

        return $data;
    }

    private function ai(Request $request): array
    {
        $data = $request->validate([
            'mcp_server_enabled' => ['required', 'boolean'],
            'local_llm_allowed_hosts' => ['nullable', 'string', 'max:2000'],
        ]);

        return [
            'mcp_server_enabled' => (bool) $data['mcp_server_enabled'],
            'local_llm_allowed_hosts' => $this->hosts('local_llm_allowed_hosts', $data['local_llm_allowed_hosts'] ?? '', true),
        ];
    }

    private function supplier(Request $request): array
    {
        $data = $request->validate([
            'supplier_erp_allowed_hosts' => ['required', 'string', 'max:2000'],
        ]);

        return ['supplier_erp_allowed_hosts' => $this->hosts('supplier_erp_allowed_hosts', $data['supplier_erp_allowed_hosts'], false)];
    }

    private function hosts(string $field, string $text, bool $allowEmpty): array
    {
        $hosts = array_map('strtolower', Cfg::splitList($text));
        $invalid = array_values(array_filter($hosts, fn ($h) => ! Cfg::isValidHost($h)));

        if ($invalid !== []) {
            throw ValidationException::withMessages([$field => 'Host no válido (solo nombre o IP, sin http:// ni puerto): '.implode(', ', $invalid)]);
        }
        if (! $allowEmpty && $hosts === []) {
            throw ValidationException::withMessages([$field => 'La lista no puede quedar vacía.']);
        }
        if (count($hosts) > 50) {
            throw ValidationException::withMessages([$field => 'Máximo 50 hosts.']);
        }

        return $hosts;
    }

    private function afterSaveWarning(string $section, array $changed): ?string
    {
        if ($section === 'access' && Cfg::effective('two_factor_enabled')) {
            $pending = $this->status->usersWithout2fa((array) Cfg::effective('two_factor_roles'), 0);
            if ($pending['total']) {
                return "2FA obligatorio activo: {$pending['total']} usuario(s) de esos roles aún no tienen 2FA y solo podrán ir a Perfil > Doble factor hasta activarlo.";
            }
        }
        if (in_array('mcp_server_enabled', $changed, true) && app()->routesAreCached()) {
            return 'Las rutas están cacheadas: el cambio del servidor MCP se aplica al regenerar la caché de rutas (php artisan route:cache).';
        }
        if (in_array('helpdesk_attachments_disk', $changed, true)) {
            return 'Los adjuntos ya guardados no se mueven solos: los nuevos van al disco elegido.';
        }

        return null;
    }

    private function back(string $section): RedirectResponse
    {
        return redirect()->to(route('settings.security.config').'#'.$section);
    }
}
