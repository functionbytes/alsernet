# Memory Index

## Feedback
- [pint --dirty ignores path arg](feedback_pint_dirty_ignores_path_arg.md) — `--dirty <file>` scans ALL git-dirty files repo-wide (not scoped); use plain `pint <file>` (no --dirty) to format a single file in this heavily multi-agent shared checkout
- [HelpdeskTickets manager routes require role](feedback_helpdesktickets_manager_routes_require_role.md) — manager.helpdesk.* routes gated by role:super-admin|super-settings middleware; tests need assignRole, not just permissions, or they 403 before reaching the Policy

## Reference
- [Cross-connection exists: rule needs prefix](reference_cross_connection_exists_rule_needs_prefix.md) — `exists:table,id` on a helpdesk-connection table silently fails validation unless written as `exists:helpdesk.table,id`
