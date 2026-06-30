---
name: project-helpdeskerp-test-infrastructure
description: Test infrastructure issues and workarounds discovered in HelpdeskErp module tests
metadata:
  type: project
---

# HelpdeskErp module test infrastructure

## Critical: use DatabaseTransactions, NOT RefreshDatabase
`RefreshDatabase` calls `php artisan migrate:fresh` which fails because module migrations have table ordering bugs (tables that reference other tables are migrated before the referenced table exists). `DatabaseTransactions` wraps each test in a transaction and requires no migration.

**Why:** The `system_test_pristine` DB must be pre-populated with the full schema. Bootstrap it once with:
```bash
mysql -u root -p... -h 127.0.0.1 system_test_pristine < /tmp/system_schema_full.sql
```
Where `system_schema_full.sql` = `mysqldump --no-data system`.

## Pulse stub required
`ErpContextService::recordPulse()` calls `Pulse::set()` with an array but `Pulse::set()` requires a string. This causes `TypeError` in tests even when `PULSE_ENABLED=false`. Guard in code checks `app()->bound(Pulse::class)` which is always true.

**Workaround (in test setUp):**
```php
$this->app->instance(\Laravel\Pulse\Pulse::class, new class {
    public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
    { return new \stdClass; }
    public function record(mixed ...$args): object { return new \stdClass; }
});
```

**Real fix needed:** `ErpContextService::recordPulse()` should `json_encode($value)` the array, or check `config('pulse.enabled')` instead of `app()->bound()`.

## manager_url config required
`ErpContextService` reads `config('helpdeskErp.manager_url')` in constructor. In tests, env var `ERP_MANAGER_URL` is empty so all HTTP calls return empty context ('not_configured'). Always set in setUp:
```php
config(['helpdeskErp.manager_url' => 'http://manager.test']);
```
This must be done BEFORE `$this->app->make(ErpContextService::class)` if testing the service directly.

## helpdesk_data_access_logs table mismatch
`AuditDataAccess` middleware (on ERP routes) tries to write to `helpdesk_data_access_logs` (Laravel pluralized default), but the migration creates `helpdesk_data_access_log` (singular). The model `HelpdeskDataAccessLog` has no explicit `$table` property.

**Workaround:** Add both the singular and plural tables to the test DB, or add `protected $table = 'helpdesk_data_access_log';` to the model.

**Real fix needed:** Model needs `$table` property or migration must use plural name.

## Test DB bootstrap (127.0.0.1, not localhost socket)
Always use `-h 127.0.0.1` when loading schemas for the test DB — `localhost` uses a Unix socket that may point to a different server instance.

## How to apply
Use this pattern for all HelpdeskErp tests:
- `use DatabaseTransactions;`
- Stub Pulse in `setUp()`
- Set `config(['helpdeskErp.manager_url' => 'http://manager.test'])` in `setUp()`
- Seed `HelpdeskErpPermissionsSeeder` and call `app(PermissionRegistrar::class)->forgetCachedPermissions()` first
