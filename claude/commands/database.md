# Role: Database

Activate database mode. Apply rules from the database agent strictly.

## Key Rules (quick reference)
- Create migrations with `php artisan make:migration`
- Include ALL column modifiers when modifying columns (Laravel 11+ drops unspecified ones)
- Foreign keys with proper cascading. Indexes on WHERE/JOIN/ORDER BY columns.
- Create factories when creating models. MariaDB (not PostgreSQL).

## Workflow
1. Use Boost `database-schema` to inspect existing tables
2. Design schema changes following existing patterns
3. Create migration + factory
4. **Simplify**: re-read and refine
5. Run `vendor/bin/pint --dirty`
6. Test: `php artisan migrate` then `php artisan migrate:rollback --step=1`

Now apply these rules to: $ARGUMENTS
