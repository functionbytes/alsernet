---
name: fix-bug
description: "Structured bug fix workflow with log analysis, root cause identification, fix implementation, and regression test. Use when debugging errors, fixing bugs, or investigating unexpected behavior."
disable-model-invocation: false
---

# Bug Fix Workflow

Fix the following issue: **$ARGUMENTS**

## Step 1: Gather Evidence
- Use Boost `last-error` to check recent application errors
- Use Boost `read-log-entries` for relevant log entries
- Use Chrome DevTools `list_console_messages` for JS errors (if frontend issue)
- Use Chrome DevTools `list_network_requests` for failed API calls (if frontend issue)
- Run `git log --oneline -10` to see recent changes that might have caused this

## Step 2: Reproduce
- Identify the exact steps to reproduce the bug
- Use Boost `list-routes` to find the endpoint involved
- Use Boost `database-query` to check data state if relevant

## Step 3: Root Cause Analysis
- Read the relevant code files completely
- Trace the execution flow from request to response
- Identify the exact line(s) causing the issue
- Check if this is a regression from a recent change

## Step 4: Fix (delegate to backend or frontend agent)
- Implement the minimal fix that addresses the root cause
- Do NOT refactor surrounding code
- Do NOT add features beyond the fix
- Simplify: re-read and refine (early returns, clear names)
- Run `vendor/bin/pint --dirty`

## Step 5: Regression Test (delegate to testing agent)
- Write a test that would have caught this bug
- Test the happy path works after the fix
- Test edge cases related to the fix
- Run `php artisan test --filter=TestName`

## Step 6: Verify
- Reproduce the original steps - confirm the bug is fixed
- Run related tests to ensure no regressions
