<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionMacroRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreMacroRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateMacroRequest;
use Modules\HelpdeskTickets\Models\Macro;

class MacrosController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    public function index(Request $request): View
    {
        $query = Macro::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $macros = $query->orderBy('name')->paginate(20);

        $stats = [
            'total' => Macro::count(),
            'active' => Macro::where('is_active', true)->count(),
            'shared' => Macro::where('is_shared', true)->count(),
            'total_uses' => (int) Macro::sum('usage_count'),
        ];

        return view('helpdesktickets::managers.settings.macros.index', compact('macros', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesktickets::managers.settings.macros.create', [
            'actionTypes' => Macro::$actionTypes,
        ]);
    }

    public function store(StoreMacroRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['is_shared'] = $request->boolean('is_shared', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['user_id'] = auth()->id();

        Macro::create($validated);

        return redirect()
            ->route('manager.helpdesk.settings.macros.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.macro.created'));
    }

    public function edit(Macro $macro): View
    {
        $this->authorizeOwnership($macro);

        return view('helpdesktickets::managers.settings.macros.edit', [
            'macro' => $macro,
            'actionTypes' => Macro::$actionTypes,
        ]);
    }

    public function update(UpdateMacroRequest $request, Macro $macro): RedirectResponse
    {
        $this->authorizeOwnership($macro);

        $validated = $request->validated();

        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['is_shared'] = $request->boolean('is_shared', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $macro->update($validated);

        return redirect()
            ->route('manager.helpdesk.settings.macros.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.macro.updated'));
    }

    public function destroy(Macro $macro): RedirectResponse
    {
        $this->authorizeOwnership($macro);

        $macro->delete();

        return redirect()
            ->route('manager.helpdesk.settings.macros.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.macro.deleted'));
    }

    /**
     * Auditoría de seguridad (14-sep-2026): el middleware de esta pantalla
     * solo exige helpdesk.tickets.settings, un permiso que en producción
     * tienen 77+ cuentas (roles super-settings/helpdesk-admin/super-admin),
     * no solo un admin único. Antes de este fix, edit/update/destroy y
     * bulkAction() confiaban solo en ese permiso de ruta e ignoraban por
     * completo is_shared/user_id del macro: cualquiera de esas cuentas podía
     * editar o borrar por id el macro PRIVADO de otra (mismo problema que
     * MacroPolicy::apply() ya evita para "aplicar" un macro a un ticket,
     * pero que aquí, en ajustes, nunca se comprobaba). Un macro compartido
     * (is_shared) o el permiso amplio de gestión siguen permitiendo tocarlo;
     * uno privado de otro usuario, no.
     */
    protected function authorizeOwnership(Macro $macro): void
    {
        abort_unless($this->canManage($macro), 403, 'No tienes permisos para gestionar este macro.');
    }

    /**
     * Versión sin abort() para bulkAction(): ahí un macro ajeno no debe
     * tumbar la petición entera, solo quedar fuera del lote (mismo criterio
     * que TicketCannedRepliesController::bulkAction()).
     */
    protected function canManage(Macro $macro): bool
    {
        return $macro->is_shared
            || (int) $macro->user_id === (int) auth()->id()
            || auth()->user()->can('helpdesk.tickets.manage');
    }

    /**
     * Apply a bulk action (activate, deactivate or delete) to several macros.
     */
    public function bulkAction(BulkActionMacroRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;

        $macros = Macro::whereIn('id', $ids)->get();
        $skipped = 0;

        if ($action === 'delete') {
            foreach ($macros as $macro) {
                if (! $this->canManage($macro)) {
                    $skipped++;

                    continue;
                }

                $macro->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($macros as $macro) {
                if (! $this->canManage($macro)) {
                    $skipped++;

                    continue;
                }

                $macro->update(['is_active' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminada(s)', 'activate' => 'activada(s)', 'deactivate' => 'desactivada(s)'];
        $message = "{$count} macro(s) {$labels[$action]}.";
        if ($skipped > 0) {
            $message .= " {$skipped} omitida(s) por ser macros privados de otro usuario.";
        }

        return response()->json([
            'message' => $message,
            'count' => $count,
            'skipped' => $skipped,
        ]);
    }
}
