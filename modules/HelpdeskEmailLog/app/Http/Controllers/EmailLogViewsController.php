<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailLogView;

/**
 * Vistas guardadas del log de emails — mismo patrón/alcance deliberadamente
 * mínimo que Modules\HelpdeskTickets\Http\Controllers\Managers\
 * TicketMailViewsController: guardar/listar/borrar el conjunto de filtros
 * actual, no un gestor de vistas completo (sin reorder ni edición de
 * is_public tras crearla). is_public solo puede fijarse al crear, y solo
 * por quien tiene 'helpdeskemaillog.manage' — ver store().
 */
class EmailLogViewsController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $views = EmailLogView::forUser(auth()->id())->ordered()->get();

        return response()->json(['success' => true, 'views' => $views]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'filters' => ['required', 'array'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        // Solo quien puede gestionar el log (helpdeskemaillog.manage) puede
        // publicar una vista para todo el equipo; un viewer normal que mande
        // is_public=true no recibe error, simplemente se ignora en silencio
        // y la vista se crea privada igual que siempre.
        $canManage = $request->user()?->can('helpdeskemaillog.manage') ?? false;
        $isPublic = $canManage && (bool) ($validated['is_public'] ?? false);

        $view = EmailLogView::create([
            'name' => $validated['name'],
            'filters' => $validated['filters'],
            'user_id' => auth()->id(),
            'is_public' => $isPublic,
        ]);

        return response()->json(['success' => true, 'view' => $view], 201);
    }

    public function destroy(EmailLogView $view): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        if (! $view->canDelete(auth()->id())) {
            return response()->json(['success' => false, 'message' => __('helpdeskemaillog::emaillog.views.cannot_delete')], 403);
        }

        $view->delete();

        return response()->json(['success' => true]);
    }
}
