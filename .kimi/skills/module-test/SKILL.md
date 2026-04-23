# /module-test — Generate PHPUnit Tests for Module

Generates feature tests for existing module controllers, routes, and models.

## When to use
- Adding test coverage to an existing module
- After creating new CRUD operations

## Full documentation
See `.claude/skills/module-test/SKILL.md` for test generation patterns.

## Quick approach
1. Analyze module controllers and routes
2. Create feature tests covering:
   - Happy path (create, read, update, delete)
   - Validation errors
   - Authorization (unauthenticated, unauthorized)
   - Edge cases
3. Auto-generate factories if missing
4. Run `php artisan test --filter=ModuleName`
