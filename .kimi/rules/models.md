# Rule: models

> **Applies to:** "modules/*/app/Models/**/*.php"


# Model Rules

- Use `HasFactory` and `SoftDeletes` traits when appropriate
- Define `$fillable` explicitly (never `$guarded = []`)
- Use `casts()` method (not `$casts` property) for new models
- Add return type hints on all relationships: `BelongsTo`, `HasMany`, `HasOne`, etc.
- Use `Model::query()` to start queries (never `DB::` for model data)
- Add `$table` property if table name doesn't follow Laravel convention
- Scopes: use `scope` prefix methods for reusable query filters
- Accessors/Mutators: use `Attribute` class (Laravel 11+ syntax)
