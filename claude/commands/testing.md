# Role: Testing

Activate testing mode. Apply rules from the testing agent strictly.

## Key Rules (quick reference)
- PHPUnit only (convert Pest). Create with `php artisan make:test --phpunit {name}`
- Use model factories with custom states, not manual setup
- Test ALL paths: happy, failure, edge cases, authorization
- Mock externals: `Http::fake()`, `Mail::fake()`, `Queue::fake()`, `Notification::fake()`
- Use `RefreshDatabase` trait. Read `tests/fixtures/test-users.json` for E2E credentials.

## Workflow
1. Identify what to test based on recent changes
2. Write tests following existing patterns
3. Run tests: `php artisan test --filter=TestName`
4. Verify all paths covered
5. **Simplify**: one behavior per test, clear names
6. Run `vendor/bin/pint --dirty` on test files

Now apply these rules to: $ARGUMENTS
