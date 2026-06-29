<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignAutomationRule;

class AutomationRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CampaignAutomationRule::orderBy('created_at', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string', 'max:64'],
            'condition' => ['nullable', 'string', 'max:64'],
            'condition_value' => ['nullable', 'array'],
            'action' => ['required', 'string', 'max:64'],
            'action_value' => ['nullable', 'array'],
            'delay_minutes' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['boolean'],
        ]);

        $rule = CampaignAutomationRule::create([
            'uid' => (string) Str::uuid(),
            ...$data,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $rule,
        ], 201);
    }

    public function show(string $uid): JsonResponse
    {
        $rule = CampaignAutomationRule::where('uid', $uid)->firstOrFail();

        return response()->json(['data' => $rule]);
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $rule = CampaignAutomationRule::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'trigger_event' => ['sometimes', 'string', 'max:64'],
            'condition' => ['nullable', 'string', 'max:64'],
            'condition_value' => ['nullable', 'array'],
            'action' => ['sometimes', 'string', 'max:64'],
            'action_value' => ['nullable', 'array'],
            'delay_minutes' => ['sometimes', 'integer', 'min:0'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $rule->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $rule,
        ]);
    }

    public function delete(string $uid): JsonResponse
    {
        $rule = CampaignAutomationRule::where('uid', $uid)->firstOrFail();
        $rule->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Rule deleted.',
        ]);
    }

    public function toggle(string $uid): JsonResponse
    {
        $rule = CampaignAutomationRule::where('uid', $uid)->firstOrFail();
        $rule->update(['enabled' => ! $rule->enabled]);

        return response()->json([
            'status' => 'success',
            'data' => $rule,
        ]);
    }
}
