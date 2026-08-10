<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketMailView;

/**
 * Vistas guardadas de "Emails enviados" — chips "Vistas" de la barra de
 * KPIs (mockup). CRUD deliberadamente mínimo (sin reorder ni is_public
 * editable desde aquí): lo que pidió esta pantalla es guardar/aplicar/
 * borrar el conjunto de filtros actual, no un gestor de vistas completo.
 */
class TicketMailViewsController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        $views = TicketMailView::forUser(auth()->id())->ordered()->get();

        return response()->json(['success' => true, 'views' => $views]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'filters' => ['required', 'array'],
        ]);

        $view = TicketMailView::create([
            'name' => $validated['name'],
            'filters' => $validated['filters'],
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'view' => $view], 201);
    }

    public function destroy(TicketMailView $view): JsonResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        if (! $view->canDelete(auth()->id())) {
            return response()->json(['success' => false, 'message' => 'No puedes eliminar esta vista.'], 403);
        }

        $view->delete();

        return response()->json(['success' => true]);
    }
}
