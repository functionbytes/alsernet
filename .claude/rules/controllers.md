---
globs: "modules/*/app/Http/Controllers/**/*.php"
---

# Controller Rules

- Use constructor property promotion for dependency injection
- Use Form Request classes for ALL validation (never inline `$request->validate()`)
- Use `$this->authorize()` or policies for authorization
- Keep controllers thin: delegate business logic to Service classes
- Use explicit return type declarations on all methods
- Web controllers return `View`, API controllers return `JsonResponse` or Resources
- Use `Model::query()` over `DB::` facade
- Eager load relationships to prevent N+1 queries
