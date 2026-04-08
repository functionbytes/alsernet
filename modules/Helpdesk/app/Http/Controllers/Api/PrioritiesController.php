<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\Priority;

class PrioritiesController extends Controller
{
    public function index(): JsonResponse
    {
        $priorities = Priority::where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'slug', 'level', 'color', 'response_time_hours', 'resolution_time_hours']);

        return response()->json($priorities);
    }
}
