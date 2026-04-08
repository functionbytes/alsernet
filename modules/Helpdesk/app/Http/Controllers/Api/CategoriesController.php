<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\TicketCategory;

class CategoriesController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TicketCategory::active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'description', 'icon', 'color']);

        return response()->json($categories);
    }
}
