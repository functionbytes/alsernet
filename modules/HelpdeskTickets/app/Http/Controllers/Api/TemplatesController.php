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
        $this->authorize('helpdesk.cannedreplies.view');

        $templates = TicketTemplate::active()
            ->with('category:id,name')
            ->orderBy('firstname')
            ->get(['id', 'name', 'description', 'subject', 'body', 'category_id']);

        return ApiResponse::success(TemplateResource::collection($templates));
    }
}
