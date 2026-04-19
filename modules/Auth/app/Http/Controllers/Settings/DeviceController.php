<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\TrustedDevice;
use Modules\Auth\Services\DeviceService;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceService $devices,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $currentFingerprint = $this->devices->fingerprint($request);

        $devices = TrustedDevice::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (TrustedDevice $d) => [
                'id' => $d->id,
                'label' => $d->label,
                'platform' => $d->platform,
                'browser' => $d->browser,
                'device_type' => $d->device_type,
                'ip_address' => $d->ip_address,
                'trusted' => $d->trusted,
                'trusted_until' => $d->trusted_until?->toIso8601String(),
                'first_seen_at' => $d->first_seen_at?->toIso8601String(),
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
                'is_current' => $d->fingerprint === $currentFingerprint,
            ]);

        return response()->json(['devices' => $devices]);
    }

    public function trust(Request $request, int $id): JsonResponse
    {
        $request->validate(['label' => ['nullable', 'string', 'max:100']]);

        $device = $this->findUserDevice($request, $id);
        $this->devices->trust($device, $request->input('label'));

        return response()->json(['message' => 'Dispositivo marcado como de confianza.']);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        $device = $this->findUserDevice($request, $id);
        $this->devices->revoke($device);

        return response()->json(['message' => 'Confianza revocada.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $device = $this->findUserDevice($request, $id);
        $device->delete();

        return response()->json(['message' => 'Dispositivo eliminado.']);
    }

    private function findUserDevice(Request $request, int $id): TrustedDevice
    {
        return TrustedDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();
    }
}
