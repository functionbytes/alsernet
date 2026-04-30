<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignSuppressionList;

class SuppressionListController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CampaignSuppressionList::orderBy('created_at', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:email,domain,pattern'],
            'value' => ['required', 'string', 'max:500'],
            'is_global' => ['boolean'],
        ]);

        $entry = CampaignSuppressionList::create([
            'uid' => (string) Str::uuid(),
            ...$data,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $entry,
        ], 201);
    }

    public function show(string $uid): JsonResponse
    {
        $entry = CampaignSuppressionList::where('uid', $uid)->firstOrFail();

        return response()->json(['data' => $entry]);
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $entry = CampaignSuppressionList::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:email,domain,pattern'],
            'value' => ['sometimes', 'string', 'max:500'],
            'is_global' => ['boolean'],
        ]);

        $entry->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $entry,
        ]);
    }

    public function delete(string $uid): JsonResponse
    {
        $entry = CampaignSuppressionList::where('uid', $uid)->firstOrFail();
        $entry->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Suppression entry deleted.',
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        return response()->json([
            'email' => $request->input('email'),
            'suppressed' => CampaignSuppressionList::isSuppressed($request->input('email')),
        ]);
    }
}
