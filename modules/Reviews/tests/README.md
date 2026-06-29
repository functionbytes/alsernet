# Reviews Module - Test Suite

Suite completa de tests para el módulo Reviews (Google Business Profile integration).

## Resumen

- **Total tests**: 158
- **Feature tests**: 58
- **Unit tests**: 100
- **Cobertura**: Controllers, Services, Models, Enums, API endpoints

## Estructura

```
tests/
├── TestCase.php                          # Base test case con helpers
├── Feature/
│   ├── GoogleConnectionTest.php          # OAuth, conexiones (10 tests)
│   ├── GoogleLocationTest.php            # Gestión ubicaciones (6 tests)
│   ├── ReviewSyncTest.php                # Sincronización reviews (6 tests)
│   ├── ReviewModerationTest.php          # Moderación reviews (8 tests)
│   ├── ReviewReplyTest.php               # Respuestas reviews (11 tests)
│   ├── ReviewTemplateTest.php            # Templates respuestas (8 tests)
│   └── Api/
│       └── ReviewApiTest.php             # API endpoints (9 tests)
├── Unit/
│   ├── Enums/
│   │   ├── ReviewRatingTest.php          # Enum rating (7 tests)
│   │   ├── ReplyStatusTest.php           # Enum status (4 tests)
│   │   └── ConnectionStatusTest.php      # Enum conexión (4 tests)
│   ├── Models/
│   │   ├── ReviewTest.php                # Model Review (20 tests)
│   │   ├── ReviewReplyTest.php           # Model Reply (17 tests)
│   │   └── ReviewGoogleConnectionTest.php # Model Connection (15 tests)
│   └── Services/
│       ├── GoogleAuthServiceTest.php     # OAuth service (10 tests)
│       ├── GoogleReviewServiceTest.php   # Review sync (9 tests)
│       └── ReviewReplyServiceTest.php    # Reply management (14 tests)
```

## Ejecución

### Suite completa
```bash
php artisan test modules/Reviews/tests
```

### Por tipo
```bash
# Solo Feature tests
php artisan test modules/Reviews/tests/Feature

# Solo Unit tests
php artisan test modules/Reviews/tests/Unit
```

### Por categoría
```bash
# Tests de conexión OAuth
php artisan test --filter=GoogleConnection

# Tests de sincronización
php artisan test --filter=ReviewSync

# Tests de moderación
php artisan test --filter=ReviewModeration

# Tests de respuestas
php artisan test --filter=ReviewReply

# Tests de API
php artisan test modules/Reviews/tests/Feature/Api
```

### Test individual
```bash
php artisan test --filter=test_oauth_callback_stores_tokens_encrypted
```

## Casos de Prueba Cubiertos

### GoogleConnectionTest (Feature)
- ✅ View connections index
- ✅ Create new connection
- ✅ OAuth redirect with valid URL
- ✅ OAuth callback stores encrypted tokens
- ✅ OAuth callback rejects invalid state
- ✅ Token refresh updates expiry
- ✅ Revoke connection
- ✅ Revoked connection stops sync
- ✅ Unauthorized access denied

### GoogleLocationTest (Feature)
- ✅ View locations
- ✅ Toggle location active/inactive
- ✅ Manual sync trigger
- ✅ Inactive location cannot sync
- ✅ Unauthorized access denied
- ✅ Sync updates stats

### ReviewSyncTest (Feature)
- ✅ Create new reviews from Google
- ✅ Update existing reviews
- ✅ Create moderation records
- ✅ Handle expired tokens
- ✅ Skip duplicate reviews
- ✅ Dispatch ReviewSynced event

### ReviewModerationTest (Feature)
- ✅ Toggle visibility
- ✅ Feature/unfeature reviews
- ✅ Add tags
- ✅ Add internal notes
- ✅ Permission required
- ✅ Activity logging
- ✅ Bulk updates
- ✅ Unauthorized access denied

### ReviewReplyTest (Feature)
- ✅ Create draft reply
- ✅ Update draft reply
- ✅ Approve reply
- ✅ Publish reply to Google
- ✅ Auto-publish dispatch job
- ✅ Handle Google API errors
- ✅ Status transitions (draft → approved → published)
- ✅ Template variable replacement
- ✅ Delete replies
- ✅ Unauthorized access denied

### ReviewTemplateTest (Feature)
- ✅ View templates
- ✅ Create template
- ✅ Update template
- ✅ Delete template
- ✅ Variable replacement
- ✅ Usage count increment
- ✅ Unauthorized access denied
- ✅ Validation errors

### ReviewApiTest (Feature)
- ✅ Paginated results
- ✅ Filter by location
- ✅ Filter by rating
- ✅ Filter by visibility
- ✅ Authentication required
- ✅ Rate limiting (60/min)
- ✅ Stats endpoint
- ✅ Eager load relations
- ✅ Search by keyword

### Unit Tests

#### Enums (15 tests)
- ReviewRating: value(), fromInt(), stars(), validation
- ReplyStatus: label(), color(), enum cases
- ConnectionStatus: label(), color(), enum cases

#### Models (52 tests)
- Review: relationships, scopes, visibility, ratings, comments
- ReviewReply: relationships, status transitions, scopes
- ReviewGoogleConnection: relationships, token management, status

#### Services (33 tests)
- GoogleAuthService: OAuth flow, token refresh, revocation
- GoogleReviewService: sync, API calls, rating mapping
- ReviewReplyService: CRUD, approval, publishing, templates

## Helpers del TestCase

```php
// Crear entidades de prueba
$user = $this->createUser(['permission-name']);
$connection = $this->createConnection($user);
$location = $this->createLocation($connection);
$review = $this->createReview($location);
$moderation = $this->createModeration($review);
$reply = $this->createReply($review, $user);

// Mock Google API
$this->fakeGoogleOAuthSuccess();
$this->fakeGoogleOAuthError();
$this->fakeGoogleReviewsResponse($reviews);
$this->fakeGoogleReplySuccess();
$this->fakeGoogleReplyError();
$this->fakeGoogleAccountsResponse($accounts);
$this->fakeGoogleLocationsResponse($locations);

// Generar datos de Google
$reviewData = $this->createGoogleReviewData([
    'reviewId' => 'custom-id',
    'starRating' => 'FIVE',
]);
```

## Patrón de Tests

### Feature Test Example
```php
public function test_user_can_create_connection(): void
{
    $user = $this->createUser(['reviews.manage-connections']);

    $response = $this->actingAs($user)
        ->post(route('settings.reviews.connections.store'), [
            'name' => 'Mi Negocio',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('review_google_connections', [
        'user_id' => $user->id,
        'name' => 'Mi Negocio',
    ]);
}
```

### Unit Test Example
```php
public function test_review_belongs_to_location(): void
{
    $location = ReviewGoogleLocation::factory()->create();
    $review = Review::factory()->for($location)->create();

    $this->assertInstanceOf(ReviewGoogleLocation::class, $review->location);
    $this->assertTrue($review->location->is($location));
}
```

## Dependencias de Testing

- PHPUnit 11
- Laravel RefreshDatabase
- Http::fake() para Google API
- Queue::fake() para jobs
- Event::fake() para eventos
- Factories para datos de prueba
- Spatie Permission para autorización

## Notas

- Todos los tests usan `RefreshDatabase` trait
- Google API calls mockeadas por defecto con `Http::preventStrayRequests()`
- Tests aislados (cada uno independiente)
- Verificación de permisos con Spatie Permission
- Activity logging verificado donde aplica
- Tokens encriptados en conexiones

## Próximos Pasos

Para ejecutar la suite completa y verificar todo funciona:

```bash
php artisan test modules/Reviews/tests --coverage
```
