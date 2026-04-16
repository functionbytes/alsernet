---
name: team-feature
description: "Launch an agent team for parallel feature implementation with backend, frontend, and testing teammates. Use for medium-to-large features that span multiple layers."
disable-model-invocation: true
---

# Parallel Team Feature Implementation

Create an agent team to implement: **$ARGUMENTS**

## Planning Phase (Leader)
Before spawning teammates:
1. Analyze the feature requirements
2. Identify which module this belongs to
3. Check existing code for patterns and conventions
4. Design the implementation splitting work into independent pieces
5. Create a task list with dependencies

## Team Setup
Create an agent team with teammates based on the feature needs:

### Teammate: Backend Developer
Using the `backend` agent type:
- Controllers, services, models, middleware
- Form Requests for validation
- Events and listeners if needed
- Queue jobs for heavy operations
- Requires plan approval before implementing

### Teammate: Frontend Developer
Using the `frontend` agent type:
- Blade views with Bootstrap 5.3
- jQuery + AJAX interactions
- DevExpress widgets for data grids
- Responsive design (mobile, tablet, desktop)
- Requires plan approval before implementing

### Teammate: Test Engineer
Using the `testing` agent type:
- PHPUnit feature tests for all endpoints
- Test authorization, validation, happy path
- Browser E2E tests if UI-heavy
- Depends on backend and frontend completing first

## Coordination Rules
- Backend and frontend can work in parallel on separate files
- Testing starts after backend/frontend complete their tasks
- Each teammate must run `vendor/bin/pint --dirty` after PHP changes
- Each teammate must simplify code before marking tasks complete
- Avoid file conflicts: assign clear file ownership per teammate

## Quality Gates
- All tests pass
- `vendor/bin/pint` clean
- No N+1 queries
- Font Awesome 6 icons only
- jQuery + AJAX (not Livewire)
