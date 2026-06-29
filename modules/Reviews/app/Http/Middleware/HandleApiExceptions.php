<?php

namespace Modules\Reviews\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandleApiExceptions
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ThrottleRequestsException $e) {
            return $this->handleThrottleException($e, $request);
        } catch (HttpException $e) {
            return $this->handleHttpException($e, $request);
        } catch (\Throwable $e) {
            return $this->handleGeneralException($e, $request);
        }
    }

    protected function handleThrottleException(
        ThrottleRequestsException $e,
        Request $request
    ): JsonResponse {
        $requestId = Str::uuid()->toString();
        $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
        $resetTime = now()->addSeconds($retryAfter)->timestamp;

        Log::warning('API rate limit exceeded', [
            'request_id' => $requestId,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'path' => $request->path(),
            'retry_after' => $retryAfter,
        ]);

        $response = response()->json([
            'success' => false,
            'message' => 'Too many requests. Please slow down.',
            'errors' => [
                'rate_limit' => 'You have exceeded the rate limit. Please try again later.',
            ],
            'request_id' => $requestId,
            'retry_after' => $retryAfter,
        ], 429);

        // Add rate limit headers
        $response->headers->set('Retry-After', (string) $retryAfter);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    protected function handleHttpException(HttpException $e, Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        Log::warning('API HTTP exception', [
            'request_id' => $requestId,
            'status_code' => $e->getStatusCode(),
            'message' => $e->getMessage(),
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage() ?: 'An error occurred',
            'request_id' => $requestId,
        ], $e->getStatusCode());
    }

    protected function handleGeneralException(\Throwable $e, Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        Log::error('API general exception', [
            'request_id' => $requestId,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => config('app.debug')
                ? $e->getMessage()
                : 'An unexpected error occurred. Please try again.',
            'request_id' => $requestId,
        ], 500);
    }
}
