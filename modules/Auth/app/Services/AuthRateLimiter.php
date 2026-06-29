<?php

namespace Modules\Auth\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate limiter keyed by (identifier + IP) to mitigate distributed bypass.
 * Backed by Laravel's RateLimiter facade (Redis when cache driver is redis).
 */
class AuthRateLimiter
{
    /**
     * @return array{allowed: bool, seconds: int}
     */
    public function check(string $scope, string $identifier, Request $request): array
    {
        $config = config("auth.auth-policy.rate_limits.{$scope}");
        $maxAttempts = (int) ($config['max_attempts'] ?? 5);
        $key = $this->key($scope, $identifier, $request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return ['allowed' => false, 'seconds' => RateLimiter::availableIn($key)];
        }

        return ['allowed' => true, 'seconds' => 0];
    }

    public function hit(string $scope, string $identifier, Request $request): int
    {
        $config = config("auth.auth-policy.rate_limits.{$scope}");
        $decay = (int) ($config['decay_seconds'] ?? 60);

        return RateLimiter::hit($this->key($scope, $identifier, $request), $decay);
    }

    public function clear(string $scope, string $identifier, Request $request): void
    {
        RateLimiter::clear($this->key($scope, $identifier, $request));
    }

    public function attempts(string $scope, string $identifier, Request $request): int
    {
        return RateLimiter::attempts($this->key($scope, $identifier, $request));
    }

    private function key(string $scope, string $identifier, Request $request): string
    {
        return 'auth:'.$scope.':'.sha1($identifier.'|'.$request->ip());
    }
}
