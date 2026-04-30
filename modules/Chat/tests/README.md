# Chat Module Testing Guide

Quick reference for running and maintaining Chat module tests.

## Test Suite Overview

This directory contains PHPUnit tests for the Chat module covering controllers, services, jobs, and business logic.

## Test Files

### 1. ConversationControllerTest.php
Tests for `ConversationController` covering:
- ✅ Index returns view with conversations
- ✅ Index filters by status, priority, assignee
- ✅ Index scoped to account (prevents cross-account access)
- ✅ Show returns conversation detail
- ✅ Show denies cross-account access
- ✅ Store creates conversation
- ✅ Update changes conversation
- ✅ Search returns matching conversations
- ✅ Search contacts returns customers
- ✅ Guest cannot access conversations
- ✅ User without account cannot access conversations

### 2. CustomerControllerTest.php
Tests for `CustomerController` covering:
- ✅ Index returns paginated customers
- ✅ Index filters by search
- ✅ Index filters active customers
- ✅ Show returns customer with conversations
- ✅ Store creates customer
- ✅ Update modifies customer
- ✅ Destroy deletes customer
- ✅ Export downloads CSV
- ✅ Customer scoped to account
- ✅ Guest cannot access customers
- ✅ User cannot view customer from different account
- ✅ Validation rejects duplicate email
- ✅ Validation requires name
- ✅ Block/unblock customer

### 3. CampaignsControllerTest.php
Tests for `CampaignsController` covering:
- ✅ Index returns campaigns
- ✅ Store validates with form request
- ✅ Store creates campaign
- ✅ Update modifies campaign
- ✅ Statistics returns analytics data
- ✅ Publish activates campaign
- ✅ Publish fails without content
- ✅ Pause campaign
- ✅ Resume campaign
- ✅ End campaign
- ✅ Duplicate campaign
- ✅ Destroy deletes campaign
- ✅ Campaigns require authorization
- ✅ Filter by status and type
- ✅ Search campaigns

**Note:** Campaign routes need to be registered in `modules/Chat/routes/web.php` before these tests can run successfully.

## Factories

All necessary factories have been created in `modules/Chat/database/factories/`:
- ✅ AccountFactory
- ✅ CustomerFactory
- ✅ InboxFactory
- ✅ ConversationFactory
- ✅ CampaignFactory

## Running Tests

### All Chat Module Tests
```bash
php artisan test modules/Chat/tests
```

### Critical Test Suites (NEW - Phase 1)
```bash
# Webhook security tests (22 tests)
php artisan test modules/Chat/tests/Feature/Webhooks/WhatsappWebhookTest.php

# SLA tracking tests (22 tests)
php artisan test modules/Chat/tests/Feature/Sla/SlaTrackerTest.php

# Conversation routing tests (20 tests)
php artisan test modules/Chat/tests/Feature/Routing/ConversationRoutingServiceTest.php
```

### Existing Test Suites
```bash
# Controller tests
php artisan test modules/Chat/tests/Feature/ConversationControllerTest.php
php artisan test modules/Chat/tests/Feature/CustomerControllerTest.php
php artisan test modules/Chat/tests/Feature/CampaignsControllerTest.php

# Authorization tests
php artisan test modules/Chat/tests/Feature/AuthorizationTest.php
php artisan test modules/Chat/tests/Feature/SecurityTest.php

# Unit tests
php artisan test modules/Chat/tests/Unit
```

### Single Test Method
```bash
php artisan test --filter=test_webhook_verification_succeeds_with_valid_token
php artisan test --filter=test_sla_calculates_due_date_with_business_hours
```

## Test Patterns Used

- ✅ `RefreshDatabase` trait for database isolation
- ✅ `setUp()` method for common test data
- ✅ Factory pattern for test data creation
- ✅ Account-based scoping to ensure multi-tenancy
- ✅ Authorization tests (guest, cross-account)
- ✅ Validation tests (happy path + failure paths)
- ✅ Edge case tests (empty data, boundaries)

## Test Coverage

| Area | Tests | Status |
|------|-------|--------|
| **Webhooks** | 22 | ✅ Complete (NEW) |
| **SLA Tracking** | 22 | ✅ Complete (NEW) |
| **Routing** | 20 | ✅ Complete (NEW) |
| Conversations | 16 | ⚠️ 2 skipped |
| Customers | 18 | ⚠️ 4 skipped |
| Campaigns | 18 | ⚠️ ALL skipped |
| Security | 10 | ⚠️ 6 skipped |
| Authorization | 15 | ✅ Complete |
| Model Scopes | 4 | ✅ Complete |
| **Total** | **~145** | **~25% coverage** |

### Phase 1 Complete ✅
- WhatsApp webhook security & processing
- SLA compliance tracking & breach detection
- Conversation routing (all 5 strategies)

### Next Phase (Planned)
- Automation rule executor
- Message processing jobs
- Customer merge service
- Analytics services

See `/modules/Chat/TESTING_COVERAGE_REPORT.md` for detailed analysis.

---

## Documentation

- **Coverage Report:** `/modules/Chat/TESTING_COVERAGE_REPORT.md`
- **Implementation Summary:** `/modules/Chat/TESTING_IMPLEMENTATION_SUMMARY.md`

---

## Next Steps

1. **Add Campaign routes** to `modules/Chat/routes/web.php`:
   ```php
   use Modules\Chat\Http\Controllers\Helpdesk\Campaign\CampaignsController;

   Route::prefix('campaigns')->name('campaigns.')->group(function () {
       Route::get('/', [CampaignsController::class, 'index'])->name('index');
       Route::get('/templates', [CampaignsController::class, 'templates'])->name('templates');
       Route::get('/create', [CampaignsController::class, 'create'])->name('create');
       Route::post('/', [CampaignsController::class, 'store'])->name('store');
       Route::get('/{campaign}', [CampaignsController::class, 'show'])->name('show');
       Route::get('/{campaign}/edit', [CampaignsController::class, 'edit'])->name('edit');
       Route::put('/{campaign}', [CampaignsController::class, 'update'])->name('update');
       Route::delete('/{campaign}', [CampaignsController::class, 'destroy'])->name('destroy');
       Route::post('/{campaign}/publish', [CampaignsController::class, 'publish'])->name('publish');
       Route::post('/{campaign}/pause', [CampaignsController::class, 'pause'])->name('pause');
       Route::post('/{campaign}/resume', [CampaignsController::class, 'resume'])->name('resume');
       Route::post('/{campaign}/end', [CampaignsController::class, 'end'])->name('end');
       Route::get('/{campaign}/statistics', [CampaignsController::class, 'statistics'])->name('statistics');
       Route::post('/{campaign}/duplicate', [CampaignsController::class, 'duplicate'])->name('duplicate');
   });
   ```

2. **Add missing Campaign model methods** referenced in tests:
   - `publish()`, `pause()`, `resume()`, `end()` methods
   - Statistics calculation methods
   - `scopeByStatus()` query scope
   - `CampaignImpression` model relationship

3. **Add Form Request classes** if not already present:
   - `StoreCampaignRequest`
   - `UpdateCampaignRequest`
   - `StoreConversationRequest`
   - `UpdateConversationRequest`
   - `StoreCustomerRequest`
   - `UpdateCustomerRequest`

4. **Add missing model scopes** to ensure tests pass:
   - `Conversation::forAccount()`, `withCommonRelations()`, `withStatusSlug()`, etc.
   - `Customer::forAccount()`, `search()`, `active()`, `createdThisMonth()`, etc.

5. **Create policies** for authorization tests:
   - `CampaignPolicy` with `viewAny`, `view`, `create`, `update`, `delete`, `publish`, etc.
   - `CustomerPolicy` with `view`, `update`, `delete`, `restore`, `forceDelete`
