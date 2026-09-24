<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\OpsmapSaveStateMapRequest;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapAuditService;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapStateMapService;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapStateNoticeService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pantallas de administración de la integración PrestaShop:
 *  - pieza 39 "Mapeo de estados": qué pasa en el helpdesk cuando un pedido
 *    cambia de estado en la tienda;
 *  - pieza 40 "Auditoría de acciones": qué ha escrito cada agente en la
 *    tienda desde el panel, con exportación a CSV.
 */
class OpsmapController extends Controller
{
    private const EXPORT_LIMIT = 5000;

    public function __construct(
        private readonly OpsmapStateMapService $stateMap,
        private readonly OpsmapAuditService $audit,
        private readonly OpsmapStateNoticeService $notices,
        private readonly PrestashopContextService $prestashop,
    ) {}

    /**
     * Avisos al cambiar el estado de un pedido desde el chat: correo real que
     * envía cada estado (PrestaShop), "Notificar" por defecto y aviso libre.
     */
    public function stateNotices(Request $request): View
    {
        abort_unless($request->user()?->can('helpdeskprestashop.statemap.manage'), 403);

        $live = [];
        try {
            $live = $this->prestashop->getOrderStates();
        } catch (\Throwable) {
            $live = [];
        }

        $states = collect($live)
            ->filter(fn ($s) => (int) ($s['id'] ?? 0) > 0)
            ->map(fn ($s) => [
                'id' => (int) $s['id'],
                'name' => trim((string) ($s['name'] ?? '')) ?: 'Estado '.$s['id'],
                'send_email' => (bool) ($s['send_email'] ?? false),
                'template' => (string) ($s['template'] ?? ''),
                'template_label' => OpsmapStateNoticeService::templateLabel((string) ($s['template'] ?? '')),
                'shipped' => (bool) ($s['shipped'] ?? false),
                'paid' => (bool) ($s['paid'] ?? false),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $last = $this->notices->lastUpdate();
        $updater = $last['by'] ? User::query()->find($last['by']) : null;

        return view('helpdeskprestashop::ext.opsmap.state-notices', [
            'ready' => $this->notices->ready(),
            'states' => $states,
            'live' => $states !== [],
            'config' => $this->notices->all(),
            'updatedAt' => $last['at'],
            'updatedBy' => $updater ? ($updater->fullName() ?: $updater->email) : null,
        ]);
    }

    public function saveStateNotices(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('helpdeskprestashop.statemap.manage'), 403);

        if (! $this->notices->ready()) {
            return back()->with('error', 'Falta crear la tabla de avisos (migración pendiente).');
        }

        $data = $request->validate([
            'states' => ['array'],
            'states.*.notify' => ['nullable', 'in:default,yes,no'],
            'states.*.notice' => ['nullable', 'string', 'max:500'],
            'states.*.name' => ['nullable', 'string', 'max:255'],
        ], [
            'states.*.notice.max' => 'El aviso no puede pasar de 500 caracteres.',
        ]);

        $user = $request->user();
        $diff = $this->notices->save((array) ($data['states'] ?? []), $user?->getAuthIdentifier());

        if (function_exists('activity')) {
            activity('helpdeskprestashop-config')
                ->causedBy($user)
                ->withProperties($diff)
                ->log('ps.state_notices.updated');
        }

        return redirect()
            ->route('manager.helpdesk.ps.ext.opsmap.state-notices')
            ->with('success', 'Avisos de cambio de estado guardados.');
    }

    public function stateMap(Request $request): View
    {
        abort_unless($request->user()?->can('helpdeskprestashop.statemap.manage'), 403);

        $saved = $this->stateMap->load();
        $catalog = $this->stateMap->orderStates();
        $updater = $saved['updated_by'] ? User::query()->find($saved['updated_by']) : null;
        $updatedBy = $updater ? ($updater->fullName() ?: $updater->email) : null;

        return view('helpdeskprestashop::ext.opsmap.state-map', [
            'ready' => $this->stateMap->ready(),
            'states' => $catalog['states'],
            'live' => $catalog['live'],
            'map' => $saved['map'],
            'createNote' => $saved['create_note'],
            'mapActions' => $this->stateMap->availableActions(),
            'updatedAt' => $saved['updated_at'],
            'updatedBy' => $updatedBy,
            'windowDays' => (int) config('helpdeskprestashop.ext.opsmap.window_days', 30),
        ]);
    }

    public function saveStateMap(OpsmapSaveStateMapRequest $request): RedirectResponse
    {
        if (! $this->stateMap->ready()) {
            return back()->with('error', 'Falta crear la tabla del mapeo de estados (migración pendiente).');
        }

        $data = $request->validated();
        $available = $this->stateMap->availableActions();
        $map = (array) ($data['map'] ?? []);

        foreach ($map as $action) {
            if (! isset($available[$action])) {
                return back()->withInput()->with('error', 'Este helpdesk no tiene el estado «'.(OpsmapStateMapService::ACTIONS[$action] ?? $action).'».');
            }
        }

        $user = $request->user();
        $diff = $this->stateMap->save($map, (array) ($data['names'] ?? []), (bool) ($data['create_note'] ?? false), $user?->getAuthIdentifier());

        // Log aparte de 'helpdeskprestashop': esto configura el helpdesk, no
        // escribe en la tienda, y no debe aparecer en la auditoría de
        // acciones contra PrestaShop.
        if (function_exists('activity')) {
            activity('helpdeskprestashop-config')
                ->causedBy($user)
                ->withProperties($diff)
                ->log('ps.state_map.updated');
        }

        return redirect()
            ->route('manager.helpdesk.ps.ext.opsmap.state-map')
            ->with('success', 'Mapeo de estados guardado.');
    }

    public function audit(Request $request): View
    {
        abort_unless($request->user()?->can('helpdeskprestashop.ops.view'), 403);

        [$days, $agentId, $action, $search] = $this->filters($request);

        return view('helpdeskprestashop::ext.opsmap.audit', [
            'rows' => $this->audit->paginate($days, $agentId, $action, $search),
            'days' => $days,
            'periods' => $this->periods(),
            'agentId' => $agentId,
            'action' => $action,
            'search' => $search,
            'agents' => $this->audit->agentOptions($days),
            'actionOptions' => $this->audit->actionOptions($days),
        ]);
    }

    public function exportAudit(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('helpdeskprestashop.ops.view'), 403);

        [$days, $agentId, $action, $search] = $this->filters($request);
        $activities = $this->audit->baseQuery($days, $agentId, $action, $search)->limit(self::EXPORT_LIMIT)->get();
        $rows = $this->audit->present($activities);

        $filename = 'auditoria-prestashop-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM + ';': Excel en español abre así el CSV con tildes y columnas bien.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fecha', 'Agente', 'Acción', 'Cliente', 'Conversación', 'Ruta', 'Detalles'], ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => self::csvCell($v), [
                    $row['at'],
                    $row['agent'],
                    $row['text'],
                    $row['customer'] ?? '',
                    $row['conversation_label'] ?? '',
                    $row['route'] ?? $row['action'],
                    $row['details'] ? json_encode($row['details'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                ]), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * El nombre del cliente llega de canales externos: una celda que empiece
     * por = + - @ la ejecutaría Excel como fórmula. Se neutraliza con una
     * comilla simple delante, como recomienda OWASP para CSV.
     */
    private static function csvCell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    /**
     * @return array{0: int, 1: ?int, 2: ?string, 3: ?string}
     */
    private function filters(Request $request): array
    {
        $periods = $this->periods();
        $days = (int) $request->query('days', $periods[0]);
        if (! in_array($days, $periods, true)) {
            $days = $periods[0];
        }

        $agentId = $request->integer('agent') ?: null;
        $action = $request->filled('action') ? mb_substr((string) $request->query('action'), 0, 100) : null;
        $search = $request->filled('q') ? mb_substr(trim((string) $request->query('q')), 0, 100) : null;

        return [$days, $agentId, $action, $search];
    }

    /**
     * @return array<int, int>
     */
    private function periods(): array
    {
        $periods = array_values(array_filter(array_map('intval', (array) config('helpdeskprestashop.ext.opsmap.audit_periods', [7, 30]))));

        return $periods ?: [7, 30];
    }
}
