<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Notification\Models\NotificationPreference;
use Modules\Notification\Services\NotificationTypeRegistry;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationTypeRegistry $registry
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $types = $this->registry->all();

        $existing = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get();

        // Build a map: type => [channel => enabled]
        $prefs = [];
        foreach ($existing as $pref) {
            $prefs[$pref->notification_type][$pref->channel] = $pref->enabled;
        }

        return view('notification::preferences.index', compact('types', 'prefs'));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.channel' => 'required|in:mail,database,broadcast',
            'preferences.*.enabled' => 'boolean',
        ]);

        $user = $request->user();

        foreach ($request->input('preferences') as $pref) {
            NotificationPreference::toggle(
                $user->id,
                $pref['channel'],
                $pref['notification_type'],
                (bool) ($pref['enabled'] ?? true)
            );
        }

        return response()->json(['message' => 'Preferencias guardadas']);
    }
}
