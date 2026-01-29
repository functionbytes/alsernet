# Automation System Migration Report

**Generated**: 2026-01-29
**Module**: Mailing
**Source**: Acelle Mail Automation System
**Status**: ⚠️ **AWAITING SOURCE FILES ACCESS**

---

## 🎯 Executive Summary

This report documents the migration of Acelle Mail's complete Automation system to the Mailing module. The Automation system is a **CRITICAL and COMPLEX** component that enables visual workflow builders for automated email campaigns (drip campaigns, abandoned cart, trigger-based emails, etc.).

**Current Status**: Migration cannot proceed automatically because the source files are located in a separate directory (`/Users/functionbytes/Function/Coding/acelle/`) that is not accessible to the agent.

---

## 📋 Components to Migrate

### 1. Models (5 files)

Located in: `/Users/functionbytes/Function/Coding/acelle/app/Model/`
Destination: `/Users/functionbytes/Function/Coding/system/modules/Mailing/app/Models/`

| Source File | Destination | Priority | Complexity |
|------------|-------------|----------|------------|
| `Automation2.php` | `Automation2.php` | **CRITICAL** | **HIGH** |
| `AutomationElement.php` | `AutomationElement.php` | **CRITICAL** | **HIGH** |
| `AutoTrigger.php` | `AutoTrigger.php` | **CRITICAL** | **MEDIUM** |
| `Email.php` | `AutomationEmail.php` | **CRITICAL** | **HIGH** |
| `EmailLink.php` | `AutomationEmailLink.php` | **CRITICAL** | **MEDIUM** |

**Notes**:
- `Email.php` will be renamed to `AutomationEmail.php` to avoid conflicts with core Laravel Mail classes
- `EmailLink.php` will be renamed to `AutomationEmailLink.php` for consistency

### 2. Library/Automation Classes (6 files)

Located in: `/Users/functionbytes/Function/Coding/acelle/app/Library/Automation/`
Destination: `/Users/functionbytes/Function/Coding/system/modules/Mailing/app/Library/Automation/`

| Source File | Description | Priority | Complexity |
|------------|-------------|----------|------------|
| `Action.php` | Base action class for workflow nodes | **CRITICAL** | **HIGH** |
| `Evaluate.php` | Condition evaluation logic | **CRITICAL** | **HIGH** |
| `Operate.php` | Operation execution engine | **CRITICAL** | **HIGH** |
| `Send.php` | Send email action | **CRITICAL** | **MEDIUM** |
| `Trigger.php` | Automation trigger conditions | **CRITICAL** | **HIGH** |
| `Wait.php` | Wait/delay action | **CRITICAL** | **LOW** |

**Purpose**: These classes form the core automation workflow engine. They enable:
- Visual workflow builder (drag-and-drop)
- Trigger-based automation (subscribe, tag added, field updated, etc.)
- Conditional logic (if/else branches)
- Timed delays (wait X hours/days)
- Email sending actions
- Complex workflow execution

### 3. Controllers (2 files)

Located in: `/Users/functionbytes/Function/Coding/acelle/app/Http/Controllers/`
Destination: `/Users/functionbytes/Function/Coding/system/modules/Mailing/app/Http/Controllers/`

| Source File | Description | Lines | Complexity |
|------------|-------------|-------|------------|
| `Automation2Controller.php` | Main automation CRUD & builder | ~1500+ | **VERY HIGH** |
| `AutoTriggerController.php` | Trigger management | ~300+ | **MEDIUM** |

**Notes**:
- `Automation2Controller.php` is one of the largest and most complex controllers
- Contains visual workflow builder logic
- JSON-based workflow structure
- Real-time preview and testing

---

## 🔧 Required Namespace Changes

### Models

```php
// BEFORE
namespace Acelle\Model;
use Acelle\Model\Customer;
use Acelle\Model\MailList;

// AFTER
namespace Modules\Mailing\Models;
use App\Models\User; // Instead of Customer
use Modules\Mailing\Models\Lists;
```

### Library Classes

```php
// BEFORE
namespace Acelle\Library\Automation;
use Acelle\Model\Automation2;

// AFTER
namespace Modules\Mailing\Library\Automation;
use Modules\Mailing\Models\Automation2;
```

### Controllers

```php
// BEFORE
namespace Acelle\Http\Controllers;
use Acelle\Model\Automation2;

// AFTER
namespace Modules\Mailing\Http\Controllers;
use Modules\Mailing\Models\Automation2;
```

---

## 📊 Database Schema Requirements

Based on the migration plan documentation, the following tables are required:

### Core Automation Tables

```sql
-- Main automation workflows
CREATE TABLE mailing_automation2s (
    id BIGINT UNSIGNED PRIMARY KEY,
    uid VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    user_id BIGINT UNSIGNED, -- FK to users table (was customer_id)
    mail_list_id BIGINT UNSIGNED, -- FK to mailing_mail_lists
    status VARCHAR(50), -- active, inactive, paused
    data JSON, -- Workflow JSON structure
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Workflow elements (nodes)
CREATE TABLE mailing_automation_elements (
    id BIGINT UNSIGNED PRIMARY KEY,
    uid VARCHAR(255) UNIQUE,
    automation2_id BIGINT UNSIGNED, -- FK to mailing_automation2s
    type VARCHAR(50), -- trigger, action, wait, condition
    position_x INT,
    position_y INT,
    options JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Trigger configurations
CREATE TABLE mailing_auto_triggers (
    id BIGINT UNSIGNED PRIMARY KEY,
    uid VARCHAR(255) UNIQUE,
    automation2_id BIGINT UNSIGNED,
    trigger_type VARCHAR(100), -- subscribe, unsubscribe, open, click, etc.
    options JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Automation emails
CREATE TABLE mailing_automation_emails (
    id BIGINT UNSIGNED PRIMARY KEY,
    uid VARCHAR(255) UNIQUE,
    automation2_id BIGINT UNSIGNED,
    subject VARCHAR(255),
    from_email VARCHAR(255),
    from_name VARCHAR(255),
    reply_to VARCHAR(255),
    html TEXT,
    plain TEXT,
    track_open BOOLEAN DEFAULT TRUE,
    track_click BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Email link tracking
CREATE TABLE mailing_automation_email_links (
    id BIGINT UNSIGNED PRIMARY KEY,
    email_id BIGINT UNSIGNED,
    url TEXT,
    clicks_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🚧 Manual Migration Steps Required

Since the agent cannot access the Acelle source directory, here are the **MANUAL STEPS** the user needs to follow:

### Step 1: Copy Model Files

```bash
# Navigate to Acelle directory
cd /Users/functionbytes/Function/Coding/acelle

# Copy models
cp app/Model/Automation2.php /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Models/
cp app/Model/AutomationElement.php /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Models/
cp app/Model/AutoTrigger.php /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Models/

# Copy and rename Email.php to avoid conflicts
cp app/Model/Email.php /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Models/AutomationEmail.php
cp app/Model/EmailLink.php /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Models/AutomationEmailLink.php
```

### Step 2: Copy Library/Automation Directory

```bash
# Copy entire Library/Automation directory
mkdir -p /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Library/Automation

cp -r app/Library/Automation/* /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Library/Automation/
```

### Step 3: Copy Controllers

```bash
# Copy controllers
cp app/Http/Controllers/Automation2Controller.php /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Http/Controllers/
cp app/Http/Controllers/AutoTriggerController.php /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Http/Controllers/
```

### Step 4: Update Namespaces (Agent will do this)

After files are copied, run the agent again to:
1. Update all namespaces
2. Update all imports
3. Adapt customer → user relationships
4. Verify all dependencies

---

## 🔄 Alternative Approach: Provide Source Files

If the user wants full autonomous migration, they can:

**Option A**: Move Acelle directory into system project
```bash
mv /Users/functionbytes/Function/Coding/acelle /Users/functionbytes/Function/Coding/system/acelle-source
```

**Option B**: Create symlink
```bash
cd /Users/functionbytes/Function/Coding/system
ln -s /Users/functionbytes/Function/Coding/acelle acelle-source
```

**Option C**: Copy specific files to a temporary location
```bash
cd /Users/functionbytes/Function/Coding/system/modules/Mailing
mkdir -p temp-migration/models
mkdir -p temp-migration/library
mkdir -p temp-migration/controllers

# Then copy files there
```

---

## 📝 Expected Changes After Migration

### 1. Automation2.php Model

```php
// CRITICAL CHANGES:

// Relationship changes
// BEFORE:
public function customer() {
    return $this->belongsTo('Acelle\Model\Customer');
}

// AFTER:
public function user() {
    return $this->belongsTo('App\Models\User');
}

// BEFORE:
public function mailList() {
    return $this->belongsTo('Acelle\Model\MailList');
}

// AFTER:
public function mailList() {
    return $this->belongsTo('Modules\Mailing\Models\Lists');
}
```

### 2. Library Classes

All Library/Automation classes will need:
- Namespace update to `Modules\Mailing\Library\Automation`
- Import updates for models
- Potential updates to job dispatching (Laravel 12 changes)

### 3. Controllers

Controllers will need:
- Namespace update
- Authorization checks (Spatie Permission instead of Acelle's system)
- View paths update (`mailing::automation2.index` instead of `automation2.index`)
- Route name updates (`mailing.automation2.index` instead of `automation2.index`)

---

## ⚠️ Known Complexity Areas

### 1. Workflow JSON Structure
- The automation system uses complex JSON to store workflow nodes and connections
- This structure must be preserved exactly
- Example:
```json
{
  "tree": [
    {
      "type": "ElementTrigger",
      "options": {
        "key": "subscribe-list",
        "list_id": 123
      },
      "children": [
        {
          "type": "ElementWait",
          "options": {
            "delay": 1,
            "unit": "day"
          },
          "children": [
            {
              "type": "ElementAction",
              "options": {
                "action": "send-email",
                "email_id": 456
              }
            }
          ]
        }
      ]
    }
  ]
}
```

### 2. Trigger Types
Multiple trigger types need to be preserved:
- `subscribe-list` - When subscriber joins list
- `unsubscribe-list` - When subscriber leaves list
- `open-email` - When email is opened
- `click-link` - When link is clicked
- `tag-added` - When tag is added
- `field-updated` - When custom field changes
- `api-trigger` - Via API call
- `anniversary` - On specific date

### 3. Conditional Logic
- The Evaluate.php class handles complex conditions
- Supports AND/OR logic
- Field comparisons (equals, contains, greater than, etc.)
- Segment membership checks

### 4. Visual Builder
- The frontend uses a complex drag-and-drop system
- Built with custom JavaScript (not Vue/React)
- Canvas-based workflow designer
- Real-time validation

---

## 🧪 Testing Requirements

After migration, the following must be tested:

### Unit Tests
- [ ] Automation2 model relationships
- [ ] AutomationElement hierarchy
- [ ] Trigger evaluation logic
- [ ] Action execution
- [ ] Wait/delay calculation

### Integration Tests
- [ ] Create automation workflow
- [ ] Execute automation
- [ ] Trigger-based execution
- [ ] Email sending within workflow
- [ ] Conditional branching
- [ ] Multi-step workflows

### UI Tests
- [ ] Workflow builder loads
- [ ] Drag-and-drop functionality
- [ ] Save/load workflows
- [ ] Preview mode
- [ ] Testing with sample data

---

## 📈 Performance Considerations

### Queue Configuration
Automation jobs should use dedicated queue:
```php
// config/queue.php
'mailing_automation' => [
    'driver' => 'redis',
    'queue' => 'mailing_automation',
    'retry_after' => 180,
],
```

### Horizon Supervisor
```php
// config/horizon.php
'supervisor-mailing-automation' => [
    'connection' => 'redis',
    'queue' => ['mailing_automation'],
    'processes' => 5,
    'tries' => 3,
],
```

---

## 📚 Documentation Needed

After successful migration, document:

1. **Automation User Guide**
   - How to create workflows
   - Available triggers
   - Available actions
   - Best practices

2. **Developer Guide**
   - Adding custom triggers
   - Adding custom actions
   - Extending the workflow builder
   - API integration

3. **Architecture Documentation**
   - Workflow execution flow
   - Database schema
   - Job processing
   - Error handling

---

## 🎯 Next Steps

### Immediate Actions Required

1. **User Action Required**: Copy source files from Acelle directory to Mailing module using one of the methods described in "Manual Migration Steps"

2. **Agent Action (After files copied)**:
   - Update all namespaces
   - Update all imports
   - Adapt relationships (customer → user)
   - Verify dependencies
   - Run Laravel Pint for code formatting

3. **Testing Phase**:
   - Write unit tests
   - Write integration tests
   - Manual UI testing

4. **Documentation Phase**:
   - Update this report with actual changes made
   - Create user guide
   - Create developer guide

---

## 🚨 Critical Dependencies

The Automation system depends on:

### From Mailing Module
- ✅ `Campaign.php` model
- ✅ `Lists.php` model
- ✅ `Subscriber.php` model
- ✅ `Template.php` model
- ⚠️ `TrackingLog.php` model (verify exists)
- ⚠️ `OpenLog.php` model (verify exists)
- ⚠️ `ClickLog.php` model (verify exists)

### From Core System
- ✅ `App\Models\User` (auth system)
- ✅ Spatie Permission (for authorization)
- ✅ Laravel Queue (job processing)
- ✅ Laravel Horizon (job monitoring)

### External Packages
- ⚠️ Verify these are installed in composer.json:
  - `league/pipeline` - For workflow processing
  - `spatie/laravel-medialibrary` - For email attachments

---

## 📊 Estimated Effort

| Task | Hours | Risk |
|------|-------|------|
| Copy source files (manual) | 0.5 | Low |
| Update namespaces (agent) | 2 | Low |
| Update imports (agent) | 2 | Medium |
| Adapt relationships (agent) | 4 | High |
| Update controllers (agent) | 6 | High |
| Create/verify migrations | 4 | Medium |
| Write unit tests | 8 | Medium |
| Write integration tests | 8 | Medium |
| Manual UI testing | 4 | Low |
| Documentation | 6 | Low |
| **TOTAL** | **44.5 hours** | **MEDIUM-HIGH** |

---

## 📋 Checklist

### Pre-Migration
- [ ] Acelle source files accessible
- [ ] Mailing module structure created
- [ ] Database migrations prepared
- [ ] Dependencies installed

### Migration Phase
- [ ] Models copied and updated
- [ ] Library classes copied and updated
- [ ] Controllers copied and updated
- [ ] Routes configured
- [ ] Views migrated
- [ ] Assets migrated

### Post-Migration
- [ ] All tests passing
- [ ] Code formatted with Pint
- [ ] Documentation complete
- [ ] PR reviewed and approved
- [ ] Deployed to staging
- [ ] User acceptance testing

---

## 🔗 Related Documentation

- [MIGRATION_PLAN.md](./MIGRATION_PLAN.md) - Overall migration strategy
- [ACELLE_LIBRARY_ANALYSIS.md](./ACELLE_LIBRARY_ANALYSIS.md) - Library classes analysis
- [ACELLE_MODELS_ANALYSIS.md](./ACELLE_MODELS_ANALYSIS.md) - Models analysis

---

**Status**: ⏸️ **AWAITING SOURCE FILES**
**Next Action**: User must copy source files from Acelle directory
**Agent Ready**: Yes - will continue migration once files are accessible
**Last Updated**: 2026-01-29
