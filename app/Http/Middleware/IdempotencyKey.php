<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey as IdempotencyKeyModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return $next($request);
        }

        $endpoint = $request->path();

        $existing = IdempotencyKeyModel::query()
            ->where('key', $key)
            ->where('endpoint', $endpoint)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return response()->json($existing->response, $existing->status_code ?: 200);
        }

        $response = $next($request);

        if ($response->getStatusCode() < 300) {
            try {
                IdempotencyKeyModel::query()->create([
                    'key' => $key,
                    'endpoint' => $endpoint,
                    'response' => json_decode($response->getContent(), true) ?: [],
                    'status_code' => $response->getStatusCode(),
                    'expires_at' => now()->addHours(24),
                ]);
            } catch (\Throwable $e) {
                $existing = IdempotencyKeyModel::query()
                    ->where('key', $key)
                    ->where('endpoint', $endpoint)
                    ->first();
                if ($existing) {
                    return response()->json($existing->response, $existing->status_code ?: 200);
                }
            }
        }

        return $response;
    }
}
