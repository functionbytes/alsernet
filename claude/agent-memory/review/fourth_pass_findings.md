---
name: Fourth Pass Findings
description: Fourth-pass review findings across all 33 modules - new improvement areas not covered in passes 1-3
type: project
---

## Completed (Pass 4 - 2026-03-27)

### Duplicate Middleware
- Analytics routes/web.php:10+13 — outer 'web' + inner 'web' applied twice

### Enum Gaps
- BlogCategory uses raw string 'published' — PostStatus enum exists in same module, just not applied
- FormSubmissionEmail: queued/sent/failed as strings — no enum
- MailerEndpointLog: success/failed/pending as strings — no enum
- Reviews/GeneratedReport: completed/failed/processing as strings — no enum

### Job Overlap (no WithoutOverlapping/ShouldBeUnique)
- PublishScheduledPagesJob — batch job, no uniqueness; two concurrent runs would double-publish
- CreateBackupJob — long-running, no uniqueness; overlapping runs would corrupt backup ZIPs
- SyncGoogleLocationsJob — per-connection sync, no per-key uniqueness
- SyncGoogleReviewsJob — per-location sync, no per-key uniqueness
- WarmPageCacheJob — no uniqueness

### Old-Style Accessors (51 total, 3 new-style)
- All models use pre-Laravel 9 getXxxAttribute() pattern; project hasn't migrated to Attribute::make()
- Highest-value candidates: Review (4 accessors), SeoMeta (7 accessors), Page (5 accessors)
- Note: this is a low-priority stylistic improvement, not a bug

### Soft Delete Gaps
- BlogCategory — no soft delete; deleting cascades to blog_post_categories pivot
- AttentionCategory/Department/Type/Sede — config tables, no soft delete; deletion is destructive
- BackupSchedule — no soft delete; cannot recover accidentally deleted schedules

### Route Model Binding Gaps
- Role/PermissionController: Permission::findOrFail($id) on 4 methods — route already uses {id} not {permission}
- BackupScheduleController: BackupSchedule::findOrFail($id) on 5 methods

### No New Issues
- No route closures found (all use controller arrays — route caching compatible)
- No config file gaps for key modules
- No SSRF/SQLi/XSS found in fourth pass
