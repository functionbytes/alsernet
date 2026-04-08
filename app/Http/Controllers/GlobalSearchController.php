<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GlobalSearchController extends Controller
{
    public function __construct(private readonly GlobalSearchService $searchService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:3|max:100']);

        $query = $request->string('q')->trim()->value();

        $cacheKey = 'global_search:'.md5($query.':'.($request->user()?->id ?? 'guest'));

        $results = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query) {
            return $this->searchService->search($query);
        });

        return response()->json(['results' => $results]);
    }
}
