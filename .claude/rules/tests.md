---
globs: "modules/*/tests/**/*.php,tests/**/*.php"
---

# Test Rules

- PHPUnit ONLY (convert any Pest tests)
- Use `RefreshDatabase` trait for database tests
- Use model factories with states (never manual DB inserts)
- Test method naming: `test_user_can_create_post` (snake_case with test_ prefix)
- Test ALL paths: happy, failure, edge cases, authorization
- One behavior per test method
- Mock external services: `Http::fake()`, `Mail::fake()`, `Queue::fake()`, `Notification::fake()`
- Use `actingAs($user)` for authenticated requests
- Assert status codes: `assertOk()`, `assertCreated()`, `assertForbidden()`, `assertUnprocessable()`
- Assert database: `assertDatabaseHas()`, `assertDatabaseMissing()`, `assertSoftDeleted()`
