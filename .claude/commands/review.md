# Workflow: Code Review

Review recent code changes and report issues by severity. Execute these steps:

## Steps to Execute

1. **Get recent changes**:
   ```bash
   git diff --name-only
   git diff --stat
   ```
   If no uncommitted changes, check the last commit:
   ```bash
   git diff HEAD~1 --name-only
   ```

2. **Read every modified file** in full context (not just the diff)

3. **Check sibling files** to understand existing conventions

4. **Use Laravel Boost tools**:
   - `list-routes` to verify middleware on any new/modified endpoints
   - `database-schema` to check constraints if DB-related changes
   - `search-docs` to verify correct Laravel API usage

5. **Apply review checklist** on each file:

   ### Critical (Must Fix)
   - SQL injection or XSS vulnerabilities
   - Exposed secrets or credentials
   - N+1 query problems (missing eager loading)
   - Missing authorization on endpoints
   - Missing input validation
   - Mass assignment vulnerabilities

   ### Warning (Should Fix)
   - Missing return type declarations
   - Unused imports or dead code
   - Inconsistent naming with codebase conventions
   - Hardcoded values (should use config/constants)
   - Missing eager loading

   ### Code Simplification
   - Nested ternaries (use match/if-else)
   - Deep nesting (use early returns)
   - Overly compact code (clarity > brevity)
   - Redundant abstractions
   - Unnecessary comments on obvious code

6. **Report findings** in this format:
   ```
   ## Review: [Scope]

   ### Critical
   1. **[Issue]** in `file:line` - Description. Fix: [code]

   ### Warnings
   1. **[Issue]** in `file:line` - Suggestion

   ### Simplification
   1. **[Issue]** in `file:line` - How to simplify

   ### Positive
   - Things done well
   ```

If $ARGUMENTS is provided, review only those files/areas.

$ARGUMENTS
