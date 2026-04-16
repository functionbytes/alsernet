---
name: module-test
description: "Generate PHPUnit tests for an existing module by analyzing its controllers, routes, and models. Creates feature tests covering happy path, validation, authorization, and edge cases. Auto-generates factories if missing. Use when adding test coverage to an existing module."
disable-model-invocation: false
argument-hint: "[ModuleName] [optional: specific entity]"
---

# Generate Tests for Module: $ARGUMENTS

Parse: `{ModuleName}` (required) + optional `{EntityName}` for specific entity.

## MANDATORY: Analyze Before Generating

### Step 1: Discover Module Structure
1. Read `modules/{ModuleName}/module.json` - get alias
2. Use Boost `list-routes --name={alias}` - list all routes to test
3. Read `modules/{ModuleName}/app/Http/Controllers/` - identify all controllers
4. Read `modules/{ModuleName}/app/Models/` - identify models and relationships
5. Read `modules/{ModuleName}/app/Http/Requests/` - identify validation rules
6. Check `modules/{ModuleName}/database/factories/` - which factories exist
7. Read `modules/{ModuleName}/database/seeders/*PermissionsSeeder.php` - identify permissions

### Step 2: Identify What to Test

For EACH controller, identify:
- Routes it handles
- Auth middleware (`auth`, `auth:sanctum`)
- Permissions required (`can:{alias}.action`)
- Form Requests it uses
- Model it operates on

Generate test cases for:
1. **Happy path** - authenticated user with permission succeeds
2. **Authorization** - guest redirects/401, user without permission 403
3. **Validation** - Form Request rules, missing required fields 422
4. **Edge cases** - not found 404, invalid IDs, soft deleted records
5. **Database state** - assertDatabaseHas/Missing after CRUD

## Step 3: Generate Factory (if missing)

For each model without factory, create:

File: `modules/{ModuleName}/database/factories/{Entity}Factory.php`

```php
<?php

namespace Modules\{ModuleName}\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Factory extends Factory
{
    protected $model = {Entity}::class;

    public function definition(): array
    {
        return [
            // Generate based on migration columns
            'name' => $this->faker->sentence(3),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
```

## Step 4: Generate Feature Test

Command: `php artisan module:make-test --phpunit {Entity}Test {ModuleName}`

Or create manually:

File: `modules/{ModuleName}/tests/Feature/{Entity}Test.php`

```php
<?php

namespace Modules\{ModuleName}\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\{ModuleName}\Models\{Entity};
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class {Entity}Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions
        $this->seed(\Modules\{ModuleName}\Database\Seeders\{ModuleName}PermissionsSeeder::class);

        // Create users with different permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('{alias}.view', '{alias}.create');

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(Permission::all());
    }

    /** @test */
    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('{alias}.index'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function test_authenticated_user_with_permission_can_view_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('{alias}.index'))
            ->assertOk()
            ->assertViewIs('{alias}::entity.index');
    }

    /** @test */
    public function test_user_without_permission_cannot_view_index(): void
    {
        $userWithoutPermission = User::factory()->create();

        $this->actingAs($userWithoutPermission)
            ->get(route('{alias}.index'))
            ->assertForbidden();
    }

    /** @test */
    public function test_user_can_create_resource(): void
    {
        $data = {Entity}::factory()->make()->toArray();

        $this->actingAs($this->user)
            ->post(route('{alias}.store'), $data)
            ->assertRedirect(route('{alias}.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('{table}', [
            'name' => $data['name'],
        ]);
    }

    /** @test */
    public function test_validation_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->user)
            ->post(route('{alias}.store'), [])
            ->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function test_user_can_update_resource(): void
    {
        $entity = {Entity}::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('{alias}.update', $entity), ['name' => 'Updated Name'])
            ->assertRedirect();

        $this->assertDatabaseHas('{table}', [
            'id' => $entity->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function test_user_can_delete_resource(): void
    {
        $entity = {Entity}::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('{alias}.destroy', $entity))
            ->assertRedirect();

        $this->assertSoftDeleted($entity);
    }

    /** @test */
    public function test_returns_404_for_nonexistent_resource(): void
    {
        $this->actingAs($this->admin)
            ->get(route('{alias}.edit', 999999))
            ->assertNotFound();
    }

    /** @test */
    public function test_bulk_delete_removes_multiple_records(): void
    {
        $entities = {Entity}::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->postJson(route('{alias}.bulk-action'), [
                'action' => 'delete',
                'ids' => $entities->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJson(['count' => 3]);

        foreach ($entities as $entity) {
            $this->assertSoftDeleted($entity);
        }
    }
}
```

## Step 5: API Tests (si hay api.php)

File: `modules/{ModuleName}/tests/Feature/Api/{Entity}ApiTest.php`

```php
<?php

namespace Modules\{ModuleName}\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\{ModuleName}\Models\{Entity};
use Tests\TestCase;

class {Entity}ApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $this->getJson(route('api.{alias}.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function test_authenticated_user_can_list_resources(): void
    {
        Sanctum::actingAs(User::factory()->create());
        {Entity}::factory()->count(3)->create();

        $this->getJson(route('api.{alias}.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'createdAt'],
                ],
                'meta' => ['total', 'per_page'],
            ]);
    }

    /** @test */
    public function test_can_create_resource_via_api(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $data = ['name' => 'New Resource'];

        $this->postJson(route('api.{alias}.store'), $data)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Resource');
    }

    /** @test */
    public function test_api_validation_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.{alias}.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
```

## Step 6: Run Tests

```bash
# Tests del modulo especifico
php artisan test --filter={Entity}Test

# Todos los tests del modulo
php artisan test modules/{ModuleName}/tests/

# Con coverage
php artisan test --coverage --filter={Entity}Test
```

## Step 7: Verify Coverage

Check tests cover:
- [ ] Happy path authenticated user
- [ ] Guest redirects/unauthorized
- [ ] User without permission forbidden
- [ ] All validation rules (missing, invalid type, wrong format)
- [ ] CRUD: create, read, update, delete
- [ ] Bulk actions
- [ ] 404 on nonexistent resources
- [ ] Soft deletes (si model usa SoftDeletes)
- [ ] Database assertions after changes
- [ ] Authorization via Policies (si existen)
- [ ] Events dispatched (si usa events)

## Rules

- **PHPUnit only**: NO Pest
- **RefreshDatabase**: en todos los tests con DB
- **Factories always**: nunca manual DB inserts
- **actingAs() or Sanctum::actingAs()**: segun sea web o api
- **Seed permissions**: `$this->seed({ModuleName}PermissionsSeeder::class)`
- **Test naming**: `test_user_can_create_post` o `/** @test */ public function user_can_create_post()`
- **One behavior per test**: no combinar assertions no relacionadas
- **Spanish in messages**: comentarios y seed data puede ser en espanol
- **Assert database**: SIEMPRE verificar estado final con `assertDatabaseHas/Missing`
- **Mocking**: `Http::fake()`, `Mail::fake()`, `Queue::fake()`, `Notification::fake()`

## Commands Reference

```bash
php artisan module:make-test --phpunit {Name}Test {Module}       # Feature test
php artisan module:make-test --phpunit {Name}Test {Module} --unit  # Unit test
php artisan test --filter={Name}Test                              # Run specific
php artisan test modules/{Module}/tests/                           # Module only
php artisan test --coverage                                        # With coverage
vendor/bin/pint --dirty                                            # Format tests
```

## Ver tambien

- [rules/tests.md] para convenciones de tests
- [rules/form-requests.md] para validacion que se testea
- [rules/policies.md] para authorization que se testea
