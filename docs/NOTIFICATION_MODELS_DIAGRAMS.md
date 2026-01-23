# Visual Diagrams: Notification Models Analysis

## Current Architecture (BROKEN)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CURRENT STATE (PROBLEMATIC)                      │
└─────────────────────────────────────────────────────────────────────┘

                         NotificationController
                                 │
                    ┌────────────┴────────────┐
                    ▼                         ▼
        registerPushToken()         updatePreferences()
                    │                         │
        ┌───────────┴───────────┐  ┌──────────┴──────────┐
        ▼                       ▼  ▼                     ▼
NotificationPushToken  PushNotificationToken
        │                       │
        ├─ model params ✓       └─ UNUSED ❌
        └─ database cols ❌       (0 references)

        Schema mismatch:
        - expects: browser, platform, ip_address
        - has: device_name

NotificationPreference     NotificationSetting
        │                        │
        ├─ in production ✓       └─ UNUSED ❌
        ├─ table missing ❌      (0 references)
        │                       Schema mismatch:
        └─ model ok ✓           - model ≠ migration
                                - columns don't align

User Model Relationships:
  - pushTokens() → NotificationPushToken ✓
  - notificationPreferences() → NotificationPreference ✓

Database Status:
  ✓ push_notification_tokens (exists, but schema incomplete)
  ✓ notification_settings (exists, but unused/wrong schema)
  ❌ notification_preferences (missing table)
```

---

## Current Database Schema Comparison

### Push Token Models: What Exists vs What's Expected

```
┌──────────────────────────────────────────────────────────────────┐
│ push_notification_tokens TABLE (From Migration)                 │
├──────────────────────────────────────────────────────────────────┤
│ ✓ id (BIGINT)                                                    │
│ ✓ user_id (BIGINT FK)                                            │
│ ✓ token (VARCHAR, UNIQUE)                                        │
│ ✓ device_type (VARCHAR) ← "web, ios, android"                   │
│ ✓ device_name (VARCHAR, NULL)                                    │
│ ✓ last_used_at (TIMESTAMP, NULL)                                 │
│ ✓ is_active (BOOLEAN, default true)                              │
│ ✓ created_at, updated_at (TIMESTAMP)                             │
│ ✓ INDEX: user_id                                                 │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│ NotificationPushToken MODEL $fillable                            │
├──────────────────────────────────────────────────────────────────┤
│ ✓ user_id                  ← Exists in DB                        │
│ ✓ token                    ← Exists in DB                        │
│ ✓ device_type              ← Exists in DB                        │
│ ❌ browser                 ← MISSING IN DB (!)                   │
│ ❌ platform                ← MISSING IN DB (!)                   │
│ ✓ is_active                ← Exists in DB                        │
│ ✓ last_used_at             ← Exists in DB                        │
│ ❌ ip_address              ← MISSING IN DB (!)                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│ PushNotificationToken MODEL $fillable (UNUSED)                   │
├──────────────────────────────────────────────────────────────────┤
│ ✓ user_id                  ← Correct                             │
│ ✓ token                    ← Correct                             │
│ ✓ device_type              ← Correct                             │
│ ? device_id                ← Different field                     │
│ ⚠️ active (not is_active)  ← Wrong column name                   │
│ ✓ last_used_at             ← Correct                             │
└──────────────────────────────────────────────────────────────────┘

VERDICT: NotificationPushToken is ACTIVE but BROKEN
         PushNotificationToken is CORRECT but UNUSED
```

---

### Preference Models: What Exists vs What's Expected

```
┌──────────────────────────────────────────────────────────────────┐
│ notification_settings TABLE (From Migration)                    │
├──────────────────────────────────────────────────────────────────┤
│ ✓ id (BIGINT)                                                    │
│ ✓ user_id (BIGINT FK)                                            │
│ ✓ notification_type (VARCHAR)                                    │
│ ✓ email_enabled (BOOLEAN, default true)   ← Per-channel         │
│ ✓ push_enabled (BOOLEAN, default false)   ← Per-channel         │
│ ✓ in_app_enabled (BOOLEAN, default true)  ← Per-channel         │
│ ✓ frequency (VARCHAR, "instant")          ← New field           │
│ ✓ preferences (TEXT JSON)                  ← JSON data          │
│ ✓ opted_out_at (TIMESTAMP, NULL)           ← GDPR tracking      │
│ ✓ created_at, updated_at (TIMESTAMP)                             │
│ ✓ UNIQUE: user_id, notification_type                             │
│ ✓ INDEX: notification_type                                       │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│ NotificationSetting MODEL $fillable (UNUSED)                     │
├──────────────────────────────────────────────────────────────────┤
│ ✓ user_id                  ← Exists in DB                        │
│ ❌ channel                 ← NOT IN DB                           │
│ ✓ notification_type        ← Exists in DB                        │
│ ❌ enabled                 ← NOT IN DB                           │
│    (DB has: email_enabled, push_enabled, in_app_enabled)        │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│ notification_preferences TABLE (MISSING!)                        │
├──────────────────────────────────────────────────────────────────┤
│ ❌ DOES NOT EXIST                                               │
│    (NotificationPreference model exists but table doesn't)       │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│ NotificationPreference MODEL $fillable (IN PRODUCTION)           │
├──────────────────────────────────────────────────────────────────┤
│ ✓ user_id                  ← Ready for new table                 │
│ ✓ channel                  ← Ready for new table                 │
│ ✓ notification_type        ← Ready for new table                 │
│ ✓ is_enabled               ← Ready for new table                 │
│ ✓ settings (array)         ← Ready for new table                 │
└──────────────────────────────────────────────────────────────────┘

VERDICT: NotificationSetting UNUSED but BROKEN
         NotificationPreference ACTIVE but TABLE MISSING
```

---

## After Consolidation: Target Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                    TARGET STATE (FIXED)                             │
└─────────────────────────────────────────────────────────────────────┘

                         NotificationController
                                 │
                    ┌────────────┴────────────┐
                    ▼                         ▼
        registerPushToken()         updatePreferences()
                    │                         │
                    ▼                         ▼
        NotificationPushToken      NotificationPreference
                    │                         │
                    ▼                         ▼
        push_notification_tokens   notification_preferences
        (schema complete) ✓        (table exists) ✓


User Model Relationships:
  - pushTokens() → NotificationPushToken → push_notification_tokens ✓
  - notificationPreferences() → NotificationPreference → notification_preferences ✓

Database Status:
  ✓ push_notification_tokens (complete schema)
  ✓ notification_preferences (new table)
  ❌ notification_settings (deleted)

Models:
  ✓ NotificationPushToken
  ✓ NotificationPreference
  ❌ PushNotificationToken (deleted)
  ❌ NotificationSetting (deleted)
```

---

## File Deletion Flow Chart

```
BEFORE CONSOLIDATION (4 models, 2 migrations)
│
├─ Modules/Notification/app/Models/
│  ├─ NotificationPushToken.php          ✓ KEEP
│  ├─ PushNotificationToken.php           ❌ DELETE
│  ├─ NotificationPreference.php          ✓ KEEP
│  └─ NotificationSetting.php             ❌ DELETE
│
└─ database/migrations/
   ├─ 2025_12_29_054249_create_push_notification_tokens_table.php  ✓ KEEP
   └─ 2025_12_29_054242_create_notification_settings_table.php     ❌ DELETE

DELETIONS:
1. PushNotificationToken.php
   └─ No references found (0 uses in codebase)

2. NotificationSetting.php
   └─ No references found (0 uses in codebase)
   └─ Schema incompatible anyway

3. create_notification_settings_table.php
   └─ Only migration for NotificationSetting (unused)


AFTER CONSOLIDATION (2 models, 3 migrations)
│
├─ Modules/Notification/app/Models/
│  ├─ NotificationPushToken.php          ✓ KEEP
│  └─ NotificationPreference.php         ✓ KEEP
│
└─ database/migrations/
   ├─ 2025_12_29_054249_create_push_notification_tokens_table.php      ✓ KEEP
   ├─ 2025_12_30_XXXXXX_add_push_token_columns.php                     ✓ NEW
   └─ 2025_12_30_XXXXXX_create_notification_preferences_table.php      ✓ NEW
```

---

## Data Flow: Current vs Fixed

### Current (Broken) Flow

```
User tries to register push token
  │
  └─► NotificationController::registerPushToken()
       │
       ├─ Validates input ✓
       │
       └─► NotificationPushToken::register()
            │
            ├─ Calls updateOrCreate() ✓
            │
            └─► Tries to fill: browser, platform, ip_address
                │
                └─ ❌ CRASH: MassAssignmentException
                   (columns don't exist in database)
```

### Fixed Flow

```
User tries to register push token
  │
  └─► NotificationController::registerPushToken()
       │
       ├─ Validates input ✓
       │
       └─► NotificationPushToken::register()
            │
            ├─ Calls updateOrCreate() ✓
            │
            └─► Fills: browser, platform, ip_address
                │
                └─ ✅ SUCCESS: Record created/updated
                   (all columns exist in database)
```

---

## Schema Alignment: Push Tokens

```
                  MISMATCH BEFORE

        Model Layer          Database Layer
        ──────────          ──────────────
        ✓ user_id   ──────► ✓ user_id
        ✓ token      ──────► ✓ token
        ✓ device_type ─────► ✓ device_type
        ❌ browser  ─────X  (missing)
        ❌ platform ─────X  (missing)
        ✓ is_active ──────► ✓ is_active
        ✓ last_used_at ───► ✓ last_used_at
        ❌ ip_address ────X  (missing)

                        MIGRATION

                   ALIGNMENT AFTER

        Model Layer          Database Layer
        ──────────          ──────────────
        ✓ user_id   ──────► ✓ user_id
        ✓ token      ──────► ✓ token
        ✓ device_type ─────► ✓ device_type
        ✓ browser   ──────► ✓ browser (NEW)
        ✓ platform  ──────► ✓ platform (NEW)
        ✓ is_active ──────► ✓ is_active
        ✓ last_used_at ───► ✓ last_used_at
        ✓ ip_address ──────► ✓ ip_address (NEW)
```

---

## Schema Alignment: Preferences

```
                  MISSING BEFORE

        Model Layer          Database Layer
        ──────────          ──────────────
        (NotificationPreference)
        ✓ user_id           (no table exists)
        ✓ channel
        ✓ notification_type
        ✓ is_enabled
        ✓ settings

                        MIGRATION

                   ALIGNMENT AFTER

        Model Layer          Database Layer
        ──────────          ──────────────
        ✓ user_id   ──────► ✓ user_id
        ✓ channel   ──────► ✓ channel
        ✓ notification_type ► ✓ notification_type
        ✓ is_enabled ──────► ✓ is_enabled
        ✓ settings  ──────► ✓ settings (JSON)

        (Plus: created_at, updated_at, indexes)
```

---

## Timeline Gantt Chart

```
Phase 1: Verify
│▓▓▓│ (2 min)

Phase 2: Delete Models
│   ▓▓│ (2 min)

Phase 3: Delete Migration
│      ▓│ (1 min)

Phase 4: Create Migrations
│       ▓▓▓▓▓│ (5 min)

Phase 5: Run Migrations
│            ▓▓│ (2 min)

Phase 6: Test & Verify
│              ▓▓▓▓▓│ (5 min)

Total: ════════════════▓ (~30 minutes)

Each ▓ = ~1 minute
```

---

## Risk Assessment Matrix

```
            Impact
      ╔═════════════════════╗
  L   ║  ✓ LOW  │  MEDIUM  ║
  i   ║─────────┼──────────╜
  k   ║  MEDIUM │  HIGH    ║
  e   ║         │   ▲      ║
  l   ║         │   │ RISK ║
  i   ║         │   │ HERE ║
  h   ║         │   ▼      ║
  o   ║ FIXED   │ UNFIXED  ║
  o   ╚═════════════════════╝
  d

Unfixed (Current State):
  Risk: HIGH
  - Production endpoints will fail
  - Schema mismatches cause crashes
  - Duplicate models confusing

Fixed (After Consolidation):
  Risk: LOW
  - No data loss (pre-launch)
  - No breaking changes
  - Improves stability
```

---

## Code Quality Metrics

```
BEFORE CONSOLIDATION

Models:        4 (2 duplicates)
Migrations:    2 (1 unused)
Conflicts:     3 (schema mismatches)
Dead Code:     2 unused models
Code Clarity:  LOW (confusing duplicates)

Endpoints Status:
  POST /push-token        ❌ BROKEN
  POST /preferences       ❌ BROKEN


AFTER CONSOLIDATION

Models:        2 (canonical)
Migrations:    3 (all used)
Conflicts:     0
Dead Code:     0
Code Clarity:  HIGH (clear, single version)

Endpoints Status:
  POST /push-token        ✅ WORKING
  POST /preferences       ✅ WORKING
```

---

## Summary Table

```
┌─────────────────────────────────────────────────────────────────────┐
│                      CONSOLIDATION SUMMARY                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  DELETE: 3 files                                                    │
│    • PushNotificationToken.php          (unused duplicate)         │
│    • NotificationSetting.php            (unused, broken)           │
│    • create_notification_settings migration (paired with above)    │
│                                                                      │
│  CREATE: 1 table                                                    │
│    • notification_preferences           (missing table)            │
│                                                                      │
│  ADD: 3 columns to existing table                                   │
│    • push_notification_tokens.browser   (missing)                  │
│    • push_notification_tokens.platform  (missing)                  │
│    • push_notification_tokens.ip_address (missing)                 │
│                                                                      │
│  KEEP: 2 models                                                     │
│    • NotificationPushToken              (in production)            │
│    • NotificationPreference             (in production)            │
│                                                                      │
│  Time: ~30 minutes                                                  │
│  Risk: LOW (pre-launch, no user data)                              │
│  Impact: HIGH (fixes critical failures)                            │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

