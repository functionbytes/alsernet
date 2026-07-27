<?php

declare(strict_types=1);

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Engagement\Models\MobileDevice;

class MobileDeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');

        $validated = $request->validate([
            'device_token' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:ios,android'],
            'os_version' => ['nullable', 'string'],
            'app_version' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
        ]);

        $device = MobileDevice::query()->updateOrCreate(
            [
                'inbox_id' => $inbox->id,
                'device_token' => $validated['device_token'],
            ],
            [
                ...$validated,
                'last_seen_at' => now(),
                'push_enabled' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $device,
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');
        $token = $request->validate(['device_token' => ['required', 'string']])['device_token'];

        $device = MobileDevice::query()
            ->forInbox($inbox->id)
            ->where('device_token', $token)
            ->first();

        if (! $device) {
            return response()->json(['success' => false, 'message' => 'Dispositivo no encontrado.'], 404);
        }

        $device->update([
            'push_enabled' => $request->boolean('push_enabled', true),
            'locale' => $request->input('locale', $device->locale),
        ]);

        return response()->json(['success' => true, 'data' => $device]);
    }

    public function unregister(Request $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');
        $token = $request->validate(['device_token' => ['required', 'string']])['device_token'];

        MobileDevice::query()
            ->forInbox($inbox->id)
            ->where('device_token', $token)
            ->delete();

        return response()->json(['success' => true]);
    }
}
