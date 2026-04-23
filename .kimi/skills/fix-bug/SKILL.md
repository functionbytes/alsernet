# /fix-bug — Structured Bug Fix Workflow

Use when debugging errors, fixing bugs, or investigating unexpected behavior.

## Workflow
1. **Gather Evidence**
   - Use `laravel-boost:last-error` for recent errors
   - Use `laravel-boost:read-log-entries` for relevant logs
   - Use `chrome-devtools:list_console_messages` for JS errors
   - Run `git log --oneline -10` for recent changes

2. **Reproduce**
   - Identify exact steps
   - Use `laravel-boost:list-routes` to find endpoint
   - Use `laravel-boost:database-query` to check data state

3. **Root Cause Analysis**
   - Read relevant code completely
   - Trace execution flow
   - Identify exact line(s) causing issue

4. **Fix**
   - Minimal fix only (no refactoring)
   - Run `vendor/bin/pint --dirty`

5. **Regression Test**
   - Write test that would have caught the bug
   - Run `php artisan test --filter=TestName`

6. **Verify**
   - Confirm bug is fixed
   - Run related tests for regressions
