# Document Permissions Usage Examples

Practical examples of how to use document permissions in your application.

## Quick Start

### 1. Register User with Role
```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);

// Assign role
$user->assignRole('administrative');

// User now has all administrative permissions
```

### 2. Check Permission in Controller
```php
<?php

namespace App\Http\Controllers\Documents;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends BaseController
{
    public function create()
    {
        // Method 1: Using can() helper
        if (!auth()->user()->can('documents.create')) {
            abort(403, 'Not authorized to create documents');
        }

        return view('documents.create');
    }

    public function store(Request $request)
    {
        $this->authorize('documents.create'); // Method 2: Using authorize()

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type_id' => 'required|exists:document_types,id',
        ]);

        Document::create($validated);

        return redirect()->route('documents.index')
            ->with('success', 'Document created successfully');
    }

    public function edit(Document $document)
    {
        $this->authorize('documents.update');
        return view('documents.edit', compact('document'));
    }

    public function destroy(Document $document)
    {
        $this->authorize('documents.delete');
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted');
    }
}
```

### 3. Protect Routes
```php
<?php

// routes/web.php

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('documents', DocumentController::class)
        ->middleware('can:documents.view');

    Route::post('documents/{document}/approve-stage', [DocumentController::class, 'approveStage'])
        ->middleware('can:documents.approve_stage')
        ->name('documents.approve_stage');

    Route::post('documents/{document}/reject-stage', [DocumentController::class, 'rejectStage'])
        ->middleware('can:documents.reject_stage')
        ->name('documents.reject_stage');

    Route::resource('document-types', DocumentTypeController::class)
        ->middleware([
            'can:document_types.view',
            'can:document_types.create',
        ]);
});
```

### 4. Blade Template Conditionals
```blade
<!-- resources/views/documents/show.blade.php -->

<div class="document-card">
    <h1>{{ $document->title }}</h1>

    <div class="actions">
        @can('documents.update')
            <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary">
                Edit
            </a>
        @endcan

        @can('documents.approve_stage')
            <button class="btn btn-success" wire:click="approveStage">
                Approve Stage
            </button>
        @endcan

        @can('documents.reject_stage')
            <button class="btn btn-danger" wire:click="rejectStage">
                Reject Stage
            </button>
        @endcan

        @can('documents.delete')
            <button class="btn btn-danger" wire:click="delete"
                    onclick="confirm('Are you sure?') || event.stopImmediatePropagation()">
                Delete
            </button>
        @endcan
    </div>

    @can('documents.files.create')
        <div class="file-upload-section">
            <h3>Upload File</h3>
            <livewire:documents.file-upload :document="$document" />
        </div>
    @endcan

    @canany(['documents.notes.create', 'documents.notes.update'])
        <div class="notes-section">
            <h3>Notes</h3>
            <livewire:documents.notes :document="$document" />
        </div>
    @endcanany
</div>
```

### 5. Livewire Component Authorization
```php
<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Livewire\Component;

class DocumentForm extends Component
{
    public Document $document;
    public bool $editing = false;

    public function mount(Document $document)
    {
        $this->document = $document;
        $this->authorize('documents.view');
    }

    public function save()
    {
        // Check permission before allowing edit
        $this->authorize('documents.update');

        $validated = $this->validate([
            'document.title' => 'required|string|max:255',
            'document.description' => 'nullable|string',
        ]);

        $this->document->save();

        $this->dispatch('document-saved');
    }

    public function delete()
    {
        $this->authorize('documents.delete');

        $this->document->delete();
        redirect()->route('documents.index');
    }

    public function render()
    {
        return view('livewire.documents.document-form');
    }
}
```

### 6. Policy Authorization (Recommended for Models)
```php
<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can('documents.update');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.delete');
    }

    public function approveStage(User $user, Document $document): bool
    {
        return $user->can('documents.approve_stage');
    }

    public function rejectStage(User $user, Document $document): bool
    {
        return $user->can('documents.reject_stage');
    }

    public function manageFiles(User $user, Document $document): bool
    {
        return $user->can('documents.files.create')
            || $user->can('documents.files.update')
            || $user->can('documents.files.delete');
    }
}
```

Register policy in `AuthServiceProvider`:
```php
protected $policies = [
    Document::class => DocumentPolicy::class,
];
```

### 7. API Endpoint Authorization
```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use Illuminate\Http\JsonResponse;

class DocumentApiController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('documents.view');

        $documents = Document::paginate();
        return response()->json($documents);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('documents.create');

        $validated = $request->validate([
            'title' => 'required|string',
            'type_id' => 'required|integer',
        ]);

        $document = Document::create($validated);
        return response()->json($document, 201);
    }

    public function approveStage(Document $document): JsonResponse
    {
        $this->authorize('documents.approve_stage');

        $document->approveCurrentStage();
        return response()->json(['message' => 'Stage approved']);
    }
}
```

### 8. Seeding Users with Roles
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $superAdmin->assignRole('super-admin');

        // Manager
        $manager = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
        ]);
        $manager->assignRole('manager');

        // Administrative Staff
        $admin = User::factory()->create([
            'name' => 'Admin Staff',
            'email' => 'staff@example.com',
        ]);
        $admin->assignRole('administrative');
    }
}
```

### 9. Dynamic Permission Checking
```php
<?php

// Check multiple permissions (OR logic - any permission)
if (auth()->user()->canAny(['documents.update', 'documents.approve_stage'])) {
    // User can either update or approve
}

// Check multiple permissions (AND logic - all permissions)
if (auth()->user()->hasAllPermissions(['documents.update', 'documents.approve_stage'])) {
    // User can both update and approve
}

// Check by role
if (auth()->user()->hasRole('super-admin')) {
    // Is super admin
}

// Get all user permissions
$permissions = auth()->user()->getPermissionsViaRoles();
```

### 10. Custom Authorization Gate
```php
<?php

// In AuthServiceProvider boot method

Gate::define('manage-documents', function (User $user) {
    return $user->hasPermissionTo('documents.manage')
        || $user->hasRole('super-admin');
});

// Usage in controller
if (Gate::denies('manage-documents')) {
    abort(403);
}

// Or in Blade
@can('manage-documents')
    <!-- Show admin panel -->
@endcan
```

## Common Patterns

### Permission Hierarchy
```
Super Admin
├── All permissions (via super-admin role)

Manager
├── View: documents, types, groups, conditions, policies, storage, settings, blockades
└── Configure: groups, settings

Administrative
├── CRUD: documents, files, notes
├── Actions: approve/reject stages, assign
└── View: types, groups, conditions, policies, storage, settings, blockades
```

### Batch Permission Checking
```php
// Check if user can manage documents at all
$canManageDocuments = auth()->user()->canAny([
    'documents.create',
    'documents.update',
    'documents.delete',
    'documents.approve_stage',
    'documents.reject_stage',
]);
```

### Conditional Features
```blade
@if(auth()->user()->can('documents.files.create'))
    <!-- Show file upload -->
@elseif(auth()->user()->can('documents.files.download'))
    <!-- Show download only -->
@else
    <!-- No file access -->
@endif
```

## Testing Permissions

```php
<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_document_with_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('documents.create');

        $this->actingAs($user)
            ->post(route('documents.store'), [
                'title' => 'Test Document',
                'type_id' => 1,
            ])
            ->assertRedirect();
    }

    public function test_user_cannot_create_document_without_permission()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('documents.store'), [
                'title' => 'Test Document',
                'type_id' => 1,
            ])
            ->assertForbidden();
    }
}
```

## Performance Considerations

### Caching Permissions
```php
// Permissions are cached - clear cache when seeding
Cache::clear();

// Or specifically
app(PermissionRegistrar::class)->forgetCachedPermissions();
```

### Eager Loading Roles
```php
// Get users with roles (avoid N+1)
$users = User::with('roles', 'permissions')->get();

// Check permission (uses cache)
$user->can('documents.view');
```

## Related Resources

- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization](https://laravel.com/docs/authorization)
- [Document Permissions Seeder](./document-permissions-seeder.md)
