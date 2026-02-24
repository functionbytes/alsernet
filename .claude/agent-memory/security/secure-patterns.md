# Secure Coding Patterns - Quick Reference

## OAuth 2.0 Flow

### State Parameter (Anti-CSRF)
```php
// VULNERABLE
$state = session('oauth_state');
if ($request->input('state') !== $state) {
    throw new \Exception('Invalid state');
}

// SECURE
$sessionState = session()->pull('oauth_state'); // Invalida tras uso
if (!hash_equals($sessionState ?? '', $request->input('state') ?? '')) {
    abort(403, 'Invalid OAuth state - possible CSRF attack');
}
```

### Redirect URI Validation
```php
// VULNERABLE
'redirect_uri' => env('APP_URL').'/callback',

// SECURE
'redirect_uri' => rtrim(config('app.url'), '/').'/oauth/callback',

// En callback controller
if (!Str::startsWith($request->fullUrl(), config('oauth.redirect_uri'))) {
    abort(403, 'Invalid redirect URI');
}
```

### Token Storage
```php
// Model - ReviewGoogleConnection
protected function casts(): array
{
    return [
        'access_token' => 'encrypted',  // CRÍTICO
        'refresh_token' => 'encrypted', // CRÍTICO
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
    ];
}
```

## Authorization (Policies)

### IDOR Prevention
```php
// VULNERABLE - Solo verifica permiso genérico
public function view(User $user, Connection $connection): bool
{
    return $user->can('connections.view');
}

// SECURE - Verifica ownership
public function view(User $user, Connection $connection): bool
{
    return $user->can('connections.view')
        && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
}

// SECURE - Scope query automático
public function viewAny(User $user): bool
{
    return $user->can('connections.view');
}

// En Controller
public function index(Request $request)
{
    $connections = Connection::query()
        ->where('user_id', auth()->id())
        ->orWhereHas('user', fn($q) => $q->whereHas('roles', fn($q2) => $q2->where('name', 'super-admin')))
        ->paginate(15);
}
```

## HTTP Client Security

### Guzzle Configuration
```php
// VULNERABLE
$client = new GuzzleClient;
$response = $client->post($url, ['json' => $data]);

// SECURE
private function createSecureClient(int $timeout = 30): GuzzleClient
{
    return new GuzzleClient([
        'timeout' => $timeout,
        'connect_timeout' => 10,
        'verify' => true, // CRÍTICO: Forzar SSL verification
        'http_errors' => true,
        'headers' => [
            'User-Agent' => config('app.name').'/1.0',
            'Accept' => 'application/json',
        ],
    ]);
}

// Uso
$client = $this->createSecureClient();
$response = $client->post($url, [
    'json' => $data,
    'headers' => ['Authorization' => 'Bearer '.$token],
]);
```

## SQL Injection Prevention

### Avoid DB::raw with User Input
```php
// VULNERABLE
$rating = Review::query()->avg(DB::raw('CAST(star_rating AS UNSIGNED)'));

// SECURE - Opción 1: Sin DB::raw
$reviews = Review::query()->get(['star_rating']);
$rating = $reviews->avg(fn($r) => $r->star_rating->value());

// SECURE - Opción 2: Bindings
Review::query()->selectRaw('AVG(CASE
    WHEN star_rating = ? THEN 5
    WHEN star_rating = ? THEN 4
    ELSE 0 END) as avg', ['FIVE', 'FOUR'])->value('avg');
```

### Query Scopes
```php
// VULNERABLE
$query->where('location_id', $request->input('location_id'));

// SECURE
if ($request->filled('location_id')) {
    $locationId = $request->integer('location_id');

    // Verificar ownership
    $location = Location::query()
        ->whereHas('connection', fn($q) => $q->where('user_id', auth()->id()))
        ->findOrFail($locationId);

    $query->where('location_id', $locationId);
}
```

## XSS Prevention

### Blade Templates
```blade
{{-- VULNERABLE --}}
{!! $userInput !!}

{{-- SECURE - Escapar automáticamente --}}
{{ $userInput }}

{{-- SECURE - Con HTML Purifier --}}
{!! clean($userInput) !!}
```

### JavaScript/DataTables
```javascript
// VULNERABLE
{ data: 'comment', render: function(data) {
    return data;
}}

// SECURE - Escapar HTML
{ data: 'comment', render: function(data) {
    if (!data) return '<em class="text-muted">Sin comentario</em>';
    const escaped = $('<div/>').text(data).html();
    return escaped.length > 100 ? escaped.substring(0, 100) + '...' : escaped;
}}
```

### API Resources
```php
// ReviewResource
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'comment' => $this->comment, // Laravel escapa automáticamente en JSON
        'internalNotes' => $this->when(
            $request->user()?->can('moderate', $this->resource),
            $this->internal_notes // Solo exponer si tiene permisos
        ),
    ];
}
```

## CSRF Protection

### Forms
```blade
<form method="POST" action="{{ route('connections.store') }}">
    @csrf
    <!-- fields -->
</form>
```

### AJAX
```javascript
$.ajax({
    url: '/api/endpoint',
    method: 'POST',
    data: { field: value },
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function(response) {
        toastr.success('Success');
    }
});
```

## Path Traversal Prevention

### File Operations
```php
// VULNERABLE
$filename = $request->input('filename');
$path = storage_path('app/exports/'.$filename);

// SECURE
$filename = $request->validated('filename');
$filename = basename($filename); // Remove path components
$filename = Str::slug($filename, '_'); // Sanitize
$path = storage_path('app/exports/'.$filename);

// Validar path final
if (!Str::startsWith(realpath($path), realpath(storage_path('app/exports/')))) {
    throw new \Exception('Invalid file path');
}
```

## Rate Limiting

### Routes
```php
// VULNERABLE
Route::get('/oauth/callback', [Controller::class, 'callback']);

// SECURE - Global
Route::middleware(['web', 'auth', 'throttle:60,1'])
    ->get('/oauth/callback', [Controller::class, 'callback']);

// SECURE - Custom per-user
Route::middleware(['api', 'auth:sanctum', 'throttle:10,1'])
    ->post('/reviews/export', [Controller::class, 'export']);
```

### Controller Level
```php
public function __construct()
{
    $this->middleware('throttle:10,1')->only(['login', 'register']);
}
```

## Logging Security

### Activity Logs
```php
// VULNERABLE - Puede exponer tokens
activity()
    ->performedOn($connection)
    ->log('Token refresh failed: '.$e->getMessage());

// SECURE - Mensaje sanitizado
activity()
    ->performedOn($connection)
    ->log('Token refresh failed');

Log::error('OAuth error', [
    'connection_id' => $connection->id,
    'error_code' => $e->getCode(),
    // NO incluir $e->getMessage() que puede contener tokens
]);
```

### Exception Handling
```php
// VULNERABLE - Stack trace en producción
catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 500);
}

// SECURE
catch (\Exception $e) {
    Log::error('Operation failed', [
        'user_id' => auth()->id(),
        'exception' => get_class($e),
        'code' => $e->getCode(),
    ]);

    return response()->json([
        'error' => app()->environment('production')
            ? 'An error occurred. Please try again.'
            : $e->getMessage()
    ], 500);
}
```

## Input Validation

### Form Requests
```php
class StoreConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('connections.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'min:3'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'location_ids' => ['sometimes', 'array', 'max:10'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'tags.*.regex' => 'Las etiquetas solo pueden contener letras, números, guiones y guiones bajos',
        ];
    }
}
```

## Mass Assignment Protection

### Models
```php
// VULNERABLE
protected $guarded = [];

// SECURE
protected $fillable = [
    'user_id',
    'name',
    'email',
    'status',
];

// CRÍTICO: NUNCA incluir en $fillable
// - 'is_admin'
// - 'role'
// - 'permissions'
// - Cualquier campo que controle acceso
```

## Session Security

### Configuration
```php
// config/session.php
return [
    'driver' => 'redis', // En producción
    'lifetime' => 120,
    'expire_on_close' => true,
    'secure' => env('SESSION_SECURE_COOKIE', true), // Solo HTTPS
    'http_only' => true, // No accesible vía JavaScript
    'same_site' => 'strict', // CSRF protection
];
```

## API Security

### Sanctum Configuration
```php
// routes/api.php
Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->prefix('reviews')
    ->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('/', [ReviewController::class, 'store']);
    });

// Controller
public function index(Request $request)
{
    $this->authorize('viewAny', Review::class);

    $query = Review::query()
        ->where('user_id', auth()->id()); // CRÍTICO: Scope por usuario

    return ReviewResource::collection($query->paginate(20));
}
```

## File Upload Security

### Validation
```php
$request->validate([
    'file' => [
        'required',
        'file',
        'max:10240', // 10MB
        'mimes:pdf,jpg,png',
        'mimetypes:application/pdf,image/jpeg,image/png',
    ],
]);

$path = $request->file('file')->store('uploads', 'private');

// NUNCA guardar en public/ si contiene datos sensibles
```

## Environment Variables

### Secrets Management
```php
// VULNERABLE - Usar env() fuera de config
$apiKey = env('GOOGLE_API_KEY');

// SECURE - Siempre en config files
// config/services.php
return [
    'google' => [
        'api_key' => env('GOOGLE_API_KEY'),
    ],
];

// Uso
$apiKey = config('services.google.api_key');
```

## Security Headers

### Middleware
```php
// app/Http/Middleware/SecurityHeaders.php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);

    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

    if (app()->environment('production')) {
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    return $response;
}
```

## Testing Security

### Feature Tests
```php
public function test_user_cannot_access_other_users_connections()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $connection = ReviewGoogleConnection::factory()
        ->for($user2)
        ->create();

    $this->actingAs($user1)
        ->get(route('connections.show', $connection))
        ->assertForbidden();
}

public function test_oauth_callback_rejects_invalid_state()
{
    session(['oauth_state' => 'valid-state']);

    $this->get(route('oauth.callback', [
        'code' => 'auth-code',
        'state' => 'invalid-state',
    ]))->assertForbidden();
}
```
