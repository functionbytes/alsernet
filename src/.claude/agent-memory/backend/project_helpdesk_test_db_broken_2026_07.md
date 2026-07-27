---
name: project-helpdesk-test-db-broken-2026-07
description: Shared Helpdesk test DB (system_test_pristine) missing helpdesk_settings table as of 2026-07-06 — causes unrelated test failures
metadata:
  type: project
---

As of 2026-07-06, running any Helpdesk Feature test that touches `Setting`/`helpdesk_settings` (via
`modules/Helpdesk/app/Helpers/helpers.php:245` calling `Modules\Helpdesk\Models\Setting`) fails with:

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'system_test_pristine.helpdesk_settings' doesn't exist
```

This reproduces on completely unrelated test files (e.g. `StatusesControllerTest`, `Inbox/StoreMessageTest`)
before any assertion runs — it's a global helper/middleware-level dependency, not something caused by the
feature under test.

**Why:** the shared `system_test_pristine` DB used by `DatabaseTransactions` tests is out of sync with
migrations (new untracked migration files exist in `modules/Helpdesk/database/migrations/` that were never
applied to the test DB). Consistent with [[feedback_parallel_agents_shared_test_db]] pattern documented
elsewhere — concurrent agents running destructive/DDL operations against the same shared test DB.

**How to apply:** if a Helpdesk Feature test fails with a missing-table `QueryException` unrelated to the
code you changed, do NOT assume you caused a regression — check whether the failure reproduces on an
unmodified/unrelated test file first (it did, on 2026-07-06: 14/14 StatusesControllerTest and 45/72 across a
4-file targeted run all failed identically on `helpdesk_settings`). Do not attempt to fix by running
`migrate:fresh` (blocked) — flag it and move on; this is an environment/infra issue for the user to resolve
(likely `php artisan migrate --env=testing` against the test DB, out of scope for a code-cleanup task).
