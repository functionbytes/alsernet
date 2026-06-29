---
name: project_helpdesk_document_auth_gap
description: HelpdeskDocument module migrated only file-delete, panel-view, and import-from-chat to helpdesk-scoped auth; ~15 other Document API mutating routes remain protected only by auth:web with no helpdesk permission or conversation ownership check
metadata:
  type: project
---

The HelpdeskDocument module (`modules/HelpdeskDocument/`) added a proper ownership pattern in `DocumentFileController.destroy` (conversation→customer email scoping + `can:helpdesk.conversations.view`) but only applied it to 3 routes in `routes/managers.php`:
- `DELETE /conversations/{conversation}/documents/{document}/files/{docType}`
- `GET /conversations/{conversation}/documents/{document}/panel`
- `POST /conversations/{conversation}/documents/import-from-chat`

The blade view `inbox-slots/_document-detail.blade.php` lines 209-225 still points ~15 URLs at `api.documents.*` routes from the Document module (`modules/Document/routes/api.php`). Those routes use only `['web', 'auth:web']` middleware with no helpdesk permission and no conversation ownership scoping.

Specific confirmed unprotected mutating endpoints:
- `assignUser` (line 79 DocumentValidationController): ZERO authorization
- `uploadDocument` (line 752): ZERO authorization
- `sendApproval`, `sendRejection`, `sendReminder`, etc.: explicitly commented "Sin verificación de autorización"
- `emailHistory`, `getActionHistory`, etc.: `$this->authorize()` calls all commented out

**Why:** The team migrated the file-DELETE path only, treating it as a one-off fix, without auditing all the other mutating endpoints the inbox panel consumed.

**How to apply:** When reviewing HelpdeskDocument or Document module endpoints, check that every `api.documents.*` route referenced in the inbox blade has a corresponding helpdesk-scoped proxy route in `routes/managers.php` with `can:helpdesk.conversations.view` + email ownership guard.
