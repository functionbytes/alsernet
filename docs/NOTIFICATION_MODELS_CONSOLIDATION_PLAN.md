# Notification Models Consolidation Analysis & Plan

**Generated**: 2025-12-30
**Analysis Status**: Complete

---

## Executive Summary

Your Notification module contains **4 duplicate models** representing two distinct concepts:
1. **Push Notification Tokens** (2 duplicate models)
2. **Notification Preferences/Settings** (2 duplicate models)

This analysis reveals **critical inconsistencies** in naming, functionality, and database design that will cause confusion and maintenance issues. **Immediate consolidation is recommended before this grows further.**

---

## Part 1: Push Notification Token Models

### Model Comparison

| Aspect | NotificationPushToken | PushNotificationToken |
|--------|----------------------|----------------------|
| **File Path** | `Modules/Notification/app/Models/NotificationPushToken.php` | `Modules/Notification/app/Models/PushNotificationToken.php` |
| **Namespace** | `Modules\Notification\Models` | `Modules\Notification\Models` |
| **Database Table** | `notification_push_tokens` (assumed) | `push_notification_tokens` (confirmed in migration) |
| **Factory** | None | Has `HasFactory` trait |
| **Fillable Fields** | `user_id`, `token`, `device_type`, `browser`, `platform`, `is_active`, `last_used_at`, `ip_address` | `user_id`, `token`, `device_type`, `device_id`, `active`, `last_used_at` |
| **Boolean Column** | `is_active` | `active` |
| **Special Columns** | `browser`, `platform`, `ip_address` | `device_id` |
| **Methods** | `register()`, `activate()`, `deactivate()`, `activeForUser()`, `cleanup()` | `markAsUsed()`, `deactivate()`, `getActiveTokensForUser()` |
| **Scopes** | `active()`, `inactive()`, `recentlyUsed()` | None |
| **Casts Method** | Uses `casts()` method (Laravel 11+) | Uses `$casts` property (deprecated style) |
| **PHPDoc** | Minimal | Complete with property definitions |
| **Used In** | `NotificationController.php` line 163 | Not actively used in current code |
| **User Relationship** | Yes, `belongsTo(User)` | Yes, `belongsTo(User)` |

### Database Schema Mismatch

**Migration for PushNotificationToken** (`2025_12_29_054249`):
```php
Schema::create('push_notification_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('token')->unique();
    $table->string('device_type');      // web, ios, android
    $table->string('device_name')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index('user_id');
});
```

**What NotificationPushToken Expects**:
- `browser` column (not in migration)
- `platform` column (not in migration)
- `ip_address` column (not in migration)
- `device_type` with custom metadata (migration calls it device_type but model adds browser/platform)

**Critical Issue**: The NotificationPushToken model references columns that don't exist in the actual database table (`push_notification_tokens`).

### Production Usage

**NotificationController** (lines 6-7, 163):
```php
use Modules\Notification\Models\NotificationPreference;
use Modules\Notification\Models\NotificationPushToken;

// Line 163
$pushToken = NotificationPushToken::register(
    $user->id,
    $validated['token'],
    [
        'device_type' => $validated['device_type'] ?? null,
        'browser' => $validated['browser'] ?? null,
        'platform' => $validated['platform'] ?? null,
        'ip_address' => $request->ip(),
    ]
);
```

**User Model** (lines 2140-2143):
```php
public function pushTokens(): HasMany
{
    return $this->hasMany(\App\Models\Notifications\NotificationPushToken::class);
}
```

### Recommendation

**✅ KEEP: NotificationPushToken** (with corrections)
- Currently used in production via NotificationController
- Imported in User model as the canonical relationship
- Has more comprehensive helper methods (`register()`, `cleanup()`, scopes)
- Better code style (casts() method, explicit return types)

**❌ DEPRECATE: PushNotificationToken**
- Not currently used anywhere
- Has factory but no actual factory file location found
- Less feature-rich implementation
- Naming convention inconsistency (repeats "Notification" implicitly)

### Action Items for Push Token Consolidation

1. **Correct NotificationPushToken model** to match actual database schema:
   - Remove or conditionally handle `browser`, `platform` attributes
   - Align model with `push_notification_tokens` table structure
   - Update validation in controller if needed

2. **Delete PushNotificationToken.php** file

3. **Update any imports** (currently only in NotificationController, already correct)

4. **Create proper factory** for NotificationPushToken if needed for testing

---

## Part 2: Notification Preference/Setting Models

### Model Comparison

| Aspect | NotificationSetting | NotificationPreference |
|--------|---------------------|----------------------|
| **File Path** | `Modules/Notification/app/Models/NotificationSetting.php` | `Modules/Notification/app/Models/NotificationPreference.php` |
| **Namespace** | `Modules\Notification\Models` | `Modules\Notification\Models` |
| **Database Table** | `notification_settings` (confirmed in migration) | Unknown (no migration found) |
| **Factory** | Has `HasFactory` trait | None |
| **Fillable Fields** | `user_id`, `channel`, `notification_type`, `enabled` | `user_id`, `channel`, `notification_type`, `is_enabled`, `settings` |
| **Boolean Column** | `enabled` | `is_enabled` |
| **Extra Fields** | None | `settings` (JSON array) |
| **Methods** | `isEnabled()`, `getSettingsForUser()` | `isEnabled()`, `toggle()`, `forUser()` |
| **Scopes** | None | `enabled()`, `forChannel()`, `forType()` |
| **Casts Method** | Uses `$casts` property | Uses `casts()` method (Laravel 11+) |
| **PHPDoc** | Complete with property definitions | Minimal |
| **Used In** | Unknown (no references found) | `NotificationController.php` line 6, 136 |
| **User Relationship** | Yes, `belongsTo(User)` | Yes, `belongsTo(User)` |

### Database Schema Analysis

**Migration for NotificationSetting** (`2025_12_29_054242`):
```php
Schema::create('notification_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('notification_type');
    $table->boolean('email_enabled')->default(true);
    $table->boolean('push_enabled')->default(false);
    $table->boolean('in_app_enabled')->default(true);
    $table->string('frequency')->default('instant');
    $table->text('preferences')->nullable(); // JSON data
    $table->timestamp('opted_out_at')->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'notification_type']);
    $table->index('notification_type');
});
```

**Critical Issue**:
- NotificationSetting model has fields: `channel`, `notification_type`, `enabled`
- Database table has fields: `email_enabled`, `push_enabled`, `in_app_enabled`, `frequency`, `preferences`, `opted_out_at`
- **These don't match at all!** The model won't work with the actual migration.

**NotificationPreference Model** expects:
- `channel`, `notification_type`, `is_enabled`, `settings` (array)
- **No migration exists for this model**

### Production Usage

**NotificationController** (lines 6, 114, 136):
```php
use Modules\Notification\Models\NotificationPreference;

// Line 114
$preferences = $user->notificationPreferences()->get()->groupBy('channel');

// Line 136
NotificationPreference::toggle(
    $user->id,
    $pref['channel'],
    $pref['type'],
    $pref['enabled']
);
```

**User Model** (lines 2132-2135):
```php
public function notificationPreferences(): HasMany
{
    return $this->hasMany(\App\Models\Notifications\NotificationPreference::class);
}
```

### Recommendation

**❌ NEITHER MODEL IS CORRECT**

The situation is more serious here. There are **two incompatible approaches**:

**Option A: Use NotificationSetting as Base**
- Has actual migration with comprehensive schema
- Supports per-channel enablement (`email_enabled`, `push_enabled`, `in_app_enabled`)
- Tracks frequency preferences
- Tracks opt-out history
- **Issue**: Model definition doesn't match migration

**Option B: Create New NotificationPreference Table**
- NotificationPreference logic is already in use (current production code)
- Simpler channel/type/enabled model
- Supports JSON settings field for extensibility
- **Issue**: No migration exists yet

### Database Schema Problem

The `notification_settings` table schema is overly complex for what the code is trying to do. It has:
- **Channel-specific booleans** (`email_enabled`, `push_enabled`, `in_app_enabled`) - violates normalization
- **Frequency field** - separate concern, might belong on User settings
- **Opted-out timestamp** - for GDPR compliance but unused

This suggests incomplete schema design.

### Action Items for Preference/Setting Consolidation

**Choose Path A or B:**

#### Path A: Fix NotificationSetting to Match Migration
1. Update NotificationSetting model to reflect actual database columns
2. Refactor to handle per-channel enablement properly
3. Delete NotificationPreference.php
4. Update User model relationship
5. Update NotificationController to use NotificationSetting

**New NotificationSetting structure**:
```php
public static function isChannelEnabled(
    int $userId,
    string $channel,
    string $notificationType
): bool {
    $setting = static::where('user_id', $userId)
        ->where('notification_type', $notificationType)
        ->first();

    return match($channel) {
        'email' => $setting?->email_enabled ?? true,
        'push' => $setting?->push_enabled ?? false,
        'in_app' => $setting?->in_app_enabled ?? true,
        default => true,
    };
}
```

#### Path B: Create NotificationPreference Migration (Recommended)
1. Create new migration `create_notification_preferences_table`
2. Use NotificationPreference as the canonical model
3. Delete NotificationSetting.php and its migration
4. Keep NotificationPreference as-is (already correct)

**Recommended Schema**:
```php
Schema::create('notification_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('channel');  // email, push, in_app, sms
    $table->string('notification_type');
    $table->boolean('is_enabled')->default(true);
    $table->json('backups')->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'channel', 'notification_type']);
    $table->index(['user_id', 'channel']);
});
```

---

## Part 3: Consolidated Recommendation

### Models to Keep

| Model | Keep/Delete | Priority |
|-------|-----------|----------|
| **NotificationPushToken** | ✅ KEEP (FIX SCHEMA) | HIGH |
| **PushNotificationToken** | ❌ DELETE | MEDIUM |
| **NotificationSetting** | ❌ DELETE (vs PreferencePreference) | HIGH |
| **NotificationPreference** | ✅ KEEP (ADD MIGRATION) | HIGH |

### Recommended Consolidation Strategy

#### Phase 1: Immediate (High Priority)
1. **Push Tokens**: Delete `PushNotificationToken.php`
2. **Preferences**: Delete `NotificationSetting.php` and its migration
3. Create missing migration for `NotificationPreference`

#### Phase 2: Schema Corrections (Medium Priority)
1. **NotificationPushToken**: Update model to match existing `push_notification_tokens` table schema
2. **NotificationPreference**: Create and run migration for new `notification_preferences` table
3. Data migration if needed (unlikely, pre-launch)

#### Phase 3: Code Updates (Low Priority)
1. Ensure all imports use canonical models
2. Update NotificationController validation if needed
3. Add factory for NotificationPushToken

### File Deletions Required

```
DELETE: /Users/functionbytes/Function/Coding/manager/Modules/Notification/app/Models/PushNotificationToken.php
DELETE: /Users/functionbytes/Function/Coding/manager/database/migrations/2025_12_29_054242_create_notification_settings_table.php
DELETE: /Users/functionbytes/Function/Coding/manager/Modules/Notification/app/Models/NotificationSetting.php
```

### File Creations Required

```
CREATE: Migration for NotificationPreference table
UPDATE: NotificationPushToken model to fix schema mismatches
```

### Migration Impact

**No existing production data affected** (appears to be pre-launch):
- Both tables are empty or recently created
- No data migration scripts needed
- Safe to delete and recreate migrations

---

## Appendix: Quick Reference

### Import Paths Used in Production

```php
// NotificationController currently imports:
use Modules\Notification\Models\NotificationPreference;      ✅ KEEP
use Modules\Notification\Models\NotificationPushToken;       ✅ KEEP
```

### User Model Relationships

```php
public function notificationPreferences(): HasMany      ✅ KEEP
public function pushTokens(): HasMany                  ✅ KEEP (but fix model schema)
```

### Active Model Methods Called

```
NotificationPushToken::register()          ✅ KEEP
NotificationPushToken::deactivate()        ✅ KEEP
NotificationPreference::toggle()           ✅ KEEP
NotificationPreference::forUser()          ✅ KEEP
$user->pushTokens()                        ✅ KEEP
$user->notificationPreferences()           ✅ KEEP
```

---

## Next Steps

1. **Review this analysis** with team
2. **Choose consolidation strategy** (Path A or B for preferences)
3. **Execute Phase 1** deletions
4. **Create missing migration** (if Path B chosen)
5. **Fix schema mismatches** in NotificationPushToken
6. **Run tests** to verify functionality

---

**Note**: This is a pre-consolidation analysis only. No files have been modified.
