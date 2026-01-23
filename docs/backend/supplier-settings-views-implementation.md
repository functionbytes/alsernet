# Supplier Settings Views Implementation

## Overview

Implementation of the suppliers settings interface at `https://manager.test/manager/settings/suppliers` following the exact same pattern and styling as other settings modules (departments, categories).

**Implementation Date**: 2025-12-20
**Pattern Reference**: Settings views (departments/categories)
**Technology Stack**: Laravel 12, Blade, jQuery, AJAX, Bootstrap 5.3

---

## Files Created/Modified

### Views Created

1. **resources/views/managers/views/settings/suppliers/index.blade.php**
   - Suppliers listing with search and filters
   - Pagination-based (not DataTables)
   - Matches departments/categories pattern exactly

2. **resources/views/managers/views/settings/suppliers/create.blade.php**
   - Create new supplier form
   - jQuery validation
   - AJAX submission

3. **resources/views/managers/views/settings/suppliers/edit.blade.php**
   - Edit existing supplier form
   - Pre-filled data
   - jQuery validation with PUT method

### Controller Modified

**app/Http/Controllers/Managers/Settings/Suppliers/SuppliersController.php**

Updated methods:
- `index()`: Changed from DataTables to standard pagination
- `store()`: Simplified to match form fields
- `update()`: Simplified to match form fields

---

## Implementation Details

### Index View Pattern

Following the exact structure from departments/categories settings:

```blade
@extends('layouts.managers')

@section('content')
  @include('managers.includes.card', ['title' => 'Proveedores'])

  <div class="widget-content searchable-container list">
    <!-- Search form with GET method -->
    <!-- Filter dropdown for status -->
    <!-- Table with dropdown actions -->
    <!-- Pagination with appends -->
  </div>
@endsection
```

**Key Features**:
- Search by: name, code, email
- Filter by: status (active/inactive)
- Sort by: priority DESC, name ASC
- Pagination: 15 items per page
- Actions: Edit, Sources, Delete (dropdown menu)

### Form Fields

All forms include these fields:

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| code | text | required, 2-20 chars | Unique supplier code |
| name | text | required, 3-255 chars | Supplier name |
| website | url | optional, valid URL | Supplier website URL |
| priority | number | required, 1-100 | Display priority (default: 10) |
| contact_email | email | optional, valid email | Contact email |
| is_active | select | required | Active/Inactive status |
| description | textarea | optional | Supplier description |

### jQuery Validation Pattern

```javascript
$("#formSupplier").validate({
  submit: false,
  ignore: ".ignore",
  rules: {
    code: { required: true, minlength: 2, maxlength: 20 },
    name: { required: true, minlength: 3, maxlength: 255 },
    // ... more rules
  },
  messages: {
    code: {
      required: "El código es necesario.",
      minlength: "Debe contener al menos 2 caracteres",
      // ... more messages
    }
  },
  submitHandler: function(form) {
    var formData = new FormData($('#formSupplier')[0]);

    $.ajax({
      url: "{{ route('...') }}",
      type: "POST", // or "PUT" for update
      contentType: false,
      processData: false,
      data: formData,
      success: function(response) {
        if(response.success == true){
          toastr.success(message, "Operación exitosa");
          setTimeout(function() {
            window.location = "{{ route('manager.backups.suppliers.index') }}";
          }, 2000);
        }
      }
    });
  }
});
```

### Controller Index Method

```php
public function index(Request $request): View
{
    $pageTitle = 'Gestión de Proveedores';
    $breadcrumb = 'Configuración / Proveedores';

    $searchKey = $request->get('search');
    $is_active = $request->get('is_active');

    $query = Supplier::query()->withCount('sources');

    if ($searchKey) {
        $query->where(function ($q) use ($searchKey) {
            $q->where('name', 'like', "%{$searchKey}%")
                ->orWhere('code', 'like', "%{$searchKey}%")
                ->orWhere('contact_email', 'like', "%{$searchKey}%");
        });
    }

    if ($is_active !== null && $is_active !== '') {
        $query->where('is_active', $is_active);
    }

    $suppliers = $query->orderBy('priority', 'desc')
        ->orderBy('name', 'asc')
        ->paginate(15);

    return view('theme.views.backups.suppliers.index',
        compact('suppliers', 'searchKey', 'is_active', 'pageTitle', 'breadcrumb'));
}
```

### Controller Store Method

```php
public function store(StoreSupplierRequest $request): JsonResponse
{
    try {
        $supplier = Supplier::create([
            'name' => $request->name,
            'code' => $request->code,
            'website' => $request->website,
            'description' => $request->description,
            'contact_email' => $request->contact_email,
            'priority' => $request->priority ?? 10,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor creado exitosamente',
            'supplier' => $supplier,
        ]);
    } catch (\Exception $e) {
        Log::error('Error creating supplier: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al crear el proveedor: '.$e->getMessage(),
        ], 500);
    }
}
```

### Controller Update Method

```php
public function update(UpdateSupplierRequest $request, string $uid): JsonResponse
{
    try {
        $supplier = Supplier::where('uid', $uid)->firstOrFail();

        $supplier->update([
            'name' => $request->name,
            'code' => $request->code,
            'website' => $request->website,
            'description' => $request->description,
            'contact_email' => $request->contact_email,
            'priority' => $request->priority ?? $supplier->priority,
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor actualizado exitosamente',
            'supplier' => $supplier->fresh(),
        ]);
    } catch (\Exception $e) {
        Log::error('Error updating supplier: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar el proveedor: '.$e->getMessage(),
        ], 500);
    }
}
```

---

## Routes

All routes already defined in `routes/managers.php`:

```php
Route::group(['prefix' => 'suppliers'], function () {
    Route::get('/', [SuppliersController::class, 'index'])
        ->name('manager.backups.suppliers.index');
    Route::get('/create', [SuppliersController::class, 'create'])
        ->name('manager.backups.suppliers.create');
    Route::post('/', [SuppliersController::class, 'store'])
        ->name('manager.backups.suppliers.store');
    Route::get('/{uid}/edit', [SuppliersController::class, 'edit'])
        ->name('manager.backups.suppliers.edit');
    Route::put('/{uid}', [SuppliersController::class, 'update'])
        ->name('manager.backups.suppliers.update');
    Route::delete('/{uid}', [SuppliersController::class, 'destroy'])
        ->name('manager.backups.suppliers.destroy');
    Route::get('/{uid}', [SuppliersController::class, 'show'])
        ->name('manager.backups.suppliers.show');
    Route::post('/{uid}/toggle', [SuppliersController::class, 'toggle'])
        ->name('manager.backups.suppliers.toggle');
});
```

---

## Design Consistency

### Bootstrap Classes Used

Matching the exact classes from departments/categories:

- **Cards**: `card`, `card-body`
- **Forms**: `form-control`, `form-select`, `select2`
- **Buttons**: `btn btn-primary`, `btn btn-info px-4 waves-effect waves-light`
- **Tables**: `table search-table align-middle text-nowrap`
- **Badges**: `badge bg-light-success`, `badge bg-light-danger`, `badge bg-light-info`
- **Dropdowns**: `dropdown dropstart`, `dropdown-menu`, `dropdown-item`
- **Spacing**: `mb-3`, `mt-2`, `p-3`, `gap-2`

### Icons (Font Awesome 6)

```html
<i class="fa-duotone fa-magnifying-glass"></i>  <!-- Search button -->
<i class="fa-duotone fa-plus"></i>              <!-- Create button -->
<i class="fa-duotone fa-solid fa-ellipsis"></i> <!-- Actions dropdown -->
<i data-feather="search"></i>                   <!-- Search input icon -->
```

### Color Scheme

- **Primary**: `#90bb13` (Green)
- **Success**: `#13C672` (Active status)
- **Danger**: `#FA896B` (Inactive status)
- **Info**: `#5D87FF` (Type badges)

---

## User Flow

1. **List Suppliers** (`/manager/settings/suppliers`)
   - View all suppliers with search/filter
   - See count of sources per supplier
   - Click "Create" to add new supplier
   - Click "Edit" to modify supplier
   - Click "Sources" to manage supplier sources
   - Click "Delete" to remove supplier

2. **Create Supplier** (`/manager/settings/suppliers/create`)
   - Fill form fields
   - Click "Guardar"
   - Form validates with jQuery
   - AJAX POST request
   - Success toast notification
   - Redirect to index after 2 seconds

3. **Edit Supplier** (`/manager/settings/suppliers/{uid}/edit`)
   - View pre-filled form
   - Modify fields
   - Click "Guardar"
   - Form validates with jQuery
   - AJAX PUT request
   - Success toast notification
   - Redirect to index after 2 seconds

---

## Testing Checklist

- [ ] Index page loads with suppliers list
- [ ] Search by name works
- [ ] Search by code works
- [ ] Search by email works
- [ ] Filter by status (active/inactive) works
- [ ] Pagination works correctly
- [ ] Create new supplier form submits successfully
- [ ] Validation prevents invalid data
- [ ] Edit supplier form loads with pre-filled data
- [ ] Update supplier saves changes
- [ ] Delete supplier removes record (with active sources check)
- [ ] Toast notifications appear on success/error
- [ ] Redirect to index after create/update works
- [ ] Dropdown actions menu displays correctly
- [ ] Sources link navigates to sources management

---

## Notes

- **No DataTables**: Removed DataTables implementation in favor of standard Laravel pagination to match project patterns
- **AJAX Forms**: All forms use AJAX submission with jQuery validation
- **Consistent Styling**: 100% matches departments/categories visual design
- **Font Awesome 6**: All icons use Font Awesome (no Tabler Icons)
- **Bootstrap 5.3**: All classes are Bootstrap 5.3 compatible
- **Responsive**: Mobile-friendly with Bootstrap grid system
- **Error Handling**: Try-catch blocks with logging and user-friendly messages

---

## Related Documentation

- [Supplier Automation Implementation](./supplier-automation-implementation-log.md)
- [Bootstrap Modernize Template](../frontend/README.md)
- [Design Rules](../frontend/design-rules.md)
- [Components Reference](../frontend/components.md)
