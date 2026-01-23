<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Reports;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;

class AgentStatusController extends Controller
{
    /**
     * Get current user's status.
     */
    public function show()
    {
        $user = auth()->user();

        return response()->json([
            'availability_status' => $user->availability_status,
            'custom_status' => $user->custom_status,
            'last_seen_at' => $user->last_seen_at,
        ]);
    }

    /**
     * Update agent availability status.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'availability_status' => 'required|in:online,busy,away,offline',
            'custom_status' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $user->setAvailabilityStatus(
            $validated['availability_status'],
            $validated['custom_status'] ?? null
        );

        return response()->json([
            'message' => 'Status updated successfully',
            'availability_status' => $user->fresh()->availability_status,
            'custom_status' => $user->fresh()->custom_status,
        ]);
    }

    /**
     * Update last seen timestamp (heartbeat).
     */
    public function heartbeat()
    {
        auth()->user()->updateLastSeen();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get all agents status for account.
     */
    public function index()
    {
        $agents = User::where('account_id', auth()->user()->account_id)
            ->select('id', 'name', 'email', 'availability_status', 'custom_status', 'last_seen_at')
            ->orderBy('name')
            ->get()
            ->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'availability_status' => $agent->availability_status,
                    'custom_status' => $agent->custom_status,
                    'last_seen_at' => $agent->last_seen_at,
                    'is_online' => $agent->isOnline(),
                    'is_available' => $agent->isAvailable(),
                ];
            });

        return response()->json(['agents' => $agents]);
    }

    /**
     * Get available agents count.
     */
    public function available()
    {
        $count = User::where('account_id', auth()->user()->account_id)
            ->whereIn('availability_status', ['online', 'busy'])
            ->count();

        return response()->json(['available_count' => $count]);
    }
}
