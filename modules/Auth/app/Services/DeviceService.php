<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Auth\Events\NewDeviceDetected;
use Modules\Auth\Models\TrustedDevice;

class DeviceService
{
    /**
     * Fingerprint a device using user_agent + accept-language. Stable across same browser.
     */
    public function fingerprint(Request $request): string
    {
        $components = [
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language'),
            (string) $request->header('Accept-Encoding'),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Register or update the device for the given user and trigger NewDeviceDetected on first seen.
     */
    public function registerForUser(User $user, Request $request): TrustedDevice
    {
        if (! config('auth.auth-policy.devices.enabled', true)) {
            return new TrustedDevice;
        }

        $fingerprint = $this->fingerprint($request);
        $existing = TrustedDevice::query()
            ->where('user_id', $user->id)
            ->where('fingerprint', $fingerprint)
            ->first();

        $parsed = $this->parseUserAgent($request->userAgent());
        $now = now();

        if ($existing) {
            $existing->update([
                'ip_address' => $request->ip(),
                'last_seen_at' => $now,
            ]);

            return $existing;
        }

        $device = TrustedDevice::create([
            'user_id' => $user->id,
            'fingerprint' => $fingerprint,
            'platform' => $parsed['platform'],
            'browser' => $parsed['browser'],
            'device_type' => $parsed['device_type'],
            'ip_address' => $request->ip(),
            'trusted' => false,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);

        if (config('auth.auth-policy.devices.alert_new_device', true)) {
            NewDeviceDetected::dispatch($user, $device);
        }

        return $device;
    }

    /**
     * Mark a device as trusted for the configured duration.
     */
    public function trust(TrustedDevice $device, ?string $label = null): TrustedDevice
    {
        $days = (int) config('auth.auth-policy.devices.trust_duration_days', 30);

        $device->update([
            'trusted' => true,
            'label' => $label ?? $device->label,
            'trusted_until' => $days > 0 ? now()->addDays($days) : null,
        ]);

        return $device;
    }

    public function revoke(TrustedDevice $device): void
    {
        $device->update([
            'trusted' => false,
            'trusted_until' => null,
        ]);
    }

    /**
     * Minimal user-agent parser (platform + browser + type).
     *
     * @return array{platform: ?string, browser: ?string, device_type: ?string}
     */
    private function parseUserAgent(?string $ua): array
    {
        if (! $ua) {
            return ['platform' => null, 'browser' => null, 'device_type' => null];
        }

        $platform = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            default => null,
        };

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => null,
        };

        $type = match (true) {
            str_contains($ua, 'Mobile'), str_contains($ua, 'iPhone'), str_contains($ua, 'Android') => 'mobile',
            str_contains($ua, 'Tablet'), str_contains($ua, 'iPad') => 'tablet',
            default => 'desktop',
        };

        return ['platform' => $platform, 'browser' => $browser, 'device_type' => $type];
    }
}
