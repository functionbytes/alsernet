<?php

namespace Modules\Auth\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Auth\Http\Middleware\RestrictStaffAccessByIp;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Services\StaffIpAllowlist;

/**
 * "Redes permitidas": modo del filtro por IP, lista de IPs/CIDR y excepciones
 * de acceso remoto por usuario (29-sep-2026). Solo super-admin.
 */
class StaffIpFilterController extends Controller
{
    public function __construct(
        private readonly StaffIpAllowlist $filter,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureSuperAdmin($request);
        $this->filter->clearCache();

        $remoteUsers = StaffIpAllowlist::remoteColumnsExist()
            ? User::query()->where('remote_access_enabled', true)->orderBy('email')->get()
            : collect();

        $outsideUsers = LoginAttempt::query()
            ->where('status', RestrictStaffAccessByIp::STATUS_NOT_ALLOWED)
            ->whereNotNull('user_id')
            ->where('attempted_at', '>=', now()->subDays(7))
            ->count();

        return view('auth::admin.ip-filter.index', [
            'mode' => $this->filter->mode(),
            'modeSource' => $this->filter->modeSource(),
            'forcedOff' => $this->filter->isForcedOff(),
            'entriesText' => StaffIpAllowlist::toText($this->filter->entries()),
            'entries' => $this->filter->entries(),
            'alwaysAllowed' => $this->filter->alwaysAllowed(),
            'currentIp' => $request->ip(),
            'currentIpAllowed' => $this->filter->isAllowed($request->ip()),
            'remoteUsers' => $remoteUsers,
            'remoteColumns' => StaffIpAllowlist::remoteColumnsExist(),
            'outsideUsers7d' => $outsideUsers,
        ]);
    }

    public function updateMode(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'mode' => ['required', 'in:'.implode(',', StaffIpAllowlist::MODES)],
        ]);

        // Protección contra auto-bloqueo: no se activa enforce desde fuera de la lista.
        if ($data['mode'] === StaffIpAllowlist::MODE_ENFORCE && ! $this->filter->isAllowed($request->ip())) {
            return back()->withErrors(['mode' => "Tu IP actual ({$request->ip()}) no está en la lista: no se activa el bloqueo para que no te quedes fuera."]);
        }

        if ($data['mode'] === StaffIpAllowlist::MODE_ENFORCE && $this->filter->entries() === []) {
            return back()->withErrors(['mode' => 'La lista está vacía: añade las redes de la empresa antes de activar el bloqueo.']);
        }

        $this->filter->setMode($data['mode']);
        $this->audit($request, 'staff_ip_filter_mode', ['mode' => $data['mode']]);

        return back()->with('success', 'Modo del filtro actualizado: '.$data['mode'].'.');
    }

    public function updateAllowlist(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $request->validate(['allowlist' => ['nullable', 'string', 'max:10000']]);

        try {
            $entries = StaffIpAllowlist::parseText((string) $request->input('allowlist', ''));
            $this->filter->saveEntries($entries, $request->ip());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['allowlist' => $e->getMessage()]);
        }

        $this->audit($request, 'staff_ip_filter_allowlist', ['entries' => $entries]);

        return back()->with('success', 'Lista de redes permitidas guardada ('.count($entries).' entradas).');
    }

    public function updateRemoteAccess(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_without:email'],
            'email' => ['nullable', 'email', 'exists:users,email', 'required_without:user_id'],
            'enabled' => ['required', 'boolean'],
            'until' => ['nullable', 'date', 'after:now'],
        ]);

        $target = isset($data['user_id'])
            ? User::findOrFail($data['user_id'])
            : User::where('email', $data['email'])->firstOrFail();

        $until = ! empty($data['until']) ? Carbon::parse($data['until']) : null;
        $enabled = (bool) $data['enabled'];

        try {
            $this->filter->setRemoteAccess($target, $enabled, $until, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['remote' => $e->getMessage()]);
        }

        $msg = $enabled
            ? "Acceso remoto concedido a {$target->email}".($until ? ' hasta '.$until->format('d/m/Y H:i') : '').'.'
            : "Acceso remoto retirado a {$target->email}.";

        if ($enabled && ! $target->hasTwoFactorEnabled()) {
            $msg .= ' Atención: no tiene 2FA activado, la excepción no surtirá efecto hasta que lo active.';
        }

        return back()->with('success', $msg);
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);
    }

    private function audit(Request $request, string $event, array $properties): void
    {
        try {
            activity()
                ->causedBy($request->user())
                ->event($event)
                ->withProperties($properties + ['ip' => $request->ip()])
                ->log('Filtro por IP del personal: '.$event);
        } catch (\Throwable) {
            // El registro de actividad no debe impedir el cambio.
        }
    }
}
