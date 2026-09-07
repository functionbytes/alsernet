<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Http\Resources\TemplateResource;
use Modules\HelpdeskTickets\Models\TicketTemplate;

class TemplatesController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        // Generales + las propias del usuario autenticado — igual que el
        // selector "Usar plantilla" de los formularios de creacion de ticket.
        $templates = TicketTemplate::active()
            ->visibleTo(auth()->id())
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'subject', 'body', 'category_id', 'priority', 'created_by']);

        return ApiResponse::success(TemplateResource::collection($templates));
    }
}
