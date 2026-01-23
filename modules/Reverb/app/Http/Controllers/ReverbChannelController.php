<?php

namespace Modules\Reverb\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ReverbChannelController extends Controller
{
    /**
     * List all available channels.
     */
    public function list(): JsonResponse
    {
        $channels = [
            'public' => [],
            'private' => [],
            'presence' => [],
        ];

        return response()->json($channels);
    }

    /**
     * Show channel details and subscribers.
     */
    public function show(string $channel): JsonResponse
    {
        $details = [
            'name' => $channel,
            'type' => $this->getChannelType($channel),
            'subscribers' => 0,
            'created_at' => now()->toIso8601String(),
        ];

        return response()->json($details);
    }

    /**
     * Broadcast an event to a channel.
     */
    public function broadcast(string $channel): JsonResponse
    {
        // Broadcasting logic would be implemented here
        return response()->json([
            'success' => true,
            'message' => "Event broadcasted to channel: {$channel}",
        ]);
    }

    /**
     * Get presence information for a channel.
     */
    public function presence(string $channel): JsonResponse
    {
        $presence = [
            'channel' => $channel,
            'count' => 0,
            'here' => [],
            'joining' => [],
            'leaving' => [],
        ];

        return response()->json($presence);
    }

    /**
     * Determine channel type based on naming convention.
     */
    private function getChannelType(string $channel): string
    {
        if (str_starts_with($channel, 'presence-')) {
            return 'presence';
        }

        if (str_starts_with($channel, 'private-')) {
            return 'private';
        }

        return 'public';
    }
}
