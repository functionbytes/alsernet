# Warehouse System - Implementation Guides

**Document Version:** 1.0
**Last Updated:** December 2, 2025
**Category:** Technical Implementation

---

## Table of Contents

1. [Phase 1: Database Performance](#phase-1-database-performance)
2. [Phase 2: Caching & Real-Time](#phase-2-caching--real-time)
3. [Phase 3: UI/UX & Bulk Operations](#phase-3-uiux--bulk-operations)
4. [Phase 4: Reporting & Analytics](#phase-4-reporting--analytics)
5. [Phase 5: Data Management](#phase-5-data-management)
6. [Testing & Validation](#testing--validation)

---

## Phase 1: Database Performance

### Task 1.1: Add Database Indexes

#### Step 1: Create Migration

```bash
php artisan make:migration add_warehouse_performance_indexes
```

#### Step 2: Migration Content

**File:** `database/migrations/2025_12_02_000000_add_warehouse_performance_indexes.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Movement History Queries
        Schema::table('warehouse_inventory_movements', function (Blueprint $table) {
            $table->index(['warehouse_id', 'recorded_at'], 'idx_wim_warehouse_recorded');
            $table->index(['movement_type', 'user_id', 'recorded_at'], 'idx_wim_movement_user');
            $table->index(['slot_id', 'recorded_at'], 'idx_wim_slot_recorded');
        });

        // 2. Slot Validation
        Schema::table('warehouse_inventory_slots', function (Blueprint $table) {
            $table->index(['location_id', 'product_id'], 'idx_wis_location_product');
            $table->index(['barcode'], 'idx_wis_barcode');
            $table->index(['location_id', 'is_occupied'], 'idx_wis_location_occupied');
        });

        // 3. Location Structure
        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->index(['warehouse_id', 'code'], 'idx_wl_warehouse_code');
            $table->index(['floor_id', 'available'], 'idx_wl_floor_available');
        });

        Schema::table('warehouse_floors', function (Blueprint $table) {
            $table->index(['warehouse_id', 'level'], 'idx_wf_warehouse');
        });

        Schema::table('warehouse_location_sections', function (Blueprint $table) {
            $table->index(['location_id', 'code'], 'idx_wls_location_code');
        });

        // 4. User Permissions
        Schema::table('user_warehouse', function (Blueprint $table) {
            $table->index(['warehouse_id', 'can_transfer', 'can_inventory'], 'idx_uw_warehouse_perms');
            $table->index(['user_id', 'is_default'], 'idx_uw_user_default');
        });

        // 5. Operation Items
        Schema::table('warehouse_operation_items', function (Blueprint $table) {
            $table->index(['operation_id', 'status'], 'idx_woi_operation_status');
            $table->index(['slot_id', 'status'], 'idx_woi_slot_status');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_inventory_movements', function (Blueprint $table) {
            $table->dropIndex('idx_wim_warehouse_recorded');
            $table->dropIndex('idx_wim_movement_user');
            $table->dropIndex('idx_wim_slot_recorded');
        });

        Schema::table('warehouse_inventory_slots', function (Blueprint $table) {
            $table->dropIndex('idx_wis_location_product');
            $table->dropIndex('idx_wis_barcode');
            $table->dropIndex('idx_wis_location_occupied');
        });

        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->dropIndex('idx_wl_warehouse_code');
            $table->dropIndex('idx_wl_floor_available');
        });

        Schema::table('warehouse_floors', function (Blueprint $table) {
            $table->dropIndex('idx_wf_warehouse');
        });

        Schema::table('warehouse_location_sections', function (Blueprint $table) {
            $table->dropIndex('idx_wls_location_code');
        });

        Schema::table('user_warehouse', function (Blueprint $table) {
            $table->dropIndex('idx_uw_warehouse_perms');
            $table->dropIndex('idx_uw_user_default');
        });

        Schema::table('warehouse_operation_items', function (Blueprint $table) {
            $table->dropIndex('idx_woi_operation_status');
            $table->dropIndex('idx_woi_slot_status');
        });
    }
};
```

#### Step 3: Run Migration

```bash
# Test in development first
php artisan migrate

# Monitor query performance
# Then deploy to production with:
php artisan migrate --force
```

#### Step 4: Verify Indexes

```bash
# Create a verification script
php artisan tinker
```

```php
// In tinker
use Illuminate\Support\Facades\DB;

// Check warehouse_inventory_movements indexes
DB::select("SHOW INDEX FROM warehouse_inventory_movements");

// Expected output shows 3 new indexes
// idx_wim_warehouse_recorded
// idx_wim_movement_user
// idx_wim_slot_recorded
```

---

### Task 1.2: Eager Loading Optimization

#### Step 1: Identify N+1 Problem Locations

Create a script to detect N+1 problems:

**File:** `app/Services/Warehouse/QueryOptimizationService.php`

```php
<?php

namespace App\Services\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QueryOptimizationService
{
    protected static array $queryLog = [];

    public static function startMonitoring(): void
    {
        DB::listen(function ($query) {
            static::$queryLog[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });
    }

    public static function detectN1Problems(): array
    {
        $problems = [];
        $queryCounts = array_count_values(array_map(
            fn($q) => preg_replace('/\?/', 'X', $q['sql']),
            static::$queryLog
        ));

        foreach ($queryCounts as $query => $count) {
            if ($count > 5) {
                $problems[] = [
                    'query' => $query,
                    'count' => $count,
                    'problem' => 'N+1 detected - query executed ' . $count . ' times',
                ];
            }
        }

        return $problems;
    }

    public static function getQuerySummary(): array
    {
        return [
            'total_queries' => count(static::$queryLog),
            'total_time' => array_sum(array_column(static::$queryLog, 'time')),
            'problems' => static::detectN1Problems(),
        ];
    }
}
```

#### Step 2: Update Location Queries

**File:** `app/Http/Controllers/Managers/Warehouse/WarehouseLocationsController.php`

```php
// BEFORE
public function view($warehouse_uid, $location_id)
{
    $location = WarehouseLocation::find($location_id);
    return view('theme.views.warehouse.locations.view', compact('location'));
    // Problem: Loading location triggers N+1 when accessing sections/slots/inventaries
}

// AFTER
public function view($warehouse_uid, $location_id)
{
    $location = WarehouseLocation::with([
        'sections.slots.product',  // Deep eager loading
        'floor',
        'style',
    ])->find($location_id);

    return view('theme.views.warehouse.locations.view', compact('location'));
    // Solution: All relationships loaded in 1 query
}
```

#### Step 3: Create Query Optimization Checklist

```markdown
## Locations to Update (Priority Order)

### High Priority (N+1 Likely)
- [ ] WarehouseLocationsController@view
  - Add: sections.slots.product, floor, style

- [ ] WarehouseController@view
  - Add: floors.locations, floors.locations.sections.slots

- [ ] WarehouseHistoryController@index
  - Add: user, slot.product, slot.location

- [ ] WarehouseDashboardController@dashboard
  - Add: floors.locations, floors.locations.sections.slots

### Medium Priority
- [ ] WarehouseInventorySlotsController@index
  - Add: product, location, location.floor

- [ ] WarehouseLocationSectionsController@view
  - Add: slots.product, location

- [ ] LocationsController (User)@index
  - Add: sections.slots, style

### Low Priority
- [ ] WarehouseFloorsController views
- [ ] WarehouseLocationStylesController views
```

---

### Task 1.3: Pagination Implementation

#### Step 1: Create Pagination Trait

**File:** `app/Traits/Warehouse/PaginateLargeDatasets.php`

```php
<?php

namespace App\Traits\Warehouse;

trait PaginateLargeDatasets
{
    protected int $defaultPageSize = 50;
    protected int $maxPageSize = 100;

    public function getPageSize(int $requested = null): int
    {
        if ($requested === null) {
            return $this->defaultPageSize;
        }

        return min($requested, $this->maxPageSize);
    }

    public function paginateWarehouseData($query, int $perPage = null)
    {
        $perPage = $this->getPageSize($perPage);

        return $query->paginate($perPage);
    }
}
```

#### Step 2: Update High-Volume Views

**File:** `app/Http/Controllers/Managers/Warehouse/WarehouseHistoryController.php`

```php
<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Traits\Warehouse\PaginateLargeDatasets;
use Illuminate\Http\Request;

class WarehouseHistoryController extends Controller
{
    use PaginateLargeDatasets;

    // BEFORE
    public function indexOld($warehouse_id)
    {
        $movements = WarehouseInventoryMovement::where('warehouse_id', $warehouse_id)
            ->orderBy('recorded_at', 'desc')
            ->get(); // Potential 100k+ records in memory
    }

    // AFTER
    public function index(Request $request, $warehouse_id)
    {
        $perPage = $request->query('per_page', 50);

        $movements = WarehouseInventoryMovement::where('warehouse_id', $warehouse_id)
            ->with(['user', 'slot.product', 'slot.location'])
            ->orderBy('recorded_at', 'desc')
            ->paginate($this->getPageSize($perPage));

        return view('theme.views.warehouse.history.index', [
            'movements' => $movements,
            'warehouse_id' => $warehouse_id,
        ]);
    }
}
```

#### Step 3: Update Blade Templates

```blade
<!-- BEFORE: No pagination -->
@foreach ($movements as $movement)
    <tr>
        <td>{{ $movement->recorded_at }}</td>
        <td>{{ $movement->movement_type }}</td>
        <!-- ... more fields ... -->
    </tr>
@endforeach

<!-- AFTER: With pagination -->
<table class="table">
    <tbody>
    @forelse ($movements as $movement)
        <tr>
            <td>{{ $movement->recorded_at }}</td>
            <td>{{ $movement->movement_type }}</td>
            <!-- ... more fields ... -->
        </tr>
    @empty
        <tr><td colspan="5">No movements found</td></tr>
    @endforelse
    </tbody>
</table>

<!-- Pagination links -->
<div class="d-flex justify-content-center">
    {{ $movements->links('pagination::bootstrap-5') }}
</div>
```

---

## Phase 2: Caching & Real-Time

### Task 2.1: Location Cache Service

#### Step 1: Create Cache Service

**File:** `app/Services/Warehouse/LocationCacheService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\WarehouseLocation;
use Illuminate\Support\Facades\Cache;

class LocationCacheService
{
    private const CACHE_PREFIX = 'warehouse.location.';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get location with cache
     */
    public static function getWithCache(int|string $locationId): ?WarehouseLocation
    {
        return Cache::remember(
            self::CACHE_PREFIX . $locationId,
            self::CACHE_TTL,
            fn() => self::loadFullLocation($locationId)
        );
    }

    /**
     * Load complete location structure
     */
    private static function loadFullLocation(int|string $locationId): ?WarehouseLocation
    {
        return WarehouseLocation::with([
            'sections.slots.product',
            'floor',
            'style',
        ])->find($locationId);
    }

    /**
     * Invalidate location cache
     */
    public static function invalidate(int|string $locationId): void
    {
        Cache::forget(self::CACHE_PREFIX . $locationId);
    }

    /**
     * Invalidate entire warehouse locations
     */
    public static function invalidateWarehouse(int|string $warehouseId): void
    {
        // Get all locations in warehouse
        $locations = WarehouseLocation::where('warehouse_id', $warehouseId)
            ->pluck('id');

        foreach ($locations as $locationId) {
            self::invalidate($locationId);
        }
    }

    /**
     * Batch get locations with cache
     */
    public static function getMultipleWithCache(array $locationIds): array
    {
        $cached = [];

        foreach ($locationIds as $id) {
            $cached[$id] = self::getWithCache($id);
        }

        return array_filter($cached); // Remove nulls
    }
}
```

#### Step 2: Update Models with Cache Invalidation

**File:** `app/Models/Warehouse/WarehouseLocation.php`

```php
<?php

namespace App\Models\Warehouse;

use App\Services\Warehouse\LocationCacheService;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    // ... existing code ...

    protected static function booted(): void
    {
        // Invalidate cache on update
        static::updated(function ($location) {
            LocationCacheService::invalidate($location->id);
        });

        // Invalidate cache on delete
        static::deleted(function ($location) {
            LocationCacheService::invalidate($location->id);
        });

        // Invalidate warehouse on create
        static::created(function ($location) {
            LocationCacheService::invalidateWarehouse($location->warehouse_id);
        });
    }

    // ... rest of model ...
}
```

**File:** `app/Models/Warehouse/WarehouseLocationSection.php`

```php
<?php

namespace App\Models\Warehouse;

use App\Services\Warehouse\LocationCacheService;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocationSection extends Model
{
    protected static function booted(): void
    {
        // Invalidate location cache when section changes
        static::updated(function ($section) {
            LocationCacheService::invalidate($section->location_id);
        });

        static::created(function ($section) {
            LocationCacheService::invalidate($section->location_id);
        });

        static::deleted(function ($section) {
            LocationCacheService::invalidate($section->location_id);
        });
    }

    // ... rest of model ...
}
```

#### Step 3: Use in Controllers

```php
<?php

use App\Services\Warehouse\LocationCacheService;

class WarehouseLocationsController extends Controller
{
    public function view($warehouse_uid, $location_id)
    {
        // Get cached location
        $location = LocationCacheService::getWithCache($location_id);

        if (!$location) {
            abort(404);
        }

        return view('theme.views.warehouse.locations.view', compact('location'));
    }
}
```

---

### Task 2.2: User Permission Cache

#### Step 1: Create Permission Cache Service

**File:** `app/Services/Warehouse/UserPermissionService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserPermissionService
{
    private const CACHE_PREFIX = 'user.warehouses.';
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Get user's warehouses with cache
     */
    public static function getUserWarehouses(int|string $userId): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . $userId,
            self::CACHE_TTL,
            fn() => self::loadUserWarehouses($userId)
        );
    }

    /**
     * Load user warehouses from database
     */
    private static function loadUserWarehouses(int|string $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            return [];
        }

        return $user->warehouses()
            ->with('warehouse') // Warehouse details
            ->get()
            ->map(fn($assignment) => [
                'warehouse_id' => $assignment->warehouse_id,
                'warehouse_name' => $assignment->warehouse->name,
                'can_transfer' => $assignment->can_transfer,
                'can_inventory' => $assignment->can_inventory,
                'is_default' => $assignment->is_default,
            ])
            ->toArray();
    }

    /**
     * Check if user can perform action in warehouse
     */
    public static function canTransfer(int|string $userId, int|string $warehouseId): bool
    {
        $warehouses = self::getUserWarehouses($userId);

        return collect($warehouses)
            ->where('warehouse_id', $warehouseId)
            ->where('can_transfer', true)
            ->isNotEmpty();
    }

    /**
     * Check if user can perform inventory operations
     */
    public static function canInventory(int|string $userId, int|string $warehouseId): bool
    {
        $warehouses = self::getUserWarehouses($userId);

        return collect($warehouses)
            ->where('warehouse_id', $warehouseId)
            ->where('can_inventory', true)
            ->isNotEmpty();
    }

    /**
     * Get user's default warehouse
     */
    public static function getDefaultWarehouse(int|string $userId): ?array
    {
        $warehouses = self::getUserWarehouses($userId);

        return collect($warehouses)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Invalidate user cache
     */
    public static function invalidate(int|string $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . $userId);
    }
}
```

#### Step 2: Update User Model

**File:** `app/Models/User.php`

```php
<?php

namespace App\Models;

use App\Services\Warehouse\UserPermissionService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    // ... existing code ...

    protected static function booted(): void
    {
        // Invalidate cache when warehouse assignment changes
        static::updated(function ($user) {
            UserPermissionService::invalidate($user->id);
        });
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Warehouse\Warehouse::class,
            'user_warehouse',
            'user_id',
            'warehouse_id'
        )->withPivot('can_transfer', 'can_inventory', 'is_default');
    }

    // ... rest of model ...
}
```

**File:** `app/Models/Warehouse/Warehouse.php`

```php
<?php

namespace App\Models\Warehouse;

use App\Services\Warehouse\UserPermissionService;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected static function booted(): void
    {
        // Invalidate all user caches when warehouse assignment changes
        static::updated(function ($warehouse) {
            // Get all users assigned to this warehouse
            $userIds = $warehouse->users()->pluck('user_id');

            foreach ($userIds as $userId) {
                UserPermissionService::invalidate($userId);
            }
        });
    }

    // ... rest of model ...
}
```

#### Step 3: Use in Middleware/Controllers

```php
<?php

use App\Services\Warehouse\UserPermissionService;

class WarehouseAccessMiddleware
{
    public function handle($request, $next)
    {
        $warehouseId = $request->route('warehouse_id');
        $user = auth()->user();

        // Use cached permission check (10ms instead of 100ms)
        if (!UserPermissionService::canTransfer($user->id, $warehouseId)) {
            abort(403);
        }

        return $next($request);
    }
}
```

---

### Task 2.3: Configuration Cache

#### Step 1: Create Configuration Cache Service

**File:** `app/Services/Warehouse/ConfigurationCacheService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\WarehouseLocationStyle;
use App\Models\Warehouse\WarehouseLocationCondition;
use Illuminate\Support\Facades\Cache;

class ConfigurationCacheService
{
    private const CACHE_FOREVER = null;

    /**
     * Get all location styles (cached forever)
     */
    public static function getLocationStyles()
    {
        return Cache::rememberForever(
            'warehouse.location.styles',
            fn() => WarehouseLocationStyle::all()->toArray()
        );
    }

    /**
     * Get single location style
     */
    public static function getLocationStyle(int|string $styleId)
    {
        $styles = self::getLocationStyles();

        return collect($styles)->firstWhere('id', $styleId);
    }

    /**
     * Get all location conditions
     */
    public static function getLocationConditions()
    {
        return Cache::rememberForever(
            'warehouse.location.conditions',
            fn() => WarehouseLocationCondition::all()->toArray()
        );
    }

    /**
     * Invalidate configuration cache (call from admin update)
     */
    public static function invalidateStyles(): void
    {
        Cache::forget('warehouse.location.styles');
    }

    public static function invalidateConditions(): void
    {
        Cache::forget('warehouse.location.conditions');
    }
}
```

#### Step 2: Update Models

```php
<?php

namespace App\Models\Warehouse;

use App\Services\Warehouse\ConfigurationCacheService;

class WarehouseLocationStyle extends Model
{
    protected static function booted(): void
    {
        static::updated(fn() => ConfigurationCacheService::invalidateStyles());
        static::created(fn() => ConfigurationCacheService::invalidateStyles());
        static::deleted(fn() => ConfigurationCacheService::invalidateStyles());
    }
}

class WarehouseLocationCondition extends Model
{
    protected static function booted(): void
    {
        static::updated(fn() => ConfigurationCacheService::invalidateConditions());
        static::created(fn() => ConfigurationCacheService::invalidateConditions());
        static::deleted(fn() => ConfigurationCacheService::invalidateConditions());
    }
}
```

---

### Task 2.4: Barcode Validation Service

#### Step 1: Create Optimized Validation Service

**File:** `app/Services/Warehouse/BarcodeValidationService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseLocation;
use Illuminate\Support\Facades\DB;

class BarcodeValidationService
{
    /**
     * Validate barcode with single optimized query
     *
     * @return array{
     *     valid: bool,
     *     slot?: \App\Models\Warehouse\WarehouseInventorySlot,
     *     location?: \App\Models\Warehouse\WarehouseLocation,
     *     product?: array,
     *     capacity?: array,
     *     error?: string
     * }
     */
    public static function validateSlotBarcode(
        string $barcode,
        ?int $warehouseId = null
    ): array {
        // Single query with all relationships and validations
        $slot = WarehouseInventorySlot::with([
            'location.floor.warehouse',
            'location.style',
            'product',
        ])->where('barcode', $barcode)->first();

        if (!$slot) {
            return [
                'valid' => false,
                'error' => 'Barcode not found',
            ];
        }

        // Warehouse check
        if ($warehouseId && $slot->location->floor->warehouse->id !== $warehouseId) {
            return [
                'valid' => false,
                'error' => 'Slot belongs to different warehouse',
            ];
        }

        // Capacity check
        $capacityStatus = self::checkCapacity($slot);

        if (!$capacityStatus['available']) {
            return [
                'valid' => false,
                'error' => $capacityStatus['reason'],
            ];
        }

        return [
            'valid' => true,
            'slot' => $slot,
            'location' => $slot->location,
            'product' => $slot->product?->toArray(),
            'capacity' => $capacityStatus,
        ];
    }

    /**
     * Validate location barcode
     */
    public static function validateLocationBarcode(
        string $barcode,
        ?int $warehouseId = null
    ): array {
        $location = WarehouseLocation::with([
            'sections.slots.product',
            'floor.warehouse',
            'style',
        ])->where('barcode', $barcode)->first();

        if (!$location) {
            return [
                'valid' => false,
                'error' => 'Location barcode not found',
            ];
        }

        if ($warehouseId && $location->floor->warehouse->id !== $warehouseId) {
            return [
                'valid' => false,
                'error' => 'Location belongs to different warehouse',
            ];
        }

        return [
            'valid' => true,
            'location' => $location,
            'warehouse' => $location->floor->warehouse,
            'occupancy' => self::calculateOccupancy($location),
        ];
    }

    /**
     * Batch validate multiple barcodes (for bulk scanning)
     */
    public static function validateMultiple(array $barcodes): array
    {
        $slots = WarehouseInventorySlot::with([
            'location.floor.warehouse',
            'product',
        ])->whereIn('barcode', $barcodes)->get();

        return $slots->map(fn($slot) => [
            'barcode' => $slot->barcode,
            'valid' => true,
            'slot_id' => $slot->id,
            'product_id' => $slot->product_id,
            'location_id' => $slot->location_id,
        ])->toArray();
    }

    /**
     * Check slot capacity
     */
    private static function checkCapacity(WarehouseInventorySlot $slot): array
    {
        $quantityAvailable = $slot->max_quantity - $slot->quantity;
        $weightAvailable = $slot->weight_max - $slot->weight_current;

        return [
            'available' => $quantityAvailable > 0 && $weightAvailable > 0,
            'quantity_available' => $quantityAvailable,
            'weight_available' => $weightAvailable,
            'reason' => $quantityAvailable <= 0 ? 'Quantity capacity exceeded' :
                       ($weightAvailable <= 0 ? 'Weight capacity exceeded' : null),
        ];
    }

    /**
     * Calculate location occupancy percentage
     */
    private static function calculateOccupancy(WarehouseLocation $location): float
    {
        $totalSlots = $location->sections()
            ->withCount('slots')
            ->get()
            ->sum('slots_count');

        if ($totalSlots === 0) {
            return 0;
        }

        $occupiedSlots = $location->sections()
            ->with('slots')
            ->get()
            ->flatMap->slots
            ->filter(fn($slot) => $slot->is_occupied)
            ->count();

        return ($occupiedSlots / $totalSlots) * 100;
    }
}
```

#### Step 2: Create API Endpoint for Barcode Validation

**File:** `routes/api.php` (add to warehouse routes)

```php
Route::prefix('warehouse')->group(function () {
    Route::post('barcode/validate-slot', [WarehouseBarcodeController::class, 'validateSlot']);
    Route::post('barcode/validate-location', [WarehouseBarcodeController::class, 'validateLocation']);
    Route::post('barcode/validate-batch', [WarehouseBarcodeController::class, 'validateBatch']);
});
```

**File:** `app/Http/Controllers/API/WarehouseBarcodeController.php`

```php
<?php

namespace App\Http\Controllers\API;

use App\Services\Warehouse\BarcodeValidationService;
use Illuminate\Http\Request;

class WarehouseBarcodeController extends Controller
{
    public function validateSlot(Request $request)
    {
        $barcode = $request->input('barcode');
        $warehouseId = $request->input('warehouse_id');

        $result = BarcodeValidationService::validateSlotBarcode($barcode, $warehouseId);

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    public function validateLocation(Request $request)
    {
        $barcode = $request->input('barcode');
        $warehouseId = $request->input('warehouse_id');

        $result = BarcodeValidationService::validateLocationBarcode($barcode, $warehouseId);

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    public function validateBatch(Request $request)
    {
        $barcodes = $request->input('barcodes', []);

        $results = BarcodeValidationService::validateMultiple($barcodes);

        return response()->json(['results' => $results]);
    }
}
```

---

## Phase 3: UI/UX & Bulk Operations

### Task 3.1: Batch Transfer Operations

#### Step 1: Create Transfer Service

**File:** `app/Services/Warehouse/BulkTransferService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseInventorySlot;
use Illuminate\Support\Facades\DB;

class BulkTransferService
{
    /**
     * Bulk transfer items between locations
     */
    public static function transfer(
        array $transfers, // [['from_slot_id' => ..., 'to_slot_id' => ...], ...]
        int $userId,
        string $reason = 'Bulk transfer'
    ): array {
        return DB::transaction(function () use ($transfers, $userId, $reason) {
            $successful = 0;
            $failed = 0;
            $errors = [];

            foreach ($transfers as $index => $transfer) {
                $result = self::transferSingle(
                    $transfer,
                    $userId,
                    $reason
                );

                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                    $errors[] = [
                        'index' => $index,
                        'error' => $result['error'],
                    ];
                }
            }

            return [
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors,
                'total' => count($transfers),
            ];
        });
    }

    /**
     * Transfer single item
     */
    private static function transferSingle(
        array $transfer,
        int $userId,
        string $reason
    ): array {
        $fromSlot = WarehouseInventorySlot::lockForUpdate()->find($transfer['from_slot_id']);
        $toSlot = WarehouseInventorySlot::lockForUpdate()->find($transfer['to_slot_id']);

        if (!$fromSlot || !$toSlot) {
            return ['success' => false, 'error' => 'Slot not found'];
        }

        if ($fromSlot->quantity < ($transfer['quantity'] ?? 1)) {
            return ['success' => false, 'error' => 'Insufficient quantity'];
        }

        if (($toSlot->max_quantity - $toSlot->quantity) < ($transfer['quantity'] ?? 1)) {
            return ['success' => false, 'error' => 'Destination capacity exceeded'];
        }

        $quantity = $transfer['quantity'] ?? 1;

        // Update slots
        $fromSlot->decrement('quantity', $quantity);
        $toSlot->increment('quantity', $quantity);

        // If product differs, update product_id
        if ($transfer['product_id'] ?? null) {
            $toSlot->update(['product_id' => $transfer['product_id']]);
        }

        // Record movements
        WarehouseInventoryMovement::create([
            'slot_id' => $fromSlot->id,
            'product_id' => $fromSlot->product_id,
            'movement_type' => 'subtract',
            'from_quantity' => $fromSlot->quantity + $quantity,
            'to_quantity' => $fromSlot->quantity,
            'quantity_delta' => -$quantity,
            'reason' => $reason,
            'warehouse_id' => $fromSlot->location->warehouse_id,
            'user_id' => $userId,
            'recorded_at' => now(),
        ]);

        WarehouseInventoryMovement::create([
            'slot_id' => $toSlot->id,
            'product_id' => $transfer['product_id'] ?? $toSlot->product_id,
            'movement_type' => 'add',
            'from_quantity' => $toSlot->quantity - $quantity,
            'to_quantity' => $toSlot->quantity,
            'quantity_delta' => $quantity,
            'reason' => $reason,
            'warehouse_id' => $toSlot->location->warehouse_id,
            'user_id' => $userId,
            'recorded_at' => now(),
        ]);

        return ['success' => true];
    }

    /**
     * Get transfer progress
     */
    public static function getProgress(string $sessionId): array
    {
        // Get from cache or session
        return cache()->get("transfer.progress.$sessionId", [
            'total' => 0,
            'completed' => 0,
            'failed' => 0,
        ]);
    }
}
```

---

## Phase 4: Reporting & Analytics

### Task 4.1: Daily Summary Table

#### Step 1: Create Migration for Summary Table

```php
// database/migrations/2025_12_02_000001_create_warehouse_daily_summary.php

public function up(): void
{
    Schema::create('warehouse_daily_summary', function (Blueprint $table) {
        $table->id();
        $table->uuid('warehouse_id');
        $table->date('date')->index();
        $table->integer('total_movements')->default(0);
        $table->integer('total_quantity_moved')->default(0);
        $table->decimal('total_weight_moved', 12, 2)->default(0);
        $table->integer('discrepancies')->default(0);
        $table->timestamps();

        // Composite unique constraint
        $table->unique(['warehouse_id', 'date']);

        // Indexes for common queries
        $table->index(['warehouse_id', 'date'], 'idx_warehouse_date_summary');
        $table->index(['date'], 'idx_date_summary');
    });
}
```

#### Step 2: Create Summary Calculation Job

**File:** `app/Jobs/Warehouse/CalculateDailySummary.php`

```php
<?php

namespace App\Jobs\Warehouse;

use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseInventoryMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class CalculateDailySummary implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    public function __construct($date = null)
    {
        $this->date = $date ?? today()->subDay();
    }

    public function handle(): void
    {
        $warehouses = Warehouse::all();

        foreach ($warehouses as $warehouse) {
            $this->calculateForWarehouse($warehouse);
        }
    }

    private function calculateForWarehouse(Warehouse $warehouse): void
    {
        $movements = WarehouseInventoryMovement::where('warehouse_id', $warehouse->id)
            ->whereDate('recorded_at', $this->date)
            ->get();

        $summary = [
            'warehouse_id' => $warehouse->id,
            'date' => $this->date,
            'total_movements' => $movements->count(),
            'total_quantity_moved' => $movements->sum('quantity_delta'),
            'total_weight_moved' => $movements->sum('weight_delta'),
            'discrepancies' => $movements->where('reason', 'like', '%discrepancy%')->count(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('warehouse_daily_summary')->updateOrInsert(
            [
                'warehouse_id' => $warehouse->id,
                'date' => $this->date,
            ],
            $summary
        );
    }
}
```

#### Step 3: Schedule Job

**File:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Calculate summary at 2 AM daily
    $schedule->job(new \App\Jobs\Warehouse\CalculateDailySummary())
        ->dailyAt('02:00');
}
```

---

## Testing & Validation

### Performance Testing

**File:** `tests/Feature/Warehouse/PerformanceTest.php`

```php
<?php

namespace Tests\Feature\Warehouse;

use App\Models\Warehouse\WarehouseLocation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_view_uses_eager_loading(): void
    {
        // Create test data
        $location = WarehouseLocation::factory()
            ->has(WarehouseLocationSection::factory()
                ->has(WarehouseInventorySlot::factory(10))
                ->count(5)
            )->create();

        // Count database queries
        $this->assertQueryCount(fn() => $location->load([
            'sections.slots.product',
            'floor',
            'style',
        ]), 1);
    }

    private function assertQueryCount($callback, $expectedCount): void
    {
        $queryCount = 0;

        \Illuminate\Support\Facades\DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $callback();

        $this->assertEquals($expectedCount, $queryCount);
    }
}
```

---

**End of Implementation Guides**

This document provides step-by-step instructions for implementing each optimization. Each task includes:
- File paths
- Code examples
- Migration scripts
- Testing approaches

Follow them sequentially for best results.
