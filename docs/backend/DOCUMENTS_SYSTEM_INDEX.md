# Document Management System - Documentation Index

Complete guide to understanding and working with Alsernet's document management system.

---

## 📚 Documentation Roadmap

Choose your starting point based on your role:

### For Project Managers / Product Owners
→ **Start with:** [DOCUMENT_SYSTEM_ARCHITECTURE.md](./DOCUMENT_SYSTEM_ARCHITECTURE.md)

**What you'll learn:**
- Complete document lifecycle from Prestashop order to completion
- How customers interact with the system
- Email notifications and timelines
- Admin capabilities and workflows

**Read sections:**
- Overview and system design
- Document lifecycle phases
- Email notification system
- Status transitions and timeline

---

### For Backend Developers Implementing Features
→ **Start with:** [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md) + [DOCUMENTS_QUICK_REFERENCE.md](./DOCUMENTS_QUICK_REFERENCE.md)

**Implementation plan shows:**
- Phase-by-phase implementation roadmap
- Service classes to create
- Controllers and endpoints needed
- Database interactions
- Background jobs and scheduling

**Quick reference shows:**
- Where to find code
- Common code patterns
- How to add new features
- Testing strategies

**Then read:** [MAILERS_DOCUMENTS_INTEGRATION.md](./MAILERS_DOCUMENTS_INTEGRATION.md) for email template setup

---

### For Email & Template Configuration
→ **Start with:** [MAILERS_DOCUMENTS_INTEGRATION.md](./MAILERS_DOCUMENTS_INTEGRATION.md)

**What you'll learn:**
- How email templates integrate with mailers system
- Language support and multi-language email
- Template variables and placeholders
- How to create new email types
- Global settings that control email behavior

**Then reference:** [DOCUMENTS_QUICK_REFERENCE.md](./DOCUMENTS_QUICK_REFERENCE.md) for quick lookups

---

### For System Debugging / Troubleshooting
→ **Use:** [DOCUMENTS_QUICK_REFERENCE.md](./DOCUMENTS_QUICK_REFERENCE.md)

**Sections to use:**
- "Common Mistakes" for issues
- "Testing Commands" for validation
- "Database Schema Quick Reference" for data structure
- "Code Patterns" for implementation examples

---

## 📖 Document Overview

### 1. DOCUMENT_SYSTEM_ARCHITECTURE.md
**Purpose:** Complete architectural understanding of the system

**Contents:**
- System overview and key components
- Document lifecycle (7 phases)
- Database tables and relationships
- State machine with 7 statuses and 13 transitions
- Email notification system
- SLA policy system
- Audit trail and DocumentAction system
- Prestashop webhook integration
- Decision rationale for design choices

**Read time:** 30-45 minutes
**Best for:** Understanding the big picture
**Contains:** Flowcharts, diagrams, detailed explanations

---

### 2. IMPLEMENTATION_PLAN.md
**Purpose:** Step-by-step implementation roadmap

**Contents:**
- Overview and prerequisites
- Phase 2: Service Layer implementation (4 services)
- Phase 3: Controller implementation (2 controllers with methods)
- Phase 4: Mailable classes (5 email classes)
- Phase 5: Event-driven architecture (4 events + 6 listeners)
- Phase 6: Background jobs (2 scheduled jobs)
- Phase 7: Admin interface (manage view enhancements)
- Phase 8: Testing (10 test cases)
- Detailed implementation checklist

**Read time:** 20-30 minutes
**Best for:** Developers implementing features
**Contains:** Code signatures, docstrings, execution examples

---

### 3. MAILERS_DOCUMENTS_INTEGRATION.md
**Purpose:** Email system integration guide

**Contents:**
- System overview architecture diagram
- Email template database structure
- Global configuration mapping (7 settings)
- Document type configuration integration
- SLA policy email impact
- Language-aware email implementation
- Complete email trigger points (7 triggers)
- Template variables reference
- Implementation workflow (5 steps)
- Testing & troubleshooting

**Read time:** 25-35 minutes
**Best for:** Email template setup, language support
**Contains:** SQL schemas, code examples, configuration guides

---

### 4. DOCUMENTS_QUICK_REFERENCE.md
**Purpose:** Fast lookup guide for developers

**Contents:**
- Quick task guides (add new email, change SLA, modify status)
- Status transition flow diagram
- Email triggers summary table
- Language support quick guide
- Database schema quick reference
- Common code patterns (5 patterns)
- Statistics & queries
- Performance tips
- Testing commands
- Common mistakes reference

**Read time:** 5-10 minutes per lookup
**Best for:** Daily development work
**Contains:** Tables, code snippets, command examples

---

## 🎯 Common Workflows

### Workflow: "I need to add a new email type"

1. **Read:** MAILERS_DOCUMENTS_INTEGRATION.md → "Template Variables" section
2. **Reference:** DOCUMENTS_QUICK_REFERENCE.md → "Add a New Email Template Type"
3. **Follow:** MAILERS_DOCUMENTS_INTEGRATION.md → "Implementation Workflow → Step 1"
4. **Code:** Create method in DocumentEmailService
5. **Test:** Send test email via admin UI

**Time:** ~30 minutes

---

### Workflow: "I need to understand document status transitions"

1. **Read:** DOCUMENT_SYSTEM_ARCHITECTURE.md → "Status Machine" section
2. **Reference:** DOCUMENTS_QUICK_REFERENCE.md → "Status Transition Flow"
3. **Code:** Check `document_status_transitions` table structure
4. **Implement:** Use DocumentStatusTransition::getValidTransitions()

**Time:** ~15 minutes

---

### Workflow: "I need to set up the SLA escalation system"

1. **Read:** DOCUMENT_SYSTEM_ARCHITECTURE.md → "SLA Policies" section
2. **Read:** MAILERS_DOCUMENTS_INTEGRATION.md → "SLA Policy Integration"
3. **Reference:** DOCUMENTS_QUICK_REFERENCE.md → "Change Default SLA Times"
4. **Configure:** Go to `/manager/settings/documents/sla-policies`
5. **Create** new policy with escalation settings
6. **Test:** Run hourly job and verify escalation email

**Time:** ~20 minutes

---

### Workflow: "I need to implement language-aware emails"

1. **Read:** MAILERS_DOCUMENTS_INTEGRATION.md → "Language-Aware Email Implementation"
2. **Verify:** Customer table has `lang_id` field
3. **Create:** Email templates for each language in mailers UI
4. **Code:** SendDocumentEmailJob handles language lookup
5. **Test:** Send email with different customer languages

**Time:** ~25 minutes

---

## 🔗 Key File References

### Database Migrations
```
database/migrations/
├── 2025_12_10_185133_create_document_statuses_table.php
├── 2025_12_10_185134_create_document_status_histories_table.php
├── 2025_12_10_185134_create_document_status_transitions_table.php
├── 2025_12_10_185134_add_status_id_to_documents_table.php
├── 2025_12_10_185139_create_document_sla_policies_table.php
└── 2025_12_10_185139_create_document_sla_breaches_table.php
```

### Existing Models
```
app/Models/Order/
├── Document.php (core model - has status_id, sla_policy_id)
├── DocumentStatus.php (NEW - status definitions)
├── DocumentStatusHistory.php (NEW - audit trail)
├── DocumentStatusTransition.php (NEW - state machine)
├── DocumentSlaPolicy.php (NEW - SLA configuration)
├── DocumentSlaBreach.php (NEW - SLA tracking)
├── DocumentConfiguration.php (existing - document types)
└── DocumentAction.php (existing - action logging)
```

### Controllers
```
app/Http/Controllers/Managers/Settings/
├── DocumentSlaPoliciesController.php (NEW - SLA admin)
└── Orders/
    ├── DocumentConfigurationController.php (existing - global settings)
    └── DocumentTypeController.php (existing - document types)
```

### Routes
```
routes/managers.php

// New routes (added)
/manager/settings/documents/sla-policies/ (7 routes)
```

### Views
```
resources/views/managers/views/settings/documents/
├── index.blade.php (nav page)
├── configurations/
│   └── index.blade.php (global settings)
├── types/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
└── sla-policies/ (NEW)
    ├── index.blade.php
    ├── create.blade.php
    └── edit.blade.php
```

---

## 🛠️ Setup Checklist

- [ ] **Database**
  - [ ] Run migrations for document statuses
  - [ ] Run migrations for SLA policies
  - [ ] Seed document statuses (7 statuses)
  - [ ] Seed status transitions (13 transitions)

- [ ] **Email System**
  - [ ] Create 6 email templates in mailers UI (`document_*`)
  - [ ] Create email layouts (header, footer, wrapper)
  - [ ] Verify customer table has `lang_id`
  - [ ] Configure mail driver in `.env`

- [ ] **Global Settings**
  - [ ] Configure `/manager/settings/documents/configurations`
  - [ ] Set `enable_initial_request`, `enable_reminder`, etc.
  - [ ] Configure `reminder_days` (default: 7)

- [ ] **Document Types**
  - [ ] Verify default types: corta, rifle, escopeta, dni, general
  - [ ] Configure required documents per type
  - [ ] Test type selection in upload portal

- [ ] **SLA Policies**
  - [ ] Create default SLA policy via UI
  - [ ] Set times: upload_request, review, approval
  - [ ] Configure business hours if needed
  - [ ] Set document type multipliers
  - [ ] Enable escalation if needed

- [ ] **Code Implementation** (Phase 2-8)
  - [ ] Create DocumentEmailService
  - [ ] Create SendDocumentEmailJob
  - [ ] Create status transition events/listeners
  - [ ] Create reminder and SLA check jobs
  - [ ] Add event listeners for email triggers
  - [ ] Update admin manage view with actions
  - [ ] Write tests for status transitions
  - [ ] Write tests for email sending

- [ ] **Testing**
  - [ ] Test initial request email
  - [ ] Test reminder email after X days
  - [ ] Test missing documents request
  - [ ] Test approval/rejection/completion emails
  - [ ] Test SLA escalation
  - [ ] Test language fallback

---

## 📊 System Statistics

### Database Tables
- **7** core document tables (statuses, transitions, SLA, breaches, history, configuration)
- **13** valid state transitions
- **7** document statuses
- **5** default document types (corta, rifle, escopeta, dni, general)
- **6** email template types

### Routes
- **7** SLA policy management routes
- **2** global configuration routes
- **5** document type management routes

### Email Types
- **6** document email templates
- **7** trigger points
- **4** global settings
- **5** template-specific variables

### Implementation
- **4** service classes to create
- **2** controller updates needed
- **5** mailable classes to create
- **4** events + 6 listeners
- **2** scheduled background jobs

---

## 🚨 Critical Concepts

### Status Machine
Documents flow through states: PENDING → INCOMPLETE/AWAITING_DOCUMENTS → APPROVED → COMPLETED

State transitions are:
- **Strictly defined** in `document_status_transitions` table
- **Permission-based** (some transitions require `documents.approve`, etc.)
- **Conditional** (some require all documents uploaded)
- **Audited** (every change logged in `document_status_histories`)

### Language Support
All emails are language-aware:
- Customer `lang_id` determines template language
- Templates fallback to system default if language version not found
- Dates and numbers formatted per language

### SLA Multipliers
SLA times are adjusted per document type:
- Default: 1.0
- Rifles: 1.0
- Escopetas: 1.0
- Armas Cortas: 0.75 (faster)
- DNI: 0.5 (fastest)
- Orders: 1.5 (slower)

### Email Triggers
Emails are triggered by:
1. **Events** (DocumentCreated, StatusChanged)
2. **Global Settings** (enable_initial_request, enable_reminder)
3. **Admin Actions** (request documents, approve, reject)
4. **Scheduler Jobs** (reminders, SLA checks)

### Audit Trail
Every action logged in `document_actions`:
- Document created
- Document uploaded
- Status changed
- Email sent
- Note added
- Admin action performed

---

## 🤔 FAQ

### Q: How do I test emails locally?
**A:** Use Laravel Mail in memory or Laravel Dusk for UI testing. See DOCUMENTS_QUICK_REFERENCE.md → Testing Commands

### Q: Can I customize email content per language?
**A:** Yes! Create templates in mailers UI with same key but different lang_id. System automatically selects correct language.

### Q: What if SLA multiplier is 0.5?
**A:** The approval_time is halved. E.g., 1440 minutes × 0.5 = 720 minutes (12 hours)

### Q: How do I prevent documents from timing out?
**A:** Create a reminder job or update the document's expiration_date via admin UI.

### Q: Can status transitions require special permissions?
**A:** Yes! Set `permission` field in `document_status_transitions` e.g., 'documents.approve'

---

## 📝 Related Documentation

- Laravel Events & Listeners: https://laravel.com/docs/events
- Laravel Queues: https://laravel.com/docs/queues
- Laravel Mail: https://laravel.com/docs/mail
- Eloquent Relationships: https://laravel.com/docs/eloquent-relationships

---

## 🔄 Development Phases

```
Phase 1: Design & Architecture ✅ COMPLETE
├─ Database design
├─ Model relationships
├─ Status machine design
└─ Email integration planning

Phase 2: Service Layer 🔜 TODO
├─ DocumentStatusService
├─ DocumentActionService (enhance)
└─ DocumentMailService (enhance)

Phase 3: Controllers 🔜 TODO
├─ Document management controller
└─ Existing controllers enhanced

Phase 4: Email Mailables 🔜 TODO
├─ 5 email classes
└─ Template integration

Phase 5: Events & Listeners 🔜 TODO
├─ 4 event classes
└─ 6 listener classes

Phase 6: Background Jobs 🔜 TODO
├─ Reminder job
├─ SLA check job
└─ Scheduler configuration

Phase 7: Admin Interface 🔜 TODO
├─ Document manage view
└─ Timeline display

Phase 8: Testing 🔜 TODO
├─ Feature tests
├─ Unit tests
└─ Integration tests
```

---

## 📞 Support

For questions about:
- **Architecture:** See DOCUMENT_SYSTEM_ARCHITECTURE.md
- **Implementation:** See IMPLEMENTATION_PLAN.md
- **Emails & Languages:** See MAILERS_DOCUMENTS_INTEGRATION.md
- **Quick Lookups:** See DOCUMENTS_QUICK_REFERENCE.md

All documentation is maintainable Markdown in `docs/backend/` directory.

---

## 📋 Version History

| Version | Date | Updates |
|---------|------|---------|
| 1.0 | 2025-12-10 | Initial documentation complete - architecture, implementation plan, mailers integration, quick reference |

---

**Last Updated:** 2025-12-10
**Maintained By:** Development Team
**Status:** Active Development (Phase 2 - Service Layer Implementation)
