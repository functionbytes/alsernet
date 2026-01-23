# Technical Details: Notification Models Analysis

## File Locations

```
Models:
  /Modules/Notification/app/Models/NotificationPushToken.php      (KEEP)
  /Modules/Notification/app/Models/PushNotificationToken.php       (DELETE)
  /Modules/Notification/app/Models/NotificationPreference.php      (KEEP)
  /Modules/Notification/app/Models/NotificationSetting.php         (DELETE)

Migrations:
  /database/migrations/2025_12_29_054249_create_push_notification_tokens_table.php      (KEEP)
  /database/migrations/2025_12_29_054242_create_notification_settings_table.php         (DELETE)

Controllers:
  /Modules/Notification/app/Http/Controllers/Api/NotificationController.php

Related Models:
  /app/Models/User.php (contains relationships)
```

---

## Section 1: Push Notification Token Deep Dive

### Model 1: NotificationPushToken (KEEP)

**File**: `/Modules/Notification/app/Models/NotificationPushToken.php`

```php
<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'browser',           // ⚠️ NOT IN DATABASE
        'platform',          // ⚠️ NOT IN DATABASE
        'is_active',
        'last_used_at',
        'ip_address',        // ⚠️ NOT IN DATABASE
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Active production usage
    public static function register(int $userId, string $token, array $metadata = []): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'token' => $token,
            ],
            [
                'device_type' => $metadata['device_type'] ?? null,
                'browser' => $metadata['browser'] ?? null,         // ⚠️ PROBLEM
                'platform' => $metadata['platform'] ?? null,       // ⚠️ PROBLEM
                'ip_address' => $metadata['ip_address'] ?? request()->ip(),  // ⚠️ PROBLEM
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    // ✅ Used in production
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'last_used_at' => now(),
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    // Additional helper methods...
    public static function activeForUser(int $userId) { /*...*/ }
    public static function cleanup(int $daysInactive = 90): int { /*...*/ }
    public function scopeActive($query) { /*...*/ }
    public function scopeInactive($query) { /*...*/ }
    public function scopeRecentlyUsed($query, int $days = 30) { /*...*/ }
}
```

**Problems**:
- Lines 13, 14, 17 have attributes that don't exist in the database
- Will cause `Illuminate\Database\Eloquent\MassAssignmentException` when calling `register()`
- The database migration only has `device_type`, not `browser`, `platform`, `ip_address`

**Production Usage**:
```php
// NotificationController.php:163
$pushToken = NotificationPushToken::register(  // ❌ WILL FAIL HERE
    $user->id,
    $validated['token'],
    [
        'device_type' => $validated['device_type'] ?? null,
        'browser' => $validated['browser'] ?? null,              // ❌ EXTRA
        'platform' => $validated['platform'] ?? null,           // ❌ EXTRA
        'ip_address' => $request->ip(),                         // ❌ EXTRA
    ]
);
```

---

### Model 2: PushNotificationToken (DELETE)

**File**: `/Modules/Notification/app/Models/PushNotificationToken.php`

```php
<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationToken extends Model
{
    use HasFactory;  // ✅ Has factory (but not used)

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_id',        // Different field name
        'active',           // Uses 'active' not 'is_active'
        'last_used_at',
    ];

    protected $casts = [  // ⚠️ Deprecated style for Laravel 11+
        'active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);  // Different column name
    }

    public static function getActiveTokensForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->where('active', true)  // Different column name
            ->get();
    }
}
```

**Why Delete**:
- Completely unused in codebase
- No references in controllers, services, or User model
- Contradictory column names (`active` vs `is_active`, `device_id` vs `browser`/`platform`)
- Duplicate of NotificationPushToken functionality

**Search Results** (grep confirmed):
```
No references to PushNotificationToken found outside of:
  • Its own model file
  • Its own migration file
```

---

### Migration Mismatch: Expected vs Actual

**Actual Database Schema** (`push_notification_tokens` table):
```sql
CREATE TABLE push_notification_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL FOREIGN KEY,
    token VARCHAR(255) UNIQUE NOT NULL,
    device_type VARCHAR(255) NOT NULL,      -- web, ios, android
    device_name VARCHAR(255) NULLABLE,
    last_used_at TIMESTAMP NULLABLE,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_user_id (user_id)
);
```

**Model Expectations** (NotificationPushToken `$fillable`):
```php
'user_id',          ✅ EXISTS
'token',            ✅ EXISTS
'device_type',      ✅ EXISTS
'browser',          ❌ MISSING
'platform',         ❌ MISSING
'is_active',        ✅ EXISTS
'last_used_at',     ✅ EXISTS
'ip_address',       ❌ MISSING
```

**Fix**: Add columns to migration:
```php
// New migration: add_missing_columns_to_push_notification_tokens
public function up(): void
{
    Schema::table('push_notification_tokens', function (Blueprint $table) {
        $table->string('browser')->nullable()->after('device_type');
        $table->string('platform')->nullable()->after('browser');
        $table->string('ip_address')->nullable()->after('platform');
    });
}
```

---

## Section 2: Notification Preference Deep Dive

### Model 1: NotificationSetting (DELETE)

**File**: `/Modules/Notification/app/Models/NotificationSetting.php`

```php
<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;  // Has factory, but never instantiated

    protected $fillable = [
        'user_id',
        'channel',
        'notification_type',
        'enabled',           // ⚠️ WRONG - table has email_enabled, push_enabled, etc.
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function isEnabled(int $userId, string $channel, string $notificationType): bool
    {
        $setting = static::where('user_id', $userId)
            ->where('channel', $channel)                    // ⚠️ COLUMN DOESN'T EXIST
            ->where('notification_type', $notificationType)
            ->first();

        return $setting ? $setting->enabled : true;        // ⚠️ COLUMN DOESN'T EXIST
    }

    public static function getSettingsForUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->get()
            ->groupBy('notification_type')
            ->toArray();
    }
}
```

**Problems**:
- Model expects: `channel`, `notification_type`, `enabled`
- Database has: `email_enabled`, `push_enabled`, `in_app_enabled`, `frequency`, `preferences`, `opted_out_at`
- **Zero columns match between model and database**
- Will crash at runtime with "Unknown column" errors

---

### Model 2: NotificationPreference (KEEP)

**File**: `/Modules/Notification/app/Models/NotificationPreference.php`

```php
<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'notification_type',
        'is_enabled',
        'backups',          // JSON for extensibility
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'backups' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Active production usage
    public static function isEnabled(int $userId, string $channel, string $type): bool
    {
        $preference = static::where('user_id', $userId)
            ->where('channel', $channel)
            ->where('notification_type', $type)
            ->first();

        return $preference ? $preference->is_enabled : true;
    }

    // ✅ Active production usage
    public static function toggle(int $userId, string $channel, string $type, bool $enabled): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'channel' => $channel,
                'notification_type' => $type,
            ],
            ['is_enabled' => $enabled]
        );
    }

    public static function forUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->get()
            ->groupBy('channel')
            ->toArray();
    }

    // ✅ Useful scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }
}
```

**Status**:
- ✅ Used in production (NotificationController lines 114, 136)
- ✅ Has comprehensive helper methods
- ✅ Has useful query scopes
- ✅ Modern casts() method
- ❌ **Missing database migration**

**Production Usage**:
```php
// NotificationController.php:114
$preferences = $user->notificationPreferences()->get()->groupBy('channel');

// NotificationController.php:136
NotificationPreference::toggle(
    $user->id,
    $pref['channel'],
    $pref['type'],
    $pref['enabled']
);
```

---

### Missing Migration: NotificationPreference Table

**Current Status**: No migration exists

**Required Migration** (to be created):
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('channel');           // email, push, in_app, sms
            $table->string('notification_type'); // order.created, order.shipped, etc.
            $table->boolean('is_enabled')->default(true);
            $table->json('backups')->nullable(); // For extensibility

            $table->timestamps();

            // Ensure one preference per user+channel+type combination
            $table->unique(['user_id', 'channel', 'notification_type']);

            // Index for common queries
            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
```

**File should be named**:
`2025_12_30_XXXXXX_create_notification_preferences_table.php`
(where XXXXXX is a timestamp like `000000`, `120000`, etc.)

---

### Unwanted Migration: NotificationSetting Table

**Current Status**: Migration exists but should be removed

**Migration File**: `/database/migrations/2025_12_29_054242_create_notification_settings_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('notification_type');
            $table->boolean('email_enabled')->default(true);      // Not normalized
            $table->boolean('push_enabled')->default(false);      // Not normalized
            $table->boolean('in_app_enabled')->default(true);     // Not normalized
            $table->string('frequency')->default('instant');      // Should be separate
            $table->text('preferences')->nullable();              // JSON
            $table->timestamp('opted_out_at')->nullable();        // GDPR
            $table->timestamps();
            $table->unique(['user_id', 'notification_type']);
            $table->index('notification_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
```

**Why Remove**:
- Model (NotificationSetting) is unused
- Schema design violates normalization (channel-specific booleans)
- NotificationPreference is preferred approach
- Causes confusion with duplicate models

---

## Section 3: User Model Relationships

**File**: `/app/Models/User.php`

```php
class User extends Authenticatable
{
    // ...existing code...

    /**
     * Preferencias de notificaciones del usuario
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(
            \Modules\Notification\Models\NotificationPreference::class
        );
    }

    /**
     * Tokens de push notifications del usuario
     */
    public function pushTokens(): HasMany
    {
        return $this->hasMany(
            \Modules\Notification\Models\NotificationPushToken::class
        );
    }

    /**
     * Verificar si el usuario puede recibir notificaciones en un canal específico
     */
    public function canReceiveNotification(string $channel, string $type): bool
    {
        // Implementation...
    }
}
```

**Status**:
- ✅ Both relationships correctly map to KEEP models
- ✅ Will work once migrations are created/fixed
- ⚠️ Currently will fail if code tries to access relationships (table doesn't exist)

---

## Section 4: NotificationController Usage

**File**: `/Modules/Notification/app/Http/Controllers/Api/NotificationController.php`

### Imports (Lines 1-9)

```php
<?php

namespace Modules\Notification\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Models\NotificationPreference;   // ✅ CORRECT
use Modules\Notification\Models\NotificationPushToken;    // ✅ CORRECT
```

### registerPushToken Method (Lines 152-178)

```php
public function registerPushToken(Request $request): JsonResponse
{
    $user = $request->user();

    $validated = $request->validate([
        'token' => 'required|string',
        'device_type' => 'nullable|string',
        'browser' => 'nullable|string',
        'platform' => 'nullable|string',
    ]);

    // ⚠️ PROBLEM: These attributes don't exist in database
    $pushToken = NotificationPushToken::register(
        $user->id,
        $validated['token'],
        [
            'device_type' => $validated['device_type'] ?? null,
            'browser' => $validated['browser'] ?? null,          // ❌ Missing column
            'platform' => $validated['platform'] ?? null,        // ❌ Missing column
            'ip_address' => $request->ip(),                     // ❌ Missing column
        ]
    );

    return response()->json([
        'message' => 'Push token registered successfully',
        'token_id' => $pushToken->id,
    ]);
}
```

### updatePreferences Method (Lines 124-147)

```php
public function updatePreferences(Request $request): JsonResponse
{
    $user = $request->user();

    $validated = $request->validate([
        'preferences' => 'required|array',
        'preferences.*.channel' => 'required|in:in_app,push,email,sms',
        'preferences.*.type' => 'required|string',
        'preferences.*.enabled' => 'required|boolean',
    ]);

    foreach ($validated['preferences'] as $pref) {
        // ✅ This will work once migration is created
        NotificationPreference::toggle(
            $user->id,
            $pref['channel'],
            $pref['type'],
            $pref['enabled']
        );
    }

    return response()->json([
        'message' => 'Preferences updated successfully',
    ]);
}
```

---

## Summary of Required Actions

### To Fix Push Notification Token:

1. **Delete file**: `PushNotificationToken.php`
2. **Update migration**: Add missing columns
   ```sql
   ALTER TABLE push_notification_tokens
   ADD COLUMN browser VARCHAR(255),
   ADD COLUMN platform VARCHAR(255),
   ADD COLUMN ip_address VARCHAR(255);
   ```

### To Fix Notification Preference:

1. **Delete file**: `NotificationSetting.php`
2. **Delete migration**: `create_notification_settings_table.php`
3. **Create migration**: `create_notification_preferences_table.php`
4. **Run migrations**: `php artisan migrate`

### Total Changes:
- **Files to delete**: 3
- **Migrations to delete**: 1
- **Migrations to create**: 1
- **Models to update**: 0
- **Models to delete**: 2

---

## Testing Checklist

After consolidation:
```
☐ php artisan migrate
☐ php artisan test
☐ POST /api/notifications/push-token (registerPushToken)
☐ POST /api/notifications/preferences (updatePreferences)
☐ GET /api/notifications/preferences (getPreferences)
☐ User model: $user->pushTokens()->count()
☐ User model: $user->notificationPreferences()->count()
☐ Factory tests with NotificationPushToken
```

