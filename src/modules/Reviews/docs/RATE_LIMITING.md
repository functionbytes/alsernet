# API Rate Limiting Implementation

## Overview

The Reviews API implements comprehensive rate limiting with custom headers, standardized response formats, and role-based limits following RFC 6585 best practices.

## Features Implemented

### 1. Custom Middleware (`AddApiHeaders`)

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/app/Http/Middleware/AddApiHeaders.php`

**Adds the following headers to all API responses**:
- `X-API-Version: 1.0` - API version identifier
- `X-Request-ID: {UUID}` - Unique request tracking ID
- `X-RateLimit-Reset: {timestamp}` - Unix timestamp when rate limit resets
- `Cache-Control: no-cache, no-store, must-revalidate` - Prevent caching

### 2. Exception Handler (`HandleApiExceptions`)

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/app/Http/Middleware/HandleApiExceptions.php`

**Handles**:
- `ThrottleRequestsException` - Rate limit exceeded (429)
- `HttpException` - HTTP errors (400, 403, 404, etc.)
- General exceptions - Unexpected errors (500)

**All exceptions include**:
- Request ID for tracking
- Standardized JSON error format
- Logging for debugging

### 3. Role-Based Rate Limiters

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/app/Providers/ReviewsServiceProvider.php`

**Rate Limits**:

| User Type | Limit | Identification |
|-----------|-------|----------------|
| Admin (super-admin, administrative, manager) | 1000 requests/hour | User ID |
| Authenticated Users | 100 requests/hour | User ID |
| Guest/Unauthenticated | 20 requests/hour | IP Address |

**Named Rate Limiters**:
- `reviews:admin` - For admin users
- `reviews:user` - For regular authenticated users
- `reviews:guest` - For unauthenticated requests
- `reviews:api` - Combined limiter that checks role automatically

### 4. API Response Wrapper

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/app/Http/Resources/ApiResponse.php`

**Methods**:

```php
ApiResponse::success($data, $message, $statusCode, $meta, $links)
ApiResponse::error($message, $statusCode, $errors, $requestId)
ApiResponse::paginated($collection, $message, $additionalMeta)
ApiResponse::resource($resource, $message, $statusCode)
```

**Standard Success Response**:
```json
{
    "success": true,
    "message": "Success message",
    "data": { ... },
    "meta": {
        "pagination": {
            "total": 100,
            "count": 20,
            "per_page": 20,
            "current_page": 1,
            "total_pages": 5,
            "has_more_pages": true
        }
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": "...",
        "next": "..."
    }
}
```

**Standard Error Response**:
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field_name": ["Error details"]
    },
    "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### 5. Rate Limit Exceeded Response (429)

**Headers**:
```
HTTP/1.1 429 Too Many Requests
Retry-After: 3600
X-RateLimit-Reset: 1709299200
X-Request-ID: 550e8400-e29b-41d4-a716-446655440000
```

**Body**:
```json
{
    "success": false,
    "message": "Too many requests. Please slow down.",
    "errors": {
        "rate_limit": "You have exceeded the rate limit. Please try again later."
    },
    "request_id": "550e8400-e29b-41d4-a716-446655440000",
    "retry_after": 3600
}
```

### 6. Updated API Controllers

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/app/Http/Controllers/Api/ReviewController.php`

**All endpoints now**:
- Use `ApiResponse` wrapper for consistent format
- Log requests with request ID
- Return `JsonResponse` instead of direct resources
- Include user ID and filters in logs

**Endpoints**:
- `GET /api/reviews` - List reviews (paginated)
- `GET /api/reviews/stats` - Get statistics
- `GET /api/reviews/{review}` - Get single review
- `GET /api/reviews/{review}/suggestions` - Get reply suggestions

### 7. Updated API Routes

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/routes/api.php`

**Middleware Stack**:
```php
['api', 'auth:sanctum', 'throttle:reviews:api', AddApiHeaders::class, HandleApiExceptions::class]
```

**Features**:
- Comprehensive documentation in comments
- Rate limit information
- Header documentation
- Error response documentation

### 8. Comprehensive Documentation

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/docs/API.md`

**Updated sections**:
- Rate limiting details with role-based limits
- Standard response format examples
- Error response format examples
- Headers documentation
- Code examples with retry logic
- Best practices

## Testing

### Manual Testing

**Test rate limit headers**:
```bash
curl -v https://domain.com/api/reviews \
  -H "Authorization: Bearer {token}"
```

**Expected headers in response**:
```
X-API-Version: 1.0
X-Request-ID: 550e8400-e29b-41d4-a716-446655440000
X-RateLimit-Reset: 1709299200
Cache-Control: no-cache, no-store, must-revalidate
```

**Test rate limit exceeded**:
```bash
# Make 101 requests as regular user
for i in {1..101}; do
  curl https://domain.com/api/reviews \
    -H "Authorization: Bearer {token}"
done
```

**Expected 429 response after 100 requests**:
```json
{
    "success": false,
    "message": "Too many requests. Please slow down.",
    "errors": {
        "rate_limit": "You have exceeded the rate limit. Please try again later."
    },
    "request_id": "...",
    "retry_after": 3600
}
```

### Automated Testing

**Location**: `/Users/developert/Herd/inoqualab/modules/Reviews/tests/Feature/ApiRateLimitingTest.php`

**Tests**:
- ✓ API responses include version header
- ✓ API responses include request ID header
- ✓ API responses include cache control headers
- ✓ API responses include rate limit headers
- ✓ Rate limit applies to admin users
- ✓ Rate limit applies to regular users
- ✓ Rate limit exceeded returns 429
- ✓ API response wrapper returns consistent format
- ✓ Single review endpoint uses API response wrapper
- ✓ Stats endpoint uses API response wrapper
- ✓ Suggestions endpoint uses API response wrapper
- ✓ Error responses include request ID
- ✓ Unauthorized request returns consistent error

## Architecture

### Request Flow

```
Client Request
    ↓
API Middleware Group (api)
    ↓
Sanctum Authentication (auth:sanctum)
    ↓
Rate Limiter (throttle:reviews:api)
    ├─ Checks user role
    ├─ Applies appropriate limit
    └─ Throws ThrottleRequestsException if exceeded
    ↓
AddApiHeaders Middleware
    ├─ Adds X-API-Version
    ├─ Adds X-Request-ID
    ├─ Adds Cache-Control
    └─ Adds X-RateLimit-Reset
    ↓
HandleApiExceptions Middleware
    ├─ Catches ThrottleRequestsException
    ├─ Catches HttpException
    ├─ Catches general exceptions
    └─ Returns standardized error format
    ↓
Controller
    ├─ Executes business logic
    ├─ Logs request with ID
    └─ Returns ApiResponse
    ↓
Client Response
```

### Rate Limiter Decision Tree

```
Is user authenticated?
├─ No → Apply guest limiter (20/hour by IP)
└─ Yes → Check role
    ├─ Has admin role? → Apply admin limiter (1000/hour by User ID)
    └─ Regular user → Apply user limiter (100/hour by User ID)
```

## Configuration

### Changing Rate Limits

**Edit**: `/Users/developert/Herd/inoqualab/modules/Reviews/app/Providers/ReviewsServiceProvider.php`

```php
protected function registerRateLimiters(): void
{
    // Admin: change 1000 to desired limit
    RateLimiter::for('reviews:admin', function (Request $request) {
        if ($request->user()?->hasRole('super-admin|administrative|manager')) {
            return Limit::perHour(1000)->by($request->user()->id);
        }
        return Limit::none();
    });

    // User: change 100 to desired limit
    RateLimiter::for('reviews:user', function (Request $request) {
        if ($request->user()) {
            return Limit::perHour(100)->by($request->user()->id);
        }
        return Limit::none();
    });

    // Guest: change 20 to desired limit
    RateLimiter::for('reviews:guest', function (Request $request) {
        return Limit::perHour(20)->by($request->ip());
    });
}
```

### Adding Custom Roles

To add a new role tier (e.g., "premium" with 500/hour):

```php
RateLimiter::for('reviews:api', function (Request $request) {
    if (! $request->user()) {
        return Limit::perHour(20)->by($request->ip());
    }

    if ($request->user()->hasRole('super-admin|administrative|manager')) {
        return Limit::perHour(1000)->by($request->user()->id);
    }

    // Add new premium tier
    if ($request->user()->hasRole('premium')) {
        return Limit::perHour(500)->by($request->user()->id);
    }

    return Limit::perHour(100)->by($request->user()->id);
});
```

## Monitoring

### Log Files

All API requests are logged to `storage/logs/laravel.log` with:
- Request ID
- User ID (if authenticated)
- Endpoint accessed
- Filters/parameters used
- Response status
- Timestamp

### Rate Limit Violations

Rate limit violations are logged as warnings:
```
[warning] API rate limit exceeded
    request_id: 550e8400-e29b-41d4-a716-446655440000
    user_id: 123
    ip: 192.168.1.100
    path: api/reviews
    retry_after: 3600
```

### Querying Logs

**Find rate limit violations**:
```bash
grep "API rate limit exceeded" storage/logs/laravel.log
```

**Find requests by request ID**:
```bash
grep "550e8400-e29b-41d4-a716-446655440000" storage/logs/laravel.log
```

## Best Practices

### For API Consumers

1. **Monitor rate limit headers**: Check `X-RateLimit-Remaining` before making many requests
2. **Implement exponential backoff**: When receiving 429, wait `retry_after` seconds before retrying
3. **Cache responses**: Reduce API calls by caching frequently accessed data
4. **Include request IDs**: When reporting issues, include `X-Request-ID` from response
5. **Handle errors gracefully**: Always check `success` field in responses

### For Developers

1. **Always use ApiResponse wrapper**: Ensures consistent response format
2. **Log with request ID**: Include request ID in all logs for traceability
3. **Test rate limits**: Verify rate limits work for all user types
4. **Document new endpoints**: Update API.md with rate limit info
5. **Monitor logs**: Watch for rate limit violations and adjust limits if needed

## Troubleshooting

### Rate limit not working

**Check rate limiter registration**:
```bash
php artisan tinker --execute="
\$route = app('router')->getRoutes()->getByName('api.reviews.index');
echo implode(', ', \$route->middleware());
"
```

**Expected output**: Should include `throttle:reviews:api`

### Headers not appearing

**Check middleware registration**:
```bash
php artisan route:list --path=api/reviews
```

**Verify middleware stack includes**:
- `Modules\Reviews\Http\Middleware\AddApiHeaders`
- `Modules\Reviews\Http\Middleware\HandleApiExceptions`

### Rate limit too strict/lenient

**Adjust limits in**: `ReviewsServiceProvider::registerRateLimiters()`

**Clear rate limiter cache**:
```bash
php artisan cache:clear
```

## Security Considerations

1. **IP-based limiting for guests**: Prevents abuse from unauthenticated requests
2. **User-based limiting**: Prevents single user from overwhelming API
3. **Role-based tiers**: Allows trusted users (admins) higher limits
4. **Request ID logging**: Enables tracking of abuse patterns
5. **Exponential backoff encouragement**: Documentation guides proper retry behavior

## Performance Impact

- **Minimal overhead**: Rate limiting check is O(1) operation
- **Redis recommended**: For better performance, use Redis cache driver
- **Logging is async**: Log writes don't block responses
- **Middleware order optimized**: Rate limiting happens early to reject bad requests fast

## Future Enhancements

1. **Rate limit by endpoint**: Different limits for different operations
2. **Dynamic limits**: Adjust based on system load
3. **Rate limit dashboard**: Visual monitoring of API usage
4. **Webhooks**: Notify admins of excessive rate limit violations
5. **Allowlisting**: Bypass rate limits for trusted IPs/users
6. **Token bucket algorithm**: More sophisticated rate limiting strategy

## References

- [RFC 6585 - Additional HTTP Status Codes](https://tools.ietf.org/html/rfc6585)
- [Laravel Rate Limiting Documentation](https://laravel.com/docs/12.x/routing#rate-limiting)
- [REST API Best Practices](https://restfulapi.net/)
- [HTTP Status Code Registry](https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml)
