<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreTicketTemplateRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateTicketTemplateRequest;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketTemplate;

class TicketTemplatesController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TicketTemplate::class);

        $userId = $request->user()->id;

        // Búsqueda + filtros (comparados contra el patrón ya establecido en
        // _auto-reply-section.blade.php / bulk.js): mismos query params en
        // ambas tablas (generales/mías) para que un filtro activo no deje
        // resultados inconsistentes entre pestañas.
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->integer('category') ?: null;
        $priority = (string) $request->query('priority', '') ?: null;
        $status = (string) $request->query('status', '') ?: null; // 'active' | 'inactive'

        $applyFilters = function ($query) use ($search, $categoryId, $priority, $status) {
            return $query
                ->when($search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")))
                ->when($categoryId, fn ($q) => $q->forCategory($categoryId))
                ->when($priority, fn ($q) => $q->where('priority', $priority))
                ->when($status === 'active', fn ($q) => $q->where('is_active', true))
                ->when($status === 'inactive', fn ($q) => $q->where('is_active', false));
        };

        $general = $applyFilters(TicketTemplate::query()->general())
            ->with('category')
            ->orderBy('name')
            ->paginate(20, ['*'], 'general_page')
            ->appends($request->query());

        $mine = $applyFilters(TicketTemplate::query()->ownedBy($userId))
            ->with('category')
            ->orderBy('name')
            ->paginate(20, ['*'], 'mine_page')
            ->appends($request->query());

        // Los totales de las tarjetas de arriba son del conjunto COMPLETO, sin
        // filtrar — igual que en Bienvenida, no reflejan la búsqueda activa.
        $stats = [
            'total' => TicketTemplate::query()->count(),
            'general' => TicketTemplate::query()->general()->count(),
            'mine' => TicketTemplate::query()->ownedBy($userId)->count(),
            'active' => TicketTemplate::query()->where('is_active', true)->count(),
        ];

        return view('helpdesktickets::managers.ticket-templates.index', [
            'general' => $general,
            'mine' => $mine,
            'stats' => $stats,
            'categories' => TicketCategory::active()->ordered()->get(),
            'canManageGeneral' => $request->user()->can('helpdesk.tickets.manage'),
        ]);
    }

    /**
     * Acción masiva (Activar/Desactivar/Eliminar) — mismo contrato que
     * settings.helpdesk.conversation-greetings.bulk-action (bulk.js espera
     * {action, ids} y responde {success, message}). Cada id se autoriza
     * individualmente con la misma Policy que ya usan update()/destroy(): una
     * plantilla general requiere helpdesk.tickets.manage, una personal solo
     * la puede tocar su dueño — así que IDs mezclados de ambas pestañas (si
     * el usuario cambió de tab sin limpiar la selección) se filtran solos sin
     * que el front tenga que saberlo.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $templates = TicketTemplate::query()->whereIn('id', $validated['ids'])->get();

        $count = 0;
        foreach ($templates as $template) {
            if ($validated['action'] === 'delete') {
                if ($request->user()->cannot('delete', $template)) {
                    continue;
                }
                $template->delete();
                $count++;

                continue;
            }

            if ($request->user()->cannot('update', $template)) {
                continue;
            }
            $template->is_active = $validated['action'] === 'activate';
            $template->save();
            $count++;
        }

        $verb = match ($validated['action']) {
            'activate' => 'activada(s)',
            'deactivate' => 'desactivada(s)',
            'delete' => 'eliminada(s)',
        };

        return response()->json([
            'success' => true,
            'message' => $count > 0
                ? "{$count} plantilla(s) {$verb}."
                : 'No se aplicó ningún cambio (sin permiso sobre las plantillas seleccionadas).',
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', TicketTemplate::class);

        $categories = TicketCategory::active()->ordered()->get();

        return view('helpdesktickets::managers.ticket-templates.form', [
            'template' => null,
            'categories' => $categories,
            'canManageGeneral' => $request->user()->can('helpdesk.tickets.manage'),
        ]);
    }

    public function store(StoreTicketTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $isGeneral = $request->boolean('is_general') && $request->user()->can('helpdesk.tickets.manage');
        unset($validated['is_general']);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = $isGeneral ? null : $request->user()->id;

        TicketTemplate::create($validated);

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.template_created'));
    }

    public function edit(Request $request, TicketTemplate $ticketTemplate): View
    {
        $this->authorize('update', $ticketTemplate);

        $categories = TicketCategory::active()->ordered()->get();

        return view('helpdesktickets::managers.ticket-templates.form', [
            'template' => $ticketTemplate,
            'categories' => $categories,
            'canManageGeneral' => $request->user()->can('helpdesk.tickets.manage'),
        ]);
    }

    public function update(UpdateTicketTemplateRequest $request, TicketTemplate $ticketTemplate): RedirectResponse
    {
        $validated = $request->validated();

        $canManageGeneral = $request->user()->can('helpdesk.tickets.manage');
        $isGeneral = $canManageGeneral ? $request->boolean('is_general') : $ticketTemplate->isGeneral();
        unset($validated['is_general']);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = $isGeneral ? null : ($ticketTemplate->created_by ?? $request->user()->id);

        $ticketTemplate->update($validated);

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.template_updated'));
    }

    public function destroy(TicketTemplate $ticketTemplate): RedirectResponse
    {
        $this->authorize('delete', $ticketTemplate);

        $ticketTemplate->delete();

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.template_deleted'));
    }

    /**
     * "Usarla como base para la mía": un agente sin helpdesk.tickets.manage
     * no puede editar una plantilla general, pero sí puede querer partir de
     * ella para tener su propia copia editable. Mismo gate que view() —
     * cualquier plantilla que el agente puede VER en el listado (general o
     * suya) se puede duplicar; la copia nace SIEMPRE personal (created_by =
     * quien duplica), incluso si el original era general, porque de eso se
     * trata: una versión propia que no dependa de permisos de gestión.
     */
    public function duplicate(Request $request, TicketTemplate $ticketTemplate): RedirectResponse
    {
        $this->authorize('view', $ticketTemplate);

        $copy = $ticketTemplate->replicate(['created_by']);
        $copy->name = $ticketTemplate->name.' (copia)';
        $copy->created_by = $request->user()->id;
        $copy->is_active = true;
        $copy->save();

        return redirect()
            ->route('manager.helpdesk.ticket-templates.edit', $copy->id)
            ->with('success', 'Plantilla duplicada. Ya es tuya: edítala como quieras.');
    }
}
