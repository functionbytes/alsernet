# Guía de Desarrollo - Reviews Module

Información para desarrolladores que quieran extender o contribuir al módulo.

## Estructura del Módulo

```
modules/Reviews/
├── app/
│   ├── Console/Commands/
│   │   ├── CleanupExpiredConnectionsCommand.php
│   │   ├── GenerateReportCommand.php
│   │   ├── InstallReviewsCommand.php
│   │   ├── PruneOldReviewsCommand.php
│   │   └── SyncReviewsCommand.php
│   ├── Enums/
│   │   ├── ConnectionStatus.php        # pending, active, expired, revoked, error
│   │   ├── ReplyStatus.php             # draft, approved, published, rejected
│   │   └── ReviewRating.php            # ONE, TWO, THREE, FOUR, FIVE
│   ├── Events/
│   │   ├── ReviewReplied.php
│   │   └── ReviewSynced.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── ReviewController.php
│   │   │   ├── ReviewController.php
│   │   │   ├── ReviewReplyController.php
│   │   │   ├── ReviewTemplateController.php
│   │   │   └── Settings/
│   │   │       ├── GoogleConnectionController.php
│   │   │       ├── GoogleLocationController.php
│   │   │       └── ReviewSettingsController.php
│   │   └── Requests/
│   │       ├── StoreReplyTemplateRequest.php
│   │       ├── StoreReviewReplyRequest.php
│   │       ├── UpdateLocationRequest.php
│   │       └── UpdateModerationRequest.php
│   ├── Jobs/
│   │   ├── DeleteReviewReplyJob.php
│   │   ├── PublishReviewReplyJob.php
│   │   ├── SyncGoogleLocationsJob.php
│   │   └── SyncGoogleReviewsJob.php
│   ├── Models/
│   │   ├── Review.php
│   │   ├── ReviewGoogleConnection.php
│   │   ├── ReviewGoogleLocation.php
│   │   ├── ReviewModeration.php
│   │   ├── ReviewReply.php
│   │   └── ReviewReplyTemplate.php
│   ├── Policies/
│   │   ├── ReviewGoogleConnectionPolicy.php
│   │   ├── ReviewPolicy.php
│   │   ├── ReviewReplyPolicy.php
│   │   └── ReviewReplyTemplatePolicy.php
│   ├── Services/
│   │   ├── GoogleAccountService.php
│   │   ├── GoogleAuthService.php
│   │   ├── GoogleLocationService.php
│   │   ├── GoogleReviewService.php
│   │   ├── ReviewExportService.php
│   │   ├── ReviewModerationService.php
│   │   └── ReviewReplyService.php
│   └── Providers/
│       ├── ReviewsServiceProvider.php
│       └── RouteServiceProvider.php
├── config/
│   ├── general.php
│   ├── google.php
│   └── permissions.php
├── database/
│   ├── factories/
│   │   ├── ReviewFactory.php
│   │   ├── ReviewGoogleConnectionFactory.php
│   │   ├── ReviewGoogleLocationFactory.php
│   │   ├── ReviewModerationFactory.php
│   │   ├── ReviewReplyFactory.php
│   │   └── ReviewReplyTemplateFactory.php
│   ├── migrations/
│   └── seeders/
│       ├── ReviewsDatabaseSeeder.php
│       ├── ReviewsPermissionSeeder.php
│       └── ReviewsTestDataSeeder.php
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php
│   ├── reviews/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── settings/
│   │   ├── connections/
│   │   │   ├── create.blade.php
│   │   │   └── index.blade.php
│   │   ├── locations/
│   │   │   └── index.blade.php
│   │   └── config.blade.php
│   └── components/
│       └── (componentes reutilizables)
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/
│   │   ├── Api/
│   │   │   └── ReviewApiTest.php
│   │   ├── GoogleConnectionTest.php
│   │   ├── GoogleLocationTest.php
│   │   ├── ReviewModerationTest.php
│   │   ├── ReviewReplyTest.php
│   │   ├── ReviewSyncTest.php
│   │   └── ReviewTemplateTest.php
│   └── Unit/
│       ├── Enums/
│       ├── Models/
│       └── Services/
└── README.md
```

## Patrones de Código

### 1. Servicios

Los servicios encapsulan la lógica de negocio. Patrón:

```php
<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Models\Review;

class ReviewModerationService
{
    public function updateModeration(Review $review, array $data, User $user): ReviewModeration
    {
        // Validar permisos
        $user->authorize('moderate', $review);

        // Actualizar
        $moderation = $review->moderation ?? ReviewModeration::create([
            'review_id' => $review->id,
        ]);

        $moderation->update([
            'is_visible' => $data['is_visible'] ?? $moderation->is_visible,
            'is_featured' => $data['is_featured'] ?? $moderation->is_featured,
        ]);

        // Log
        activity()
            ->performedOn($review)
            ->causedBy($user)
            ->log('Moderation updated');

        return $moderation;
    }
}
```

Inyectar en controladores:

```php
public function __construct(
    private readonly ReviewModerationService $service
) {}

public function moderate(Request $request, Review $review): JsonResponse
{
    $moderation = $this->service->updateModeration(
        $review,
        $request->validated(),
        auth()->user()
    );

    return response()->json(['moderation' => $moderation]);
}
```

### 2. Models

Usar relaciones con type hints:

```php
<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    public function location(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleLocation::class, 'location_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReviewReply::class, 'review_id');
    }

    // Scopes para queries comunes
    public function scopeVisible($query)
    {
        return $query->whereHas('moderation', function ($q) {
            $q->where('is_visible', true);
        });
    }

    // Métodos helper
    public function isVisible(): bool
    {
        return $this->moderation?->is_visible ?? true;
    }
}
```

### 3. Policies

Usar policies para autorización:

```php
<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\Review;

class ReviewPolicy
{
    public function view(User $user, Review $review): bool
    {
        return $user->can('reviews.reviews.view');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->can('reviews.reviews.moderate');
    }
}
```

Usar en controlador:

```php
public function __construct()
{
    $this->authorizeResource(Review::class, 'review');
}

public function show(Review $review): View
{
    // Autorización automática
    return view('reviews::reviews.show', compact('review'));
}
```

### 4. Form Requests

Validación centralizada:

```php
<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.moderate');
    }

    public function rules(): array
    {
        return [
            'is_visible' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'is_visible.boolean' => 'Visibilidad debe ser true o false',
        ];
    }
}
```

### 5. Jobs

Para tareas asincrónicas:

```php
<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\GoogleReviewService;

class SyncGoogleReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public ReviewGoogleLocation $location
    ) {
        $this->onQueue('google-sync');
    }

    public function handle(GoogleReviewService $service): void
    {
        $count = $service->syncReviews($this->location);
        Log::info("Synced {$count} reviews");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Sync failed', ['error' => $exception->getMessage()]);
        // Notificar admin, etc
    }
}
```

Despachar:

```php
// Inmediato
SyncGoogleReviewsJob::dispatch($location);

// Diferido
SyncGoogleReviewsJob::dispatch($location)->delay(now()->addHours(1));

// Con prioridad
SyncGoogleReviewsJob::dispatch($location)->onQueue('high-priority');
```

## Agregar Nuevas Características

### Agregar Nuevo Filtro a Reviews

1. Agregar scope al modelo:

```php
// app/Models/Review.php
public function scopeHasTag($query, string $tag)
{
    return $query->whereHas('moderation', function ($q) use ($tag) {
        $q->where('tags', 'like', "%{$tag}%");
    });
}
```

2. Usar en controlador:

```php
// app/Http/Controllers/ReviewController.php
public function data(Request $request): JsonResponse
{
    $query = Review::query();

    if ($request->filled('tag')) {
        $query->hasTag($request->input('tag'));
    }

    // ...
}
```

### Agregar Nuevo Servicio

1. Crear archivo en `app/Services/`:

```php
<?php

namespace Modules\Reviews\Services;

class ReviewNotificationService
{
    public function notifyNewReview(Review $review): void
    {
        // Enviar notificación
        Notification::send($review->location->users, new NewReviewNotification($review));
    }
}
```

2. Registrar en Service Provider (si necesita inyección):

```php
// app/Providers/ReviewsServiceProvider.php
public function register(): void
{
    $this->app->singleton(ReviewNotificationService::class);
}
```

3. Usar en controladores/eventos:

```php
class ReviewSynced
{
    public function handle(ReviewNotificationService $service): void
    {
        $service->notifyNewReview($this->review);
    }
}
```

### Agregar Nuevo Job

1. Crear archivo en `app/Jobs/`:

```php
<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Reviews\Models\Review;

class SendReviewNotificationJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Review $review
    ) {}

    public function handle(): void
    {
        // Enviar notificación
    }
}
```

2. Despachar desde evento/controlador:

```php
event(new ReviewSynced($review));

// En listener
public function handle(ReviewSynced $event): void
{
    SendReviewNotificationJob::dispatch($event->review);
}
```

### Agregar Nuevo Comando Artisan

1. Crear archivo en `app/Console/Commands/`:

```php
<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;

class GenerateReviewSummaryCommand extends Command
{
    protected $signature = 'reviews:summary {--location=}';
    protected $description = 'Generar resumen de reseñas';

    public function handle(): int
    {
        $locationId = $this->option('location');

        $this->info('Generando resumen...');
        // Lógica

        return self::SUCCESS;
    }
}
```

2. Registrar en Service Provider:

```php
// app/Providers/ReviewsServiceProvider.php
protected function registerCommands(): void
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            \Modules\Reviews\Console\Commands\GenerateReviewSummaryCommand::class,
        ]);
    }
}
```

### Agregar Permiso Nuevo

1. Agregar a `config/permissions.php`:

```php
'permissions' => [
    // ... existentes
    'reviews.reviews.bulk-moderate' => 'Moderate reviews in bulk',
];
```

2. Usar en policy:

```php
public function bulkModerate(User $user): bool
{
    return $user->can('reviews.reviews.bulk-moderate');
}
```

## Testing

### Escribir Tests Unitarios

```php
<?php

namespace Modules\Reviews\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Modules\Reviews\Services\ReviewModerationService;
use Modules\Reviews\Models\Review;

class ReviewModerationServiceTest extends TestCase
{
    private ReviewModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReviewModerationService();
    }

    public function test_update_moderation_hides_review(): void
    {
        $review = Review::factory()->create();
        $user = User::factory()->create();

        $moderation = $this->service->updateModeration($review, [
            'is_visible' => false,
        ], $user);

        $this->assertFalse($moderation->is_visible);
    }
}
```

### Escribir Tests Funcionales

```php
<?php

namespace Modules\Reviews\Tests\Feature;

use Tests\TestCase;
use Modules\Reviews\Models\Review;

class ReviewModerationTest extends TestCase
{
    public function test_moderate_review_as_moderator(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reviews.moderate');

        $review = Review::factory()->create();

        $response = $this->actingAs($user)->patch(
            route('reviews.moderate', $review),
            ['is_visible' => false]
        );

        $response->assertOk();
        $this->assertFalse($review->refresh()->isVisible());
    }

    public function test_cannot_moderate_without_permission(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $response = $this->actingAs($user)->patch(
            route('reviews.moderate', $review),
            ['is_visible' => false]
        );

        $response->assertForbidden();
    }
}
```

### Fixtures y Factories

Usar factories para generar datos:

```php
$review = Review::factory()
    ->for(ReviewGoogleLocation::factory())
    ->create([
        'star_rating' => ReviewRating::FIVE,
        'comment' => 'Excelente',
    ]);

$reviews = Review::factory()
    ->count(10)
    ->withModeration()
    ->create();
```

## Debugging

### Usar Tinker para Explorar

```bash
php artisan tinker
> $review = Review::first()
> $review->location
> $review->moderation
> $review->replies
> auth()->user()
```

### Logging

```php
use Illuminate\Support\Facades\Log;

Log::info('Info message', ['key' => 'value']);
Log::warning('Warning');
Log::error('Error');
Log::debug('Debug info');
```

Ver logs:

```bash
tail -f storage/logs/laravel.log
```

### Activity Log

Ver auditoría de cambios:

```bash
php artisan tinker
> activity()->all()
> \Spatie\Activitylog\Models\Activity::where('subject_type', 'Modules\Reviews\Models\Review')->get()
```

## Mejoras Futuras

- [ ] WebSocket notifications para sincronización en tiempo real
- [ ] Bulk operations (moderar múltiples reseñas)
- [ ] Templates avanzadas con lógica condicional
- [ ] Integración con Slack/Teams para notificaciones
- [ ] Analytics dashboard (gráficos de tendencias)
- [ ] API webhook para eventos
- [ ] Sincronización con múltiples plataformas (Yelp, Facebook, etc)

## Contribución

1. Fork del repositorio
2. Branch feature: `git checkout -b feature/nueva-funcionalidad`
3. Commit cambios: `git commit -am 'Add new feature'`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Pull Request

Asegurar que:

- Todos los tests pasan: `php artisan test modules/Reviews/tests`
- Código formateado: `vendor/bin/pint`
- No hay linting errors

## Referencias

- [Laravel Documentation](https://laravel.com/docs)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Policies](https://laravel.com/docs/authorization#creating-policies)
- [Jobs and Queues](https://laravel.com/docs/queues)
- [Spatie Activity Log](https://spatie.be/docs/laravel-activity-log)
- [Google API Documentation](https://developers.google.com/my-business)
