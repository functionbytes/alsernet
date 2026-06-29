# Workflow: Quality Check

Run all quality gates on the codebase. Execute these commands IN ORDER and report results.

## Steps to Execute

1. **Format code** (auto-fix):
   ```bash
   vendor/bin/pint --dirty
   ```

2. **Run tests**:
   ```bash
   php artisan test
   ```

3. **Check dependencies for vulnerabilities**:
   ```bash
   composer audit
   ```

4. **Check for common security issues** (use Grep tool, don't fix):
   - Unescaped Blade output: search `{!!` in `modules/` with glob `*.blade.php`
   - Hardcoded credentials: search `password|secret|api_key` in `modules/` and `config/` with glob `*.php`
   - Open mass assignment: search `$guarded = []` in `modules/` with glob `*.php`

5. **Report results** in this format:
   ```
   ## Quality Check Results

   ### Formatting (Pint)
   ✅ Clean / ❌ N files fixed (list them)

   ### Tests
   ✅ All pass / ❌ N failures (list them)

   ### Dependencies
   ✅ No vulnerabilities / ❌ N issues (list them)

   ### Security Scan
   ✅ Clean / ⚠️ N findings (list them)
   ```

If $ARGUMENTS is provided, scope the checks to those files/directories only.

$ARGUMENTS
