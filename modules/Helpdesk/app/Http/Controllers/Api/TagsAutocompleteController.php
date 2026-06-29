<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\ConversationTag;

class TagsAutocompleteController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tags.view');
    }

    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $tags = ConversationTag::query()
            ->where('name', 'like', "%{$q}%")
            ->where('is_active', true)
            ->select(['id', 'name', 'color'])
            ->take(10)
            ->get();

        return response()->json($tags);
    }
}
