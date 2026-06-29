<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignSegment;

class SegmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CampaignSegment::with('mailList:id,uid,name')->orderBy('created_at', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'matching' => ['required', 'in:all,any'],
            'mail_list_id' => ['required', 'exists:campaign_maillists,id'],
        ]);

        $segment = CampaignSegment::create([
            'uid' => (string) Str::uuid(),
            ...$data,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $segment,
        ], 201);
    }

    public function show(string $uid): JsonResponse
    {
        $segment = CampaignSegment::where('uid', $uid)->with('conditions')->firstOrFail();

        return response()->json(['data' => $segment]);
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $segment = CampaignSegment::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'matching' => ['sometimes', 'in:all,any'],
            'mail_list_id' => ['sometimes', 'exists:campaign_maillists,id'],
        ]);

        $segment->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $segment,
        ]);
    }

    public function delete(string $uid): JsonResponse
    {
        $segment = CampaignSegment::where('uid', $uid)->firstOrFail();
        $segment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Segment deleted.',
        ]);
    }

    public function preview(string $uid): JsonResponse
    {
        $segment = CampaignSegment::where('uid', $uid)->with('conditions')->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => [
                'matching_subscribers' => $segment->subscribersCount(),
                'segment' => [
                    'uid' => $segment->uid,
                    'name' => $segment->name,
                    'matching' => $segment->matching,
                ],
            ],
        ]);
    }

    public function subscribers(Request $request, string $uid): JsonResponse
    {
        $segment = CampaignSegment::where('uid', $uid)->with('conditions')->firstOrFail();

        $perPage = min(100, max(1, (int) $request->input('per_page', 25)));

        $query = $segment->subscribers();

        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->q, '%_').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('email', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate($perPage),
        ]);
    }
}
