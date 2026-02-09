# Workflow: Security Audit

Run a security audit on the specified scope. Execute these checks:

## Steps to Execute

1. **Dependency scan**:
   ```bash
   composer audit
   ```

2. **Code scan** (use Grep tool for vulnerabilities):
   - Unescaped Blade: search `{!!` in `modules/` with glob `*.blade.php`
   - Hardcoded secrets: search `password|secret|api_key` in `modules/` and `config/` with glob `*.php`
   - Open mass assignment: search `$guarded = []` in `modules/` with glob `*.php`
   - Raw SQL: search `whereRaw|DB::raw|DB::select` in `modules/` with glob `*.php`

3. **Route audit**: Use Boost `list-routes` to check middleware on all endpoints

4. **Config audit**: Use Boost `get-config` to verify:
   - CORS not set to `*`
   - Session secure flags
   - Debug mode

5. **Report findings** by severity (Critical > Warning > Info)

If $ARGUMENTS is provided, scope the audit to those files/areas.

$ARGUMENTS
