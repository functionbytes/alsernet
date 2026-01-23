# Media Manager System

## Overview

Alsernet includes a comprehensive Media Manager system for organizing and managing files with a folder hierarchy interface, similar to modern file explorers. The system provides:

- ✅ Folder-based organization with unlimited nesting
- ✅ File upload with validation and type detection
- ✅ Soft delete and restore functionality
- ✅ User-based access control
- ✅ Search and pagination
- ✅ Context menus for operations
- ✅ Breadcrumb navigation

## Database Schema

### media_folders Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uid | string | Unique identifier (ULID) |
| name | string | Folder name |
| slug | string | URL-friendly slug (unique) |
| parent_id | bigint | Parent folder reference (nullable) |
| user_id | bigint | Owner user |
| color | string | Folder display color (#5a67d8) |
| deleted_at | timestamp | Soft delete timestamp |
| created_at | timestamp | Created timestamp |
| updated_at | timestamp | Updated timestamp |

**Relationships:**
- `parent()` - BelongsTo MediaFolder (self-referencing)
- `children()` - HasMany MediaFolder (self-referencing)
- `files()` - HasMany MediaFile
- `user()` - BelongsTo User

**Indices:**
- parent_id
- user_id
- slug

### media_files Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uid | string | Unique identifier (ULID) |
| name | string | File name |
| mime_type | string | MIME type (image/jpeg, etc) |
| type | string | File type (image, video, audio, pdf, document) |
| size | bigint | File size in bytes |
| url | string | Storage path |
| alt | text | Alt text for images |
| folder_id | bigint | Parent folder (nullable) |
| user_id | bigint | Owner user |
| metadata | json | Image dimensions, etc |
| visibility | string | private or public |
| deleted_at | timestamp | Soft delete timestamp |
| created_at | timestamp | Created timestamp |
| updated_at | timestamp | Updated timestamp |

**Relationships:**
- `folder()` - BelongsTo MediaFolder
- `user()` - BelongsTo User

**Indices:**
- folder_id
- user_id
- mime_type
- type
- fullText name (for search)

## Models

### MediaFolder Model

```php
use App\Models\Setting\Media\MediaFolder;

// Create a folder
$folder = MediaFolder::create([
    'name' => 'My Folder',
    'parent_id' => null, // Root level
    'user_id' => auth()->id(),
]);

// Get root folders only
$rootFolders = MediaFolder::root()->byUser()->get();

// Get all files in folder
$files = $folder->files()->get();

// Get child folders
$childFolders = $folder->children()->get();

// Soft delete
$folder->delete(); // Can be restored with restore()

// Force delete (deletes all files too)
$folder->forceDelete();
```

**Scopes:**
- `root()` - Only root-level folders
- `byUser($userId = null)` - Folders owned by user (defaults to auth user)

### MediaFile Model

```php
use App\Models\Setting\Media\MediaFile;

// Create a file
$file = MediaFile::create([
    'name' => 'document.pdf',
    'mime_type' => 'application/pdf',
    'size' => 1024,
    'url' => 'media/1/document.pdf',
    'folder_id' => $folder->id,
    'user_id' => auth()->id(),
]);

// Access file properties
echo $file->type; // 'pdf'
echo $file->human_size; // '1 KB'

// Get files of specific type
$images = MediaFile::ofType('image')->get();

// Get public files only
$publicFiles = MediaFile::public()->get();

// Soft delete
$file->delete(); // Can be restored

// Force delete
$file->forceDelete();
```

**Scopes:**
- `byUser($userId = null)` - Files owned by user
- `ofType($type)` - Filter by type (image, video, audio, pdf, document)
- `public()` - Only public files

**Attributes:**
- `human_size` - Human-readable file size (1 KB, 2 MB, etc)

## Controllers

### MediaManagerController

Located at `app/Http/Controllers/Managers/Media/MediaManagerController.php`

#### Routes

```php
Route::prefix('media')->name('manager.media.')->group(function () {
    Route::get('/', [MediaManagerController::class, 'index'])->name('index');
    Route::get('/list', [MediaManagerController::class, 'getList'])->name('list');
    Route::post('/upload', [MediaManagerController::class, 'uploadFile'])->name('upload');
    Route::post('/folder/create', [MediaManagerController::class, 'createFolder'])->name('folder.create');
    Route::put('/file/{file}/rename', [MediaManagerController::class, 'renameFile'])->name('file.rename');
    Route::put('/folder/{folder}/rename', [MediaManagerController::class, 'renameFolder'])->name('folder.rename');
    Route::delete('/file/{file}', [MediaManagerController::class, 'deleteFile'])->name('file.delete');
    Route::delete('/folder/{folder}', [MediaManagerController::class, 'deleteFolder'])->name('folder.delete');
    Route::post('/file/{file}/restore', [MediaManagerController::class, 'restoreFile'])->name('file.restore');
    Route::post('/folder/{folder}/restore', [MediaManagerController::class, 'restoreFolder'])->name('folder.restore');
    Route::put('/file/{file}/move', [MediaManagerController::class, 'moveFile'])->name('file.move');
    Route::put('/folder/{folder}/move', [MediaManagerController::class, 'moveFolder'])->name('folder.move');
});
```

#### Methods

**index()** - Returns view
```php
Route: GET /manager/media
Returns: media.index view
```

**getList()** - Returns JSON of files and folders
```php
Route: GET /manager/media/list
Parameters:
  - folder_id: int (0 for root)
  - search: string
  - view_in: string (all_media, trash, recent)
  - per_page: int (default 30)
  - page: int

Response:
{
  "folders": [
    {
      "id": 1,
      "uid": "ulid123",
      "name": "My Folder",
      "icon": "ti ti-folder",
      "color": "#5a67d8",
      "created_at": "2025-12-10 20:07",
      "children_count": 5,
      "files_count": 12
    }
  ],
  "files": [
    {
      "id": 1,
      "uid": "ulid456",
      "name": "image.jpg",
      "type": "image",
      "mime_type": "image/jpeg",
      "size": 2048576,
      "human_size": "2 MB",
      "url": "media/1/image.jpg",
      "alt": "Image description",
      "created_at": "2025-12-10 20:07",
      "is_trash": false
    }
  ],
  "breadcrumbs": [
    { "id": 0, "name": "Todos los archivos", "icon": "ti ti-folder-open" }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 30,
    "total": 100,
    "last_page": 4
  }
}
```

**uploadFile()** - Upload file
```php
Route: POST /manager/media/upload
Parameters:
  - file: UploadedFile (required, max 100MB)
  - folder_id: int (nullable)

Response:
{
  "success": true,
  "file": {
    "id": 1,
    "uid": "ulid456",
    "name": "image.jpg",
    "type": "image",
    "url": "media/1/image.jpg",
    "size": "2 MB"
  }
}
```

**createFolder()** - Create folder
```php
Route: POST /manager/media/folder/create
Parameters:
  - name: string (required)
  - parent_id: int (nullable)

Response:
{
  "success": true,
  "folder": {
    "id": 1,
    "uid": "ulid123",
    "name": "My Folder",
    "color": "#5a67d8"
  }
}
```

**renameFile()** - Rename file
```php
Route: PUT /manager/media/file/{file}/rename
Parameters:
  - name: string (required)

Response:
{ "success": true }
```

**renameFolder()** - Rename folder
```php
Route: PUT /manager/media/folder/{folder}/rename
Parameters:
  - name: string (required)

Response:
{ "success": true }
```

**deleteFile()** - Soft delete file
```php
Route: DELETE /manager/media/file/{file}
Response:
{ "success": true, "message": "Archivo eliminado" }
```

**deleteFolder()** - Soft delete folder
```php
Route: DELETE /manager/media/folder/{folder}
Response:
{ "success": true, "message": "Carpeta eliminada" }
```

**restoreFile()** - Restore deleted file
```php
Route: POST /manager/media/file/{file}/restore
Response:
{ "success": true }
```

**restoreFolder()** - Restore deleted folder
```php
Route: POST /manager/media/folder/{folder}/restore
Response:
{ "success": true }
```

**moveFile()** - Move file to folder
```php
Route: PUT /manager/media/file/{file}/move
Parameters:
  - folder_id: int (nullable)

Response:
{ "success": true }
```

**moveFolder()** - Move folder to parent
```php
Route: PUT /manager/media/folder/{folder}/move
Parameters:
  - parent_id: int (nullable)

Response:
{ "success": true }
```

## Frontend UI

### Media Manager Interface

Located at `resources/views/managers/media/index.blade.php`

Built with Vue 3 for reactive folder/file management.

**Features:**
- Sidebar folder tree navigation
- Main content area with grid view
- Breadcrumb navigation
- File upload with drag & drop
- Context menus for operations
- Search functionality
- Pagination
- Soft delete and restore

**Components:**
- `FolderTree` - Sidebar folder navigation
- `MediaFolderCard` - Folder card display
- `MediaFileCard` - File card display

## Authorization

### Policies

**MediaFilePolicy** - `app/Policies/MediaFilePolicy.php`
- Users can only view/edit/delete their own files
- Based on `user_id` comparison

**MediaFolderPolicy** - `app/Policies/MediaFolderPolicy.php`
- Users can only view/edit/delete their own folders
- Based on `user_id` comparison

**Usage in Controller:**
```php
$this->authorize('update', $file); // Checks policy
$this->authorize('delete', $folder);
```

## File Type Detection

Files are automatically categorized by MIME type:

```php
private static function detectType(string $mimeType): string
{
    return match (true) {
        str_starts_with($mimeType, 'image/') => 'image',
        str_starts_with($mimeType, 'video/') => 'video',
        str_starts_with($mimeType, 'audio/') => 'audio',
        str_starts_with($mimeType, 'application/pdf') => 'pdf',
        str_starts_with($mimeType, 'text/') => 'document',
        str_starts_with($mimeType, 'application/') => 'document',
        default => 'unknown',
    };
}
```

## Storage

Files are stored in `storage/app/media/{folder_id}/` by default.

**Configuration** (php.ini/Laravel config):
- Max upload size: 100MB
- Allowed MIME types: All (validated by Laravel)

## Usage Examples

### In Blade Templates

```blade
<a href="{{ route('manager.media.index') }}" class="btn btn-primary">
    Abrir Gestor de Medios
</a>
```

### In JavaScript/Vue

```javascript
// Fetch media list
const response = await fetch('/manager/media/list?folder_id=0');
const data = await response.json();

// Upload file
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('folder_id', folderId);

fetch('/manager/media/upload', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': token
    },
    body: formData
});

// Create folder
fetch('/manager/media/folder/create', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
    },
    body: JSON.stringify({
        name: 'New Folder',
        parent_id: null
    })
});
```

### In PHP/Laravel

```php
// Get user's media
$files = MediaFile::byUser()->get();
$folders = MediaFolder::byUser()->root()->get();

// Get files in folder
$folder = MediaFolder::find(1);
$files = $folder->files()->get();

// Search files
$results = MediaFile::byUser()
    ->where('name', 'LIKE', '%document%')
    ->get();

// Get recent uploads
$recent = MediaFile::byUser()
    ->orderByDesc('created_at')
    ->limit(10)
    ->get();
```

## Best Practices

1. **Always check authorization** - Use policies before returning sensitive data
2. **Use soft deletes** - Allow recovery of accidentally deleted files
3. **Organize by folders** - Help users maintain file structure
4. **Validate uploads** - Check MIME type and size on server
5. **Optimize queries** - Use eager loading to avoid N+1 problems
6. **Cache searches** - Consider caching frequently searched terms

## Security Considerations

- Users only see their own files/folders
- Policies prevent unauthorized access
- MIME type validation on upload
- File size limits (100MB max)
- No directory traversal possible (slug-based paths)
- Soft deletes allow recovery

## Design System (Modernize Bootstrap)

El media manager sigue los patrones de diseño del template Modernize para mantener consistencia visual con el resto de la aplicación.

### Color Palette

```css
--primary: #5D87FF (azul principal)
--primary-dark: #3E5BDB (hover states)
--success: #13C672 (archivos subidos)
--danger: #FA896B (eliminación)
--warning: #FEC90F (almacenamiento)
--info: #5DADE2 (actividad reciente)
--light-bg: #f8f9fa (sidebar)
--card-border: #e0e0e0 (bordes sutiles)
```

### Visual Components

**Header con Gradient**:
- Background: `linear-gradient(135deg, #5D87FF 0%, #3E5BDB 100%)`
- Shadow: `0 4px 15px rgba(93, 135, 255, 0.2)`
- Border radius: `12px`

**Stats Cards**:
- Grid: `repeat(auto-fit, minmax(200px, 1fr))`
- Hover effect: `translateY(-2px)` + `box-shadow`
- Icon wrapper: `48px × 48px` con bg-subtle

**Media Cards**:
- Border radius: `12px`
- Hover: `translateY(-4px)` + `box-shadow: 0 8px 24px rgba(0,0,0,0.12)`
- Border color change: `#5D87FF`
- Transition: `all 0.2s ease`

**Sidebar**:
- Background: `#f8f9fa`
- Section titles: uppercase, `0.75rem`, `letter-spacing: 0.5px`
- Active state: `bg-primary-subtle` + `border-left: 3px solid #5D87FF`
- Hover: `background: #f0f4ff`

**Buttons**:
- Primary custom: padding `0.625rem 1.25rem`, border-radius `8px`
- Hover lift: `translateY(-2px)` + shadow
- Action buttons: `32px × 32px` iconos centrados

**Empty State**:
- Icon circle: `80px`, background `#f0f0f0`, border-radius `50%`
- Text hierarchy: `h5` bold + `p` muted + CTA button

### Responsive Breakpoints

```html
<!-- Grid columns por pantalla -->
col-xl-2  → 6 columnas (≥1200px)
col-lg-3  → 4 columnas (≥992px)
col-md-4  → 3 columnas (≥768px)
col-sm-6  → 2 columnas (≥576px)
```

### Icons (Tabler Icons)

- Carpetas: `ti ti-folder` (coloreadas con folder.color)
- Home: `ti ti-home`
- Archivos imagen: `ti ti-photo`
- Archivos video: `ti ti-video`
- Archivos audio: `ti ti-music`
- PDF: `ti ti-file-type-pdf`
- Documentos: `ti ti-file-text`
- Genéricos: `ti ti-file`
- Acciones: `ti ti-edit`, `ti ti-trash`, `ti ti-download`
- Navegación: `ti ti-chevron-left`, `ti ti-chevron-right`
- Búsqueda: `ti ti-search`

### Animation Classes

```css
.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.transition-all {
    transition: all 0.2s ease-in-out;
}
```

### Layout Structure

```
┌─────────────────────────────────────────┐
│ Header (gradient)                       │
│ - Título + Descripción                  │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Stats Grid (4 cards)                    │
│ Carpetas | Archivos | Espacio | Act.   │
└─────────────────────────────────────────┘

┌──────────┬──────────────────────────────┐
│ Sidebar  │ Main Content                 │
│          │ ┌──────────────────────────┐ │
│ Nav      │ │ Toolbar (breadcrumbs)    │ │
│ Folders  │ └──────────────────────────┘ │
│ Actions  │ ┌──────────────────────────┐ │
│          │ │ Search Bar               │ │
│          │ └──────────────────────────┘ │
│          │ ┌──────────────────────────┐ │
│          │ │ Grid (folders + files)   │ │
│          │ └──────────────────────────┘ │
│          │ ┌──────────────────────────┐ │
│          │ │ Pagination               │ │
│          │ └──────────────────────────┘ │
└──────────┴──────────────────────────────┘
```

## Future Enhancements

- [ ] Drag & drop to move files between folders
- [ ] Bulk operations (select multiple files)
- [ ] File preview functionality (modal con vista previa)
- [ ] Sharing with expiration
- [ ] Comments/annotations on files
- [ ] File versioning
- [ ] Auto-generated thumbnails (usando Intervention/Image)
- [ ] Integration with Spatie MediaLibrary
- [ ] Dropdown menus contextuales (reemplazar prompts)
- [ ] Upload progress bar con porcentaje
- [ ] Grid/List view toggle
- [ ] Sort by (name, date, size, type)
- [ ] Filter by type (images, videos, documents)
- [ ] Keyboard shortcuts (del, ctrl+a, escape)
