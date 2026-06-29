<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Models\CampaignBlacklist;

class BlacklistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CampaignBlacklist::query();

        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->q, '%_').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('email', 'like', $term)
                    ->orWhere('reason', 'like', $term);
            });
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 50)));

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate($perPage),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:191', 'unique:campaign_sending_server_blacklists,email'],
            'reason' => ['nullable', 'string', 'max:191'],
            'source' => ['nullable', 'string', 'max:32', 'in:manual,bounce,complaint,import'],
        ]);

        $entry = CampaignBlacklist::create([
            'email' => strtolower(trim($data['email'])),
            'reason' => $data['reason'] ?? null,
            'source' => $data['source'] ?? 'manual',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $entry,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $entry = CampaignBlacklist::findOrFail($id);

        return response()->json(['data' => $entry]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $entry = CampaignBlacklist::findOrFail($id);

        $data = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:191'],
            'source' => ['sometimes', 'string', 'max:32', 'in:manual,bounce,complaint,import'],
        ]);

        $entry->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $entry,
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        $entry = CampaignBlacklist::findOrFail($id);
        $entry->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Blacklist entry removed.',
        ]);
    }
}
