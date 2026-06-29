---
globs: "modules/*/database/migrations/**/*.php"
---

# Migration Rules

- When modifying columns with `->change()`, include ALL column attributes (nullable, default, etc.). Laravel 12 drops unspecified attributes
- Always add indexes for columns used in WHERE, JOIN, ORDER BY
- Use foreign key constraints: `->foreignId('user_id')->constrained()->cascadeOnDelete()`
- Create composite indexes for multi-column queries: `$table->index(['col_a', 'col_b'])`
- Always implement `down()` method with proper rollback
- Test: `php artisan migrate` then `php artisan migrate:rollback --step=1`
