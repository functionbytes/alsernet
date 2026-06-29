<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Inbox;

class AgentInboxCapacityController extends Controller
{
    /**
     * GET /panel/helpdesk/settings/agents/{user}/inbox-capacity
     * Returns capacities for all inboxes for a given agent (AJAX).
     */
    public function index(User $user): JsonResponse
    {
        $this->authorize('helpdesk.agents.manage');

        $inboxes = Inbox::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'channel_type']);

        $capacities = AgentInboxCapacity::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('inbox_id');

        $data = $inboxes->map(function (Inbox $inbox) use ($capacities) {
            $cap = $capacities->get($inbox->id);

            return [
                'inbox_id' => $inbox->id,
                'inbox_name' => $inbox->name,
                'channel_type' => $inbox->channel_type,
                'max_concurrent' => $cap?->max_concurrent ?? 0,
                'accepts_new' => $cap?->accepts_new ?? true,
            ];
        });

        return response()->json(['capacities' => $data]);
    }

    /**
     * PUT /panel/helpdesk/settings/agents/{user}/inbox-capacity
     * Upsert capacity rows for the given agent (AJAX).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('helpdesk.agents.manage');

        $request->validate([
            'capacities' => ['required', 'array'],
            'capacities.*.inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'capacities.*.max_concurrent' => ['required', 'integer', 'min:0'],
            'capacities.*.accepts_new' => ['required', 'boolean'],
        ]);

        foreach ($request->input('capacities') as $row) {
            AgentInboxCapacity::query()->updateOrCreate(
                ['user_id' => $user->id, 'inbox_id' => $row['inbox_id']],
                [
                    'max_concurrent' => (int) $row['max_concurrent'],
                    'accepts_new' => (bool) $row['accepts_new'],
                ]
            );
        }

        return response()->json(['ok' => true]);
    }
}
