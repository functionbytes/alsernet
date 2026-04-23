# Rule: seeders

> **Applies to:** "modules/*/database/seeders/**/*.php"


# Seeder Rules

- Permission naming convention: `{module-alias}.action` (e.g., `attention.view`, `blog.create`)
- Use `Permission::firstOrCreate()` (idempotent, safe to run multiple times)
- Reset permission cache: `app()[PermissionRegistrar::class]->forgetCachedPermissions()`
- guard_name always `'web'`
- Main DatabaseSeeder calls sub-seeders via `$this->call([])`
- Standard permissions per entity: view, create, update, delete, manage
- Settings permissions: `{alias}.settings.view`, `{alias}.settings.update`
