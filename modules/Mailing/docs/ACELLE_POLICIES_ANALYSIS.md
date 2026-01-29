# Acelle Policies Analysis Report

**Generated:** 2026-01-29
**Source:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/`
**Total Policies:** 33

## Executive Summary

This document provides a comprehensive analysis of Acelle's authorization policies, documenting permission models, access rules, quota enforcement, and critical policies for mailing functionality. The analysis covers 33 policy classes that implement Laravel's authorization system using the `HandlesAuthorization` trait.

---

## Table of Contents

1. [Authorization Architecture](#authorization-architecture)
2. [Role-Based Access Control](#role-based-access-control)
3. [Critical Mailing Policies](#critical-mailing-policies)
4. [Resource Permissions Matrix](#resource-permissions-matrix)
5. [Quota System](#quota-system)
6. [Security Patterns](#security-patterns)
7. [Policy Dependencies](#policy-dependencies)
8. [Implementation Recommendations](#implementation-recommendations)

---

## 1. Authorization Architecture

### 1.1 Policy Structure

All Acelle policies follow a consistent structure:

```php
namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\{Resource};

class {Resource}Policy
{
    use HandlesAuthorization;

    // Permission methods: read, create, update, delete, etc.
}
```

### 1.2 Core Authorization Patterns

1. **Ownership Verification**
   - Most policies verify `$user->customer_id == $item->customer_id`
   - Ensures users only access their own resources

2. **Role-Based Permissions**
   - Separate logic for `admin` and `customer` roles
   - Uses permission strings: `'yes'`, `'no'`, `'all'`, `'own'`

3. **Quota Enforcement**
   - Uses `get_tmp_quota($customer, 'quota_name')` function
   - Supports unlimited quotas with `-1` value
   - Checks current usage vs. quota limits

4. **Feature Toggling**
   - Uses `app_profile('feature.disable')` for feature flags
   - Allows disabling entire features at configuration level

---

## 2. Role-Based Access Control

### 2.1 Admin Permissions

Admin permissions follow a three-level system:

| Level | Description | Authorization Check |
|-------|-------------|---------------------|
| `'no'` | No access | Permission denied |
| `'own'` | Own resources only | `$user->admin->id == $item->admin_id` |
| `'all'` | All resources | Full access granted |
| `'yes'` | Create permission | Can create new resources |

**Example from SendingServerPolicy:**

```php
public function read(User $user, SendingServer $item, $role)
{
    switch ($role) {
        case 'admin':
            $can = $user->admin->getPermission('sending_server_read') != 'no';
            break;
        case 'customer':
            $can = $user->admin->getPermission('sending_server_read') != 'no';
            break;
    }
    return $can;
}
```

### 2.2 Customer Permissions

Customer permissions are typically boolean and ownership-based:

- Ownership check: `$user->customer->id == $item->customer_id`
- Quota limits enforced
- Subscription plan features respected

### 2.3 Permission Naming Conventions

| Resource | Permission Keys |
|----------|----------------|
| Campaigns | `campaign_max`, `campaign_create`, `campaign_update` |
| Mail Lists | `list_max`, `list_import`, `list_export` |
| Sending Servers | `sending_server_read`, `sending_server_create`, `sending_server_update`, `sending_server_delete` |
| Templates | `template_read`, `template_create`, `template_update`, `template_delete` |
| Subscribers | `subscriber_max`, `subscriber_per_list_max` |
| Automations | `automation_max` |
| Segments | `segment_per_list_max` |

---

## 3. Critical Mailing Policies

### 3.1 CampaignPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/CampaignPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View campaign | Ownership check |
| `create()` | Create new campaign | Quota: `campaign_max`, config limit check |
| `update()` | Edit campaign | Ownership + status check (NEW, QUEUING, QUEUED, ERROR, PAUSED, SCHEDULED) |
| `delete()` | Remove campaign | Ownership + status check (includes SENDING, DONE) |
| `pause()` | Pause sending | Status: QUEUING, QUEUED, SENDING, SCHEDULED |
| `run()` | Start campaign | Status: NEW only |
| `restart()` | Resume campaign | Status: PAUSED, ERROR, SCHEDULED |
| `send_test_email()` | Send test | Status: all except NEW |
| `resend()` | Resend campaign | Status: DONE or PAUSED |
| `copy()` | Duplicate | Ownership check |
| `preview()` | Preview content | Ownership check |

**Status-Based Permissions:**

```php
Campaign::STATUS_NEW           // Can run, update
Campaign::STATUS_QUEUING       // Can pause, update, delete
Campaign::STATUS_QUEUED        // Can pause, update, delete
Campaign::STATUS_SENDING       // Can pause, delete
Campaign::STATUS_ERROR         // Can restart, update, delete
Campaign::STATUS_PAUSED        // Can restart, update, delete
Campaign::STATUS_DONE          // Can delete, resend
Campaign::STATUS_SCHEDULED     // Can pause, restart, update, delete
```

**Quota Configuration:**

```php
$max = get_tmp_quota($user->customer, 'campaign_max');
$can = $max > $user->customer->campaigns()->count() || $max == -1;

// Additional limit from config/limit.php
$limit = app_profile('campaign.limit');
if (!is_null($limit)) {
    $campaignsCount = $user->customer->campaignsCount();
    $can = $can && ($campaignsCount < $limit);
}
```

---

### 3.2 MailListPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/MailListPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View list | Ownership check |
| `create()` | Create list | Quota: `list_max`, config limit |
| `update()` | Edit list | Ownership check |
| `delete()` | Remove list | Ownership check |
| `addMoreSubscribers()` | Add subscribers | Quota: `subscriber_max`, `subscriber_per_list_max` |
| `import()` | Import subscribers | Feature: `list_import` = 'yes' |
| `export()` | Export subscribers | Feature: `list_export` = 'yes' |

**Subscriber Quota Logic:**

```php
public function addMoreSubscribers(User $user, MailList $mailList, $numberOfSubscribers = 1)
{
    $max = get_tmp_quota($user->customer, 'subscriber_max');
    $maxPerList = get_tmp_quota($user->customer, 'subscriber_per_list_max');

    return $user->customer->id == $mailList->customer_id &&
        ($max >= $user->customer->subscribersCount() + $numberOfSubscribers || $max == -1) &&
        ($maxPerList >= $mailList->subscribersCount() + $numberOfSubscribers || $maxPerList == -1);
}
```

---

### 3.3 SendingServerPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/SendingServerPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View server | Admin permission != 'no' |
| `readAll()` | View all servers | Admin permission == 'all' |
| `create()` | Create server | Admin: permission + limit check; Customer: subscription check |
| `update()` | Edit server | Admin: 'all' or 'own'; Customer: subscription + ownership |
| `delete()` | Remove server | Admin: 'all' or 'own'; Customer: subscription + ownership |
| `disable()` | Deactivate | Same as update + status != 'inactive' |
| `enable()` | Activate | Same as update + status != 'active' |
| `test()` | Test connection | Same as update or new server |

**Customer Creation Logic:**

```php
case 'customer':
    $can = true;
    $useOwnServer = $user->customer->getCurrentActiveGeneralSubscription()
                        ->planGeneral->useOwnSendingServer();

    if ($useOwnServer) {
        $max = get_tmp_quota($user->customer, 'sending_servers_max');
        $isUnlimited = $max == -1;
        $notReachMax = $user->customer->sendingServersCount() < $max;
        $can = $isUnlimited || $notReachMax;
    }

    // Additional limit from config/limit.php
    $limit = app_profile('sending_server.limit');
    if (!is_null($limit)) {
        $sendingServerCount = $user->customer->sendingServers()->count();
        $can = $can && ($sendingServerCount < $limit);
    }
```

**SAAS vs Non-SAAS Mode:**

- Non-SAAS mode falls back to admin case
- Checks subscription plan features: `useOwnSendingServer()`
- System servers available when customer doesn't use own servers

---

### 3.4 TemplatePolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/TemplatePolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View template | Admin: permission != 'no'; Customer: ownership or public |
| `create()` | Create template | Admin: 'yes'; Customer: always true |
| `view()` | Full template view | Admin: 'all' or 'own'; Customer: ownership or public |
| `update()` | Edit template | Admin: 'all' or 'own'; Customer: ownership only |
| `delete()` | Remove template | Admin: 'all' or 'own'; Customer: ownership only |
| `image()` | Upload images | Same as read |
| `preview()` | Preview template | Same as read |
| `copy()` | Duplicate template | Admin: 'all' or 'own'; Customer: ownership or public |

**Public Template Access:**

```php
// Templates without customer_id are public (system templates)
$can = $user->customer->id == $item->customer_id || !isset($item->customer_id);
```

---

### 3.5 SubscriberPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/SubscriberPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View subscriber | Mail list ownership via `$item->mailList->customer_id` |
| `create()` | Add subscriber | Always true (constraints checked in MailListPolicy) |
| `update()` | Edit subscriber | Mail list ownership |
| `delete()` | Remove subscriber | Mail list ownership |
| `subscribe()` | Resubscribe | Mail list ownership |
| `unsubscribe()` | Unsubscribe | Mail list ownership |

**Delegation Pattern:**

```php
public function create(User $user)
{
    // constraints are checked in MailListPolicy
    return true;
}
```

---

### 3.6 Automation2Policy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/Automation2Policy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `list()` | List automations | Feature check: `automation.disable` |
| `create()` | Create automation | Quota: `automation_max` |
| `view()` | View automation | Feature check + ownership |
| `update()` | Edit automation | Feature check + ownership |
| `enable()` | Activate | Feature + ownership + status = INACTIVE |
| `disable()` | Deactivate | Feature + ownership + status = ACTIVE |
| `delete()` | Remove | Feature + ownership + status in [ACTIVE, INACTIVE] |

**Feature Toggle:**

```php
if (app_profile('automation.disable') === true) {
    return false;
}
```

---

### 3.7 SegmentPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/SegmentPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `list()` | List segments | Feature: `list.disable_segment` |
| `create()` | Create segment | Feature + quota: `segment_per_list_max` |
| `update()` | Edit segment | Feature + mail list ownership |
| `delete()` | Remove segment | Feature + mail list ownership |
| `export()` | Export segment | Feature + mail list ownership |

**Per-List Quota:**

```php
$max_per_list = get_tmp_quota($customer, 'segment_per_list_max');

return $customer->id == $item->mailList->customer_id
    && ($max_per_list > $item->mailList->segments()->count() || $max_per_list == -1);
```

---

### 3.8 SenderPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/SenderPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `listing()` | List senders | Always true |
| `read()` | View sender | Ownership check |
| `create()` | Create sender | Always true |
| `update()` | Edit sender | Ownership check |
| `delete()` | Remove sender | Ownership check |
| `verify()` | Verify email | Ownership check |

---

### 3.9 SendingDomainPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/SendingDomainPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View domain | Non-SAAS: always true; SAAS: subscription check |
| `readAll()` | View all domains | Admin: 'all'; Customer: false |
| `create()` | Create domain | Always true |
| `update()` | Edit domain | Non-SAAS: true; SAAS: subscription + ownership |
| `delete()` | Remove domain | Non-SAAS: true; SAAS: subscription + ownership |

**SAAS Mode Subscription Checks:**

```php
if (!config('app.saas')) {
    return true; // Any domain works in non-SAAS mode
}

$subscription = $user->customer->getNewOrActiveGeneralSubscription();

if ($subscription->planGeneral->useOwnSendingServer()) {
    return true;
} else {
    $server = $subscription->planGeneral->primarySendingServer();
    return $server->allowVerifyingOwnDomains() || $server->allowVerifyingOwnDomainsRemotely();
}
```

---

### 3.10 BlacklistPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/BlacklistPolicy.php`

**Key Methods:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View blacklist | Admin: permission; Customer: always true |
| `readAll()` | View all | Admin: 'yes'; Customer: false |
| `create()` | Add to blacklist | Admin: permission; Customer: always true |
| `import()` | Import blacklist | Admin: permission; Customer: always true |
| `importCancel()` | Cancel import | Admin: permission; Customer: always true |
| `update()` | Edit entry | Admin: permission; Customer: ownership |
| `delete()` | Remove entry | Admin: permission; Customer: ownership |

---

### 3.11 BounceHandlerPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/BounceHandlerPolicy.php`

**Admin-only resource with feature toggle:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View handler | Feature check + permission ('all' or 'own') |
| `readAll()` | View all | Feature check + permission == 'all' |
| `create()` | Create handler | Feature check + permission == 'yes' |
| `update()` | Edit handler | Feature check + permission ('all' or 'own') |
| `delete()` | Remove handler | Feature check + permission ('all' or 'own') |
| `test()` | Test connection | Same as update or new handler |

**Feature Toggle:**

```php
if (app_profile('bounce_handler.disable') === true) {
    return false;
}
```

---

### 3.12 FeedbackLoopHandlerPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/FeedbackLoopHandlerPolicy.php`

**Admin-only resource, identical structure to BounceHandlerPolicy:**

- Feature flag: `feedback_loop_handler.disable`
- Permission key: `fbl_handler_read`, `fbl_handler_create`, `fbl_handler_update`, `fbl_handler_delete`
- Same authorization patterns as bounce handler

---

### 3.13 EmailVerificationServerPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/EmailVerificationServerPolicy.php`

**Dual role support with feature toggle:**

| Method | Purpose | Admin | Customer |
|--------|---------|-------|----------|
| `read()` | View server | Permission != 'no' | Subscription: `useOwnEmailVerificationServer()` |
| `readAll()` | View all | Permission == 'all' | False |
| `create()` | Create server | Permission + limit | Subscription + quota: `email_verification_servers_max` |
| `update()` | Edit server | 'all' or 'own' | Subscription + ownership |
| `delete()` | Remove server | 'all' or 'own' | Subscription + ownership |
| `disable()` | Deactivate | Update permission | Subscription + ownership + status != INACTIVE |
| `enable()` | Activate | Update permission | Subscription + ownership + status != ACTIVE |

**Feature Toggle:**

```php
if (app_profile('email_verfication_server.disable') === true) {
    return false;
}
```

---

### 3.14 TrackingDomainPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/TrackingDomainPolicy.php`

**Simplified policy with minimal restrictions:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `read()` | View domain | Always true |
| `create()` | Create domain | Always true |
| `update()` | Edit domain | Ownership check |
| `delete()` | Remove domain | Ownership check |

---

### 3.15 FormPolicy

**Location:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/FormPolicy.php`

**Subscription form policies with feature toggle:**

| Method | Purpose | Authorization Logic |
|--------|---------|---------------------|
| `list()` | List forms | Feature: `form.disable` |
| `create()` | Create form | Feature check |
| `read()` | View form | Feature + ownership |
| `update()` | Edit form | Feature + ownership |
| `delete()` | Remove form | Feature + ownership |
| `publish()` | Publish form | Feature + ownership + status = DRAFT |
| `unpublish()` | Unpublish form | Feature + ownership + status = PUBLISHED |

---

## 4. Resource Permissions Matrix

### 4.1 Campaign Permissions

| Action | Customer | Admin (All) | Admin (Own) | Quota/Condition |
|--------|----------|-------------|-------------|-----------------|
| Read | Owner only | All campaigns | Created by admin | - |
| Create | Yes | Yes | Yes | `campaign_max`, `campaign.limit` |
| Update | Owner | All campaigns | Created by admin | Status in [NEW, QUEUING, QUEUED, ERROR, PAUSED, SCHEDULED] |
| Delete | Owner | All campaigns | Created by admin | Status not SENDING |
| Pause | Owner | All campaigns | Created by admin | Status in [QUEUING, QUEUED, SENDING, SCHEDULED] |
| Run | Owner | All campaigns | Created by admin | Status = NEW |
| Restart | Owner | All campaigns | Created by admin | Status in [PAUSED, ERROR, SCHEDULED] |

### 4.2 Mail List Permissions

| Action | Customer | Admin | Quota/Condition |
|--------|----------|-------|-----------------|
| Read | Owner only | All | - |
| Create | Yes | Yes | `list_max`, `list.limit` |
| Update | Owner only | All | - |
| Delete | Owner only | All | - |
| Import | Owner only | All | `list_import` = 'yes' |
| Export | Owner only | All | `list_export` = 'yes' |
| Add Subscribers | Owner only | All | `subscriber_max`, `subscriber_per_list_max` |

### 4.3 Sending Server Permissions

| Action | Customer | Admin (All) | Admin (Own) | Condition |
|--------|----------|-------------|-------------|-----------|
| Read | Subscription-based | Permission != 'no' | Permission != 'no' | - |
| Create | Subscription + quota | Permission = 'yes' | Permission = 'yes' | `sending_servers_max`, config limit |
| Update | Owner + subscription | All servers | Own servers | - |
| Delete | Owner + subscription | All servers | Own servers | - |
| Enable | Owner + subscription | All servers | Own servers | Status != 'active' |
| Disable | Owner + subscription | All servers | Own servers | Status != 'inactive' |

### 4.4 Template Permissions

| Action | Customer | Admin (All) | Admin (Own) | Note |
|--------|----------|-------------|-------------|------|
| Read | Owner or public | All | All | Public = no customer_id |
| Create | Always | Yes | Yes | - |
| Update | Owner only | All | Own | - |
| Delete | Owner only | All | Own | - |
| Copy | Owner or public | All | Own | - |
| Preview | Owner or public | All | All | - |

### 4.5 Subscriber Permissions

| Action | Customer | Admin | Note |
|--------|----------|-------|------|
| Read | Mail list owner | All | Via `mailList->customer_id` |
| Create | Always | Always | Quota checked in MailListPolicy |
| Update | Mail list owner | All | - |
| Delete | Mail list owner | All | - |
| Subscribe | Mail list owner | All | - |
| Unsubscribe | Mail list owner | All | - |

### 4.6 Automation Permissions

| Action | Customer | Admin | Quota/Condition |
|--------|----------|-------|-----------------|
| List | Yes | Yes | Feature: `automation.disable` |
| Create | Yes | Yes | `automation_max` |
| View | Owner | All | Feature check |
| Update | Owner | All | Feature check |
| Enable | Owner | All | Status = INACTIVE |
| Disable | Owner | All | Status = ACTIVE |
| Delete | Owner | All | Status in [ACTIVE, INACTIVE] |

---

## 5. Quota System

### 5.1 Quota Types

| Quota Name | Resource | Type | Description |
|------------|----------|------|-------------|
| `campaign_max` | Campaign | Integer | Maximum campaigns per customer |
| `list_max` | MailList | Integer | Maximum mail lists per customer |
| `subscriber_max` | Subscriber | Integer | Total subscribers across all lists |
| `subscriber_per_list_max` | Subscriber | Integer | Maximum subscribers per single list |
| `sending_servers_max` | SendingServer | Integer | Maximum sending servers per customer |
| `automation_max` | Automation | Integer | Maximum automations per customer |
| `segment_per_list_max` | Segment | Integer | Maximum segments per list |
| `email_verification_servers_max` | EmailVerificationServer | Integer | Maximum email verification servers |
| `list_import` | MailList | String ('yes'/'no') | Allow list import |
| `list_export` | MailList | String ('yes'/'no') | Allow list export |

### 5.2 Quota Enforcement Pattern

```php
// Standard quota check
$max = get_tmp_quota($user->customer, 'quota_name');
$can = $max > $currentCount || $max == -1; // -1 = unlimited

// With config limit override
$limit = app_profile('resource.limit');
if (!is_null($limit)) {
    $can = $can && ($currentCount < $limit);
}
```

### 5.3 Unlimited Quota

- Value: `-1`
- Meaning: No limit on resource creation
- Check: `$max == -1` or `$max == '-1'`

### 5.4 Quota Function

**Function:** `get_tmp_quota($customer, $quotaKey)`

**Returns:**
- Integer for numeric quotas
- String `'yes'` or `'no'` for boolean features
- `-1` for unlimited

**Usage:**

```php
$campaignMax = get_tmp_quota($user->customer, 'campaign_max');
$canImport = get_tmp_quota($user->customer, 'list_import') == 'yes';
```

---

## 6. Security Patterns

### 6.1 Ownership Verification

**Pattern:**

```php
public function action(User $user, Resource $item)
{
    return $user->customer->id == $item->customer_id;
}
```

**Applies to:**
- Campaigns
- Mail Lists
- Templates (for updates)
- Senders
- Tracking Domains
- Forms
- Blacklist entries (customer scope)

### 6.2 Status-Based Authorization

**Pattern:**

```php
public function action(User $user, Resource $item)
{
    return $ownership && in_array($item->status, [
        Resource::STATUS_ALLOWED_1,
        Resource::STATUS_ALLOWED_2,
    ]);
}
```

**Applies to:**
- Campaigns (pause, run, restart, update, delete)
- Automations (enable, disable)
- Forms (publish, unpublish)
- Sending Servers (enable, disable)

### 6.3 Permission Level Checking

**Pattern:**

```php
$ability = $user->admin->getPermission('resource_action');

// For read
$can = $ability != 'no';

// For create
$can = $ability == 'yes';

// For update/delete
$can = $ability == 'all' || ($ability == 'own' && $user->admin->id == $item->admin_id);
```

**Applies to:**
- All admin-level resources
- Sending Servers
- Templates
- Email Verification Servers
- Bounce Handlers
- Feedback Loop Handlers

### 6.4 Role-Based Switch

**Pattern:**

```php
switch ($role) {
    case 'admin':
        $can = /* admin logic */;
        break;
    case 'customer':
        $can = /* customer logic */;
        break;
}
return $can;
```

**Applies to:**
- Sending Servers
- Sending Domains
- Email Verification Servers
- Templates
- Blacklist

### 6.5 Subscription-Based Authorization

**Pattern:**

```php
$subscription = $user->customer->getCurrentActiveGeneralSubscription();
$can = $subscription->planGeneral->useOwnSendingServer();
```

**Applies to:**
- Sending Servers (customer creation)
- Email Verification Servers (customer access)
- Sending Domains (SAAS mode)

### 6.6 Feature Toggle Pattern

**Pattern:**

```php
if (app_profile('feature.disable') === true) {
    return false;
}
```

**Applies to:**
- Automations (`automation.disable`)
- Segments (`list.disable_segment`)
- Forms (`form.disable`)
- Bounce Handlers (`bounce_handler.disable`)
- Feedback Loop Handlers (`feedback_loop_handler.disable`)
- Email Verification Servers (`email_verfication_server.disable`)

### 6.7 Self-Action Prevention

**Pattern:**

```php
// Prevent admin from deleting/disabling themselves
$can = $ability && $item->id !== $user->admin->id;

// Prevent logging in as self
$can = $ability && $user->admin->id != $item->id;
```

**Applies to:**
- Admin delete/disable
- Admin login-as
- Customer login-as

---

## 7. Policy Dependencies

### 7.1 Direct Dependencies

```
CampaignPolicy
├── MailListPolicy (for subscriber constraints)
└── SubscriptionPolicy (for quota retrieval)

MailListPolicy
├── SubscriberPolicy (delegates subscriber creation)
└── SegmentPolicy (segment limits per list)

SendingServerPolicy
├── SubscriptionPolicy (for plan features)
└── PlanPolicy (for useOwnSendingServer check)

SendingDomainPolicy
├── SendingServerPolicy (for domain verification settings)
└── SubscriptionPolicy (for SAAS mode)

SubscriberPolicy
└── MailListPolicy (ownership via mailList relation)

SegmentPolicy
└── MailListPolicy (ownership via mailList relation)

EmailVerificationServerPolicy
└── SubscriptionPolicy (for useOwnEmailVerificationServer)
```

### 7.2 Shared Dependencies

All policies depend on:
- `User` model
- `Customer` model (via `$user->customer`)
- `Admin` model (via `$user->admin` for admin actions)

### 7.3 External Dependencies

| Dependency | Purpose | Used By |
|------------|---------|---------|
| `get_tmp_quota()` | Retrieve quota values | Campaign, MailList, Automation, Segment, SendingServer, EmailVerificationServer |
| `app_profile()` | Get config values | All policies with feature toggles, Campaign, MailList, SendingServer |
| `Setting::get()` | Get system settings | CustomerPolicy (registration) |
| `Subscription->planGeneral` | Plan features | SendingServer, SendingDomain, EmailVerificationServer |

---

## 8. Implementation Recommendations

### 8.1 Migration to New System

**Priority 1: Core Mailing Policies**
1. CampaignPolicy → Migrate first (most critical)
2. MailListPolicy → Migrate second
3. SubscriberPolicy → Migrate third
4. TemplatePolicy → Migrate fourth

**Priority 2: Server & Domain Policies**
1. SendingServerPolicy
2. SendingDomainPolicy
3. TrackingDomainPolicy
4. SenderPolicy

**Priority 3: Automation & Segmentation**
1. Automation2Policy
2. SegmentPolicy
3. FormPolicy

**Priority 4: Advanced Features**
1. EmailVerificationServerPolicy
2. BounceHandlerPolicy
3. FeedbackLoopHandlerPolicy
4. BlacklistPolicy

### 8.2 Recommended Policy Adaptations

#### 8.2.1 Unified Role System

Replace switch statements with Laravel's built-in role checking:

```php
// Current Acelle pattern
switch ($role) {
    case 'admin':
        $can = /* logic */;
        break;
    case 'customer':
        $can = /* logic */;
        break;
}

// Recommended Laravel pattern using Spatie Permission
public function update(User $user, Resource $item)
{
    if ($user->hasRole('admin')) {
        return $user->can('resource_update_all')
            || ($user->can('resource_update_own') && $user->id == $item->creator_id);
    }

    return $user->customer->id == $item->customer_id;
}
```

#### 8.2.2 Extract Quota Logic

Create a dedicated quota service:

```php
// app/Services/Mailing/QuotaService.php
class QuotaService
{
    public function canCreateCampaign(Customer $customer): bool
    {
        $max = $this->getQuota($customer, 'campaign_max');
        $current = $customer->campaigns()->count();
        $configLimit = config('mailing.limits.campaigns');

        return $this->checkLimit($max, $current, $configLimit);
    }

    protected function checkLimit(int $max, int $current, ?int $configLimit): bool
    {
        $withinQuota = $max === -1 || $current < $max;
        $withinConfig = is_null($configLimit) || $current < $configLimit;

        return $withinQuota && $withinConfig;
    }
}

// In policy
public function create(User $user, Campaign $campaign)
{
    return app(QuotaService::class)->canCreateCampaign($user->customer);
}
```

#### 8.2.3 Feature Toggle Service

```php
// app/Services/Mailing/FeatureService.php
class FeatureService
{
    public function isEnabled(string $feature): bool
    {
        return config("mailing.features.{$feature}.enabled", true);
    }
}

// In policy
public function list(User $user)
{
    return app(FeatureService::class)->isEnabled('automations');
}
```

#### 8.2.4 Status Constants

Move status constants to enums (PHP 8.1+):

```php
// app/Enums/Mailing/CampaignStatus.php
enum CampaignStatus: string
{
    case NEW = 'new';
    case QUEUING = 'queuing';
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case ERROR = 'error';
    case PAUSED = 'paused';
    case DONE = 'done';
    case SCHEDULED = 'scheduled';

    public static function updatable(): array
    {
        return [self::NEW, self::QUEUING, self::QUEUED, self::ERROR, self::PAUSED, self::SCHEDULED];
    }
}

// In policy
public function update(User $user, Campaign $campaign)
{
    return $user->customer->id == $campaign->customer_id
        && in_array($campaign->status, CampaignStatus::updatable());
}
```

#### 8.2.5 Subscription Checks

Extract subscription logic:

```php
// app/Services/Mailing/SubscriptionService.php
class SubscriptionService
{
    public function canUseOwnSendingServers(Customer $customer): bool
    {
        if (!config('app.saas')) {
            return true;
        }

        $subscription = $customer->getCurrentActiveGeneralSubscription();
        return $subscription->planGeneral->useOwnSendingServer();
    }
}
```

### 8.3 Testing Recommendations

#### 8.3.1 Policy Test Structure

```php
// tests/Feature/Policies/CampaignPolicyTest.php
class CampaignPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_read_own_campaign()
    {
        $customer = Customer::factory()->create();
        $user = $customer->user;
        $campaign = Campaign::factory()->for($customer)->create();

        $this->assertTrue($user->can('read', $campaign));
    }

    public function test_customer_cannot_read_other_campaign()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $campaign = Campaign::factory()->for($customer2)->create();

        $this->assertFalse($customer1->user->can('read', $campaign));
    }

    public function test_customer_cannot_create_campaign_when_quota_exceeded()
    {
        $customer = Customer::factory()->create(['campaign_max' => 5]);
        Campaign::factory()->count(5)->for($customer)->create();

        $this->assertFalse($customer->user->can('create', Campaign::class));
    }

    public function test_customer_can_create_campaign_with_unlimited_quota()
    {
        $customer = Customer::factory()->create(['campaign_max' => -1]);
        Campaign::factory()->count(100)->for($customer)->create();

        $this->assertTrue($customer->user->can('create', Campaign::class));
    }
}
```

### 8.4 Configuration Recommendations

Create a centralized mailing configuration:

```php
// config/mailing.php
return [
    'features' => [
        'automations' => [
            'enabled' => env('MAILING_AUTOMATIONS_ENABLED', true),
        ],
        'segments' => [
            'enabled' => env('MAILING_SEGMENTS_ENABLED', true),
        ],
        'forms' => [
            'enabled' => env('MAILING_FORMS_ENABLED', true),
        ],
        'bounce_handler' => [
            'enabled' => env('MAILING_BOUNCE_HANDLER_ENABLED', true),
        ],
        'feedback_loop' => [
            'enabled' => env('MAILING_FEEDBACK_LOOP_ENABLED', true),
        ],
    ],

    'limits' => [
        'campaigns' => env('MAILING_CAMPAIGN_LIMIT', null),
        'lists' => env('MAILING_LIST_LIMIT', null),
        'sending_servers' => env('MAILING_SENDING_SERVER_LIMIT', null),
        'plans' => env('MAILING_PLAN_LIMIT', null),
    ],

    'quotas' => [
        'unlimited_value' => -1,
    ],
];
```

### 8.5 Policy Registration

Ensure policies are registered in `AuthServiceProvider`:

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    \Modules\Mailing\Models\Campaign::class => \Modules\Mailing\Policies\CampaignPolicy::class,
    \Modules\Mailing\Models\MailList::class => \Modules\Mailing\Policies\MailListPolicy::class,
    \Modules\Mailing\Models\Subscriber::class => \Modules\Mailing\Policies\SubscriberPolicy::class,
    \Modules\Mailing\Models\Template::class => \Modules\Mailing\Policies\TemplatePolicy::class,
    \Modules\Mailing\Models\SendingServer::class => \Modules\Mailing\Policies\SendingServerPolicy::class,
    \Modules\Mailing\Models\SendingDomain::class => \Modules\Mailing\Policies\SendingDomainPolicy::class,
    \Modules\Mailing\Models\Automation::class => \Modules\Mailing\Policies\AutomationPolicy::class,
    \Modules\Mailing\Models\Segment::class => \Modules\Mailing\Policies\SegmentPolicy::class,
    \Modules\Mailing\Models\Sender::class => \Modules\Mailing\Policies\SenderPolicy::class,
    \Modules\Mailing\Models\TrackingDomain::class => \Modules\Mailing\Policies\TrackingDomainPolicy::class,
    \Modules\Mailing\Models\Form::class => \Modules\Mailing\Policies\FormPolicy::class,
    \Modules\Mailing\Models\Blacklist::class => \Modules\Mailing\Policies\BlacklistPolicy::class,
    \Modules\Mailing\Models\BounceHandler::class => \Modules\Mailing\Policies\BounceHandlerPolicy::class,
    \Modules\Mailing\Models\FeedbackLoopHandler::class => \Modules\Mailing\Policies\FeedbackLoopHandlerPolicy::class,
    \Modules\Mailing\Models\EmailVerificationServer::class => \Modules\Mailing\Policies\EmailVerificationServerPolicy::class,
];
```

---

## 9. Critical Security Considerations

### 9.1 Always Verify Ownership

Never trust client-side data. Always verify:

```php
// ❌ WRONG - Trusts request parameter
$campaign = Campaign::find($request->campaign_id);
Mail::send(...);

// ✅ CORRECT - Verifies ownership
$campaign = $user->customer->campaigns()->findOrFail($request->campaign_id);
if ($user->can('update', $campaign)) {
    Mail::send(...);
}
```

### 9.2 Prevent Resource Exhaustion

Enforce quotas before expensive operations:

```php
// ❌ WRONG - Creates resource before quota check
$campaign = Campaign::create($data);
if ($user->cannot('create', $campaign)) {
    $campaign->delete();
}

// ✅ CORRECT - Checks quota first
if ($user->cannot('create', Campaign::class)) {
    abort(403, 'Campaign quota exceeded');
}
$campaign = Campaign::create($data);
```

### 9.3 Status Transition Validation

Validate status transitions:

```php
// ❌ WRONG - Allows any status change
$campaign->update(['status' => $request->status]);

// ✅ CORRECT - Validates allowed transitions
if ($campaign->status === 'sending' && $request->status === 'paused') {
    if ($user->can('pause', $campaign)) {
        $campaign->pause();
    }
}
```

### 9.4 Prevent Admin Self-Actions

```php
// ❌ WRONG - Admin can delete themselves
if ($user->can('delete', $admin)) {
    $admin->delete();
}

// ✅ CORRECT - Prevents self-deletion
if ($user->can('delete', $admin) && $admin->id !== $user->admin->id) {
    $admin->delete();
}
```

### 9.5 SAAS vs Non-SAAS Mode

Handle both modes appropriately:

```php
// ✅ CORRECT - Checks mode first
if (!config('app.saas')) {
    // Non-SAAS: simpler rules
    return true;
}

// SAAS: subscription-based rules
$subscription = $user->customer->getCurrentActiveGeneralSubscription();
return $subscription->planGeneral->useOwnSendingServer();
```

---

## 10. Summary

### 10.1 Key Findings

1. **Comprehensive Authorization System**: Acelle implements a mature, production-ready authorization system covering 33 resource types.

2. **Dual-Role Architecture**: Policies support both admin and customer roles with different permission levels.

3. **Quota-Based Resource Control**: Extensive quota system prevents resource exhaustion and enables tiered plans.

4. **Feature Toggle Support**: Built-in feature flags allow disabling functionality at configuration level.

5. **Subscription Integration**: Deep integration with subscription system for SAAS mode.

6. **Status-Based Permissions**: Sophisticated status management for campaigns and automations.

7. **Ownership Verification**: Consistent ownership checks across all customer-facing resources.

### 10.2 Critical Policies for Mailing Module

**Essential (Priority 1):**
- CampaignPolicy
- MailListPolicy
- SubscriberPolicy
- TemplatePolicy

**Important (Priority 2):**
- SendingServerPolicy
- SendingDomainPolicy
- SenderPolicy

**Advanced (Priority 3):**
- Automation2Policy
- SegmentPolicy
- BlacklistPolicy
- BounceHandlerPolicy
- FeedbackLoopHandlerPolicy
- EmailVerificationServerPolicy

### 10.3 Migration Complexity

| Policy | Complexity | Dependencies | Estimated Effort |
|--------|------------|--------------|------------------|
| CampaignPolicy | High | MailList, Subscription | 8-12 hours |
| MailListPolicy | High | Subscriber quotas | 6-8 hours |
| SendingServerPolicy | Very High | Subscription, SAAS mode | 12-16 hours |
| TemplatePolicy | Medium | None | 4-6 hours |
| SubscriberPolicy | Low | MailList | 2-4 hours |
| Automation2Policy | Medium | Feature toggles | 4-6 hours |
| SegmentPolicy | Medium | MailList quotas | 4-6 hours |
| SendingDomainPolicy | High | SendingServer, Subscription | 8-12 hours |
| BounceHandlerPolicy | Low | Feature toggles | 2-4 hours |
| FeedbackLoopHandlerPolicy | Low | Feature toggles | 2-4 hours |
| EmailVerificationServerPolicy | High | Subscription | 8-12 hours |
| BlacklistPolicy | Medium | Role-based | 4-6 hours |
| SenderPolicy | Low | None | 2-4 hours |
| TrackingDomainPolicy | Low | None | 2-4 hours |
| FormPolicy | Low | Feature toggles | 2-4 hours |

**Total Estimated Effort:** 70-110 hours

---

## Appendix A: Permission Key Reference

### Admin Permissions

| Permission Key | Values | Resources |
|----------------|--------|-----------|
| `sending_server_read` | no, own, all | SendingServer |
| `sending_server_create` | yes, no | SendingServer |
| `sending_server_update` | no, own, all | SendingServer |
| `sending_server_delete` | no, own, all | SendingServer |
| `template_read` | no, own, all | Template |
| `template_create` | yes, no | Template |
| `template_update` | no, own, all | Template |
| `template_delete` | no, own, all | Template |
| `bounce_handler_read` | no, own, all | BounceHandler |
| `bounce_handler_create` | yes, no | BounceHandler |
| `bounce_handler_update` | no, own, all | BounceHandler |
| `bounce_handler_delete` | no, own, all | BounceHandler |
| `fbl_handler_read` | no, own, all | FeedbackLoopHandler |
| `fbl_handler_create` | yes, no | FeedbackLoopHandler |
| `fbl_handler_update` | no, own, all | FeedbackLoopHandler |
| `fbl_handler_delete` | no, own, all | FeedbackLoopHandler |
| `email_verification_server_read` | no, own, all | EmailVerificationServer |
| `email_verification_server_create` | yes, no | EmailVerificationServer |
| `email_verification_server_update` | no, own, all | EmailVerificationServer |
| `email_verification_server_delete` | no, own, all | EmailVerificationServer |
| `sending_domain_read` | no, own, all | SendingDomain |
| `sending_domain_update` | no, own, all | SendingDomain |
| `sending_domain_delete` | no, own, all | SendingDomain |
| `report_blacklist` | yes, no | Blacklist |
| `admin_read` | no, own, all | Admin |
| `admin_create` | yes, no | Admin |
| `admin_update` | no, own, all | Admin |
| `admin_delete` | no, own, all | Admin |
| `admin_login_as` | no, own, all | Admin |
| `customer_read` | no, own, all | Customer |
| `customer_create` | yes, no | Customer |
| `customer_update` | no, own, all | Customer |
| `customer_delete` | no, own, all | Customer |
| `customer_login_as` | no, own, all | Customer |
| `plan_read` | no, own, all | PlanGeneral |
| `plan_create` | yes, no | PlanGeneral |
| `plan_update` | no, own, all | PlanGeneral |
| `plan_delete` | no, own, all | PlanGeneral |
| `plan_copy` | no, own, all | PlanGeneral |
| `subscription_read` | no, own, all | Subscription |

---

## Appendix B: Feature Toggle Reference

| Feature Flag | Default | Policy | Effect When Disabled |
|--------------|---------|--------|---------------------|
| `automation.disable` | false | Automation2Policy | All automation actions blocked |
| `list.disable_segment` | false | SegmentPolicy | All segment actions blocked |
| `form.disable` | false | FormPolicy | All form actions blocked |
| `bounce_handler.disable` | false | BounceHandlerPolicy | All bounce handler actions blocked |
| `feedback_loop_handler.disable` | false | FeedbackLoopHandlerPolicy | All FBL handler actions blocked |
| `email_verfication_server.disable` | false | EmailVerificationServerPolicy | All verification server actions blocked |

---

## Appendix C: Status Constants

### Campaign Statuses

```php
Campaign::STATUS_NEW        = 'new'
Campaign::STATUS_QUEUING    = 'queuing'
Campaign::STATUS_QUEUED     = 'queued'
Campaign::STATUS_SENDING    = 'sending'
Campaign::STATUS_ERROR      = 'error'
Campaign::STATUS_PAUSED     = 'paused'
Campaign::STATUS_DONE       = 'done'
Campaign::STATUS_SCHEDULED  = 'scheduled'
```

### Automation Statuses

```php
Automation2::STATUS_ACTIVE   = 'active'
Automation2::STATUS_INACTIVE = 'inactive'
```

### Form Statuses

```php
Form::STATUS_DRAFT     = 'draft'
Form::STATUS_PUBLISHED = 'published'
```

### Server Statuses

```php
SendingServer::STATUS_ACTIVE   = 'active'
SendingServer::STATUS_INACTIVE = 'inactive'

EmailVerificationServer::STATUS_ACTIVE   = 'active'
EmailVerificationServer::STATUS_INACTIVE = 'inactive'
```

---

## Document Metadata

- **Report Generated:** 2026-01-29
- **Source Directory:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/`
- **Total Policies Analyzed:** 33
- **Laravel Version:** Compatible with Laravel 8.x+
- **PHP Version:** 7.4+ (8.1+ recommended for enums)

---

**End of Report**
