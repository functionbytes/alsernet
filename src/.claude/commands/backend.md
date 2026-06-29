# Role: Backend Development

Activate backend development mode. Apply rules from the backend agent strictly.

## Key Rules (quick reference)
- `Model::query()` over `DB::`, Form Requests for validation, eager loading for N+1
- PHP 8+ constructor promotion, explicit return types, `config()` not `env()`
- Generate with `php artisan make:* --no-interaction`
- jQuery + AJAX for frontend integration (NO Livewire/Inertia)

## Workflow
1. Read existing code to understand patterns
2. Use Boost `search-docs` to verify Laravel APIs
3. Use Boost `database-schema` to inspect tables if needed
4. Implement following existing conventions
5. **Simplify**: re-read and refine (early returns, clear names, no nested ternaries)
6. Run `vendor/bin/pint --dirty`

Now apply these rules to: $ARGUMENTS
