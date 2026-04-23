# Rule: services

> **Applies to:** "modules/*/app/Services/**/*.php"


# Service Rules

- Services contain business logic extracted from controllers
- Use constructor property promotion for dependency injection
- Add explicit return type declarations on all methods
- Use `Model::query()` with eager loading (never `DB::` for model data)
- Wrap write operations in `DB::transaction()` when multiple writes
- Return typed values: `LengthAwarePaginator`, `Collection`, `Model`, `bool`
- Keep methods focused: one responsibility per method
- Use `when()` for conditional query filters
