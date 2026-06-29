---
name: module-audit
description: "Full audit of a module covering security, performance, code quality, and test coverage. Use when auditing a module, reviewing module health, or preparing for production."
context: fork
agent: plan
disable-model-invocation: false
argument-hint: "[ModuleName]"
---

# Full Module Audit

Audit the module: **$ARGUMENTS**

Run three parallel reviews using subagents, then synthesize findings.

## Parallel Review 1: security agent
- Check all controllers for authorization (policies/gates)
- Search for `{!!` in Blade views (XSS risk)
- Check for raw SQL queries (injection risk)
- Verify Form Requests on all endpoints
- Check mass assignment protection ($fillable vs $guarded)
- Verify routes have middleware (auth, throttle)
- Check file upload validation

## Parallel Review 2: performance agent
- Profile key queries with EXPLAIN via Boost `database-query`
- Check for N+1 queries (missing eager loading)
- Check for missing indexes on filtered/sorted columns
- Check for uncached frequently-accessed data
- Review pagination on list endpoints
- Check for unnecessary column loading (missing select())

## Parallel Review 3: review agent
- Check code quality and conventions
- Verify return types on all methods
- Check for dead code and unused imports
- Verify naming conventions (PSR-12, Laravel standards)
- Check for fat controllers (logic should be in services)
- Verify error handling

## Synthesis
Combine all findings into a single report:

```
## Module Audit: [Name]
### Health Score: X/10

### Critical (Must Fix)
1. **[Issue]** in `file:line` - Description + Fix

### Warnings (Should Fix)
1. **[Issue]** in `file:line` - Description

### Performance
- Query count: N (N+1 detected: yes/no)
- Missing indexes: [list]
- Cache opportunities: [list]

### Passed
- [Checks that passed]

### Recommendations
1. [Priority ordered list]
```
