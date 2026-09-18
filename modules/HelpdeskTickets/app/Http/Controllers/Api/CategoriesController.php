<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Http\Resources\CategoryResource;
use Modules\HelpdeskTickets\Models\TicketCategory;

class CategoriesController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $categories = TicketCategory::active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'description', 'icon', 'color']);

        return ApiResponse::success(CategoryResource::collection($categories));
    }
}
