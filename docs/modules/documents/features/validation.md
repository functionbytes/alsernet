# Sistema de Validación de Documentos - Guía Completa

## 📋 Índice
1. [Arquitectura General](#arquitectura-general)
2. [Etapas de Validación](#etapas-de-validación)
3. [Acciones Disponibles](#acciones-disponibles)
4. [Cómo Agregar Nueva Acción](#cómo-agregar-nueva-acción)
5. [Cómo Agregar Nueva Etapa](#cómo-agregar-nueva-etapa)
6. [Cómo Agregar Opción en Nav](#cómo-agregar-opción-en-nav)
7. [Ejemplos Prácticos](#ejemplos-prácticos)

---

## 🏗️ Arquitectura General

El sistema de validación de documentos está compuesto por:

```
┌─────────────────────────────────────────────────────────────┐
│                   DOCUMENT VALIDATION FLOW                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. DOCUMENTACIÓN (Stage 1)                                 │
│     ├─ Verificar documentos requeridos                      │
│     ├─ Enviar solicitudes a cliente                         │
│     └─ Aprobar o rechazar                                   │
│            ↓                                                │
│  2. LICENCIAS (Stage 2) - CONDICIONAL                       │
│     ├─ Si is_weapon = true                                  │
│     ├─ Validar licencias y permisos                         │
│     └─ Avanzar a siguiente etapa                            │
│            ↓                                                │
│  3. CONTABILIDAD (Stage 3)                                  │
│     ├─ Validación final de cuentas                          │
│     ├─ Aprobación definitiva                                │
│     └─ Rechazo definitivo                                   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Archivos Clave

| Archivo | Propósito |
|---------|-----------|
| `config/validation-permissions.php` | Define permisos y acciones por etapa |
| `app/Models/Document/Document.php` | Modelo con trait `HasValidationWorkflow` |
| `app/Library/Traits/HasValidationWorkflow.php` | Lógica de validación |
| `app/Enums/Document/ValidationAction.php` | Enum con todas las acciones |
| `resources/views/accountings/views/documents/manage.blade.php` | UI de acciones |

---

## 📍 Etapas de Validación

### 1️⃣ DOCUMENTACIÓN (Etapa 1 - Obligatoria)

**Propósito:** Verificar que todos los documentos requeridos estén completos

```php
'documentacion' => [
    'order' => 1,
    'label' => 'Documentación',
    'description' => 'Verificación de documentos requeridos',
    'allowed_actions' => [
        ValidationAction::APPROVE->value,                 // ✅ Validar y avanzar
        ValidationAction::REJECT->value,                  // ❌ Rechazar documentos
        ValidationAction::SEND_APPROVAL_EMAIL->value,     // 📧 Enviar email de aprobación
        ValidationAction::ADD_COMMENT->value,             // 💬 Agregar comentarios
        ValidationAction::REQUEST_ADDITIONAL_DOCS->value, // 📎 Solicitar más docs
    ],
]
```

**Quién lo realiza:** Equipo de Documentación (ValidatorGroup: `documentacion`)

**Acciones:**
- ✅ Aprobar documentos → Avanza a LICENCIAS (si es arma) o CONTABILIDAD
- ❌ Rechazar documentos → Envía email al cliente con motivos
- 💬 Agregar comentarios internos → Visible solo para el equipo
- 📧 Enviar email de aprobación → Notifica al cliente

---

### 2️⃣ LICENCIAS (Etapa 2 - CONDICIONAL)

**Condición:** Se ejecuta SOLO si el documento tiene `is_weapon = true`

**Propósito:** Validar licencias y documentos relacionados con armas

```php
'licencias' => [
    'order' => 2,
    'label' => 'Licencias',
    'description' => 'Validación de licencias y documentos de armas',
    'allowed_actions' => [
        ValidationAction::APPROVE->value,                 // ✅ Confirmar y avanzar
        ValidationAction::ADD_COMMENT->value,             // 💬 Comentarios
        ValidationAction::REQUEST_ADDITIONAL_DOCS->value, // 📎 Solicitar docs
    ],
    'restricted_actions' => [
        ValidationAction::REJECT->value,                  // ❌ NO se puede rechazar
        ValidationAction::SEND_APPROVAL_EMAIL->value,     // 📧 NO envía emails
    ],
]
```

**Características especiales:**
- ❌ **NO puede rechazar** → Solo avanza o solicita más docs
- 📧 **NO envía emails** → Es etapa intermedia
- 🔄 **Etapa obligatoria solo si es arma** → Si no, salta directo a CONTABILIDAD

---

### 3️⃣ CONTABILIDAD (Etapa 3 - Obligatoria)

**Propósito:** Validación final de contabilidad y aprobación definitiva

```php
'contabilidad' => [
    'order' => 3,
    'label' => 'Contabilidad',
    'description' => 'Validación final de contabilidad',
    'allowed_actions' => [
        ValidationAction::APPROVE->value,                 // ✅ Aprobación final
        ValidationAction::REJECT->value,                  // ❌ Rechazo final
        ValidationAction::SEND_APPROVAL_EMAIL->value,     // 📧 Email de aprobación final
        ValidationAction::ACCESS_ADDITIONAL_FILES->value, // 📎 Acceso a archivos adicionales
        ValidationAction::ADD_COMMENT->value,             // 💬 Comentarios finales
        ValidationAction::REQUEST_ADDITIONAL_DOCS->value, // 📎 Solicitar más docs
    ],
]
```

**Quién lo realiza:** Equipo de Contabilidad (ValidatorGroup: `contabilidad`)

**Características:**
- ✅ **Aprobación FINAL** → El documento está completamente aprobado
- ❌ **Rechazo FINAL** → El documento es rechazado completamente
- 📧 **Envía emails** → Notifica cliente de aprobación/rechazo final
- 📎 **Acceso a archivos adicionales** → Puede ver documentos suplementarios

---

## 🎬 Acciones Disponibles

Las acciones están definidas en `app/Enums/Document/ValidationAction.php`:

```php
enum ValidationAction: string {
    case APPROVE = 'approve';                       // ✅ Aprobar
    case REJECT = 'reject';                         // ❌ Rechazar
    case SEND_APPROVAL_EMAIL = 'send_approval_email'; // 📧 Email aprobación
    case ADD_COMMENT = 'add_comment';               // 💬 Agregar comentario
    case REQUEST_ADDITIONAL_DOCS = 'request_additional_docs'; // 📎 Solicitar docs
    case MOVE_TO_NEXT_STAGE = 'move_to_next_stage'; // ➡️ Avanzar etapa
    case ACCESS_ADDITIONAL_FILES = 'access_additional_files'; // 📂 Archivos extra
}
```

### Matriz de Acciones por Etapa

| Acción | Documentación | Licencias | Contabilidad |
|--------|:---:|:---:|:---:|
| Aprobar | ✅ | ✅ | ✅ |
| Rechazar | ✅ | ❌ | ✅ |
| Email Aprobación | ✅ | ❌ | ✅ |
| Comentarios | ✅ | ✅ | ✅ |
| Solicitar Docs | ✅ | ✅ | ✅ |
| Archivos Adicionales | ❌ | ❌ | ✅ |

---

## ➕ Cómo Agregar Nueva Acción

### Paso 1: Agregar al Enum

**Archivo:** `app/Enums/Document/ValidationAction.php`

```php
enum ValidationAction: string {
    case APPROVE = 'approve';
    // ... acciones existentes ...
    case SCHEDULE_MEETING = 'schedule_meeting';  // 🆕 Nueva acción
}
```

### Paso 2: Permitir en Etapa Específica

**Archivo:** `config/validation-permissions.php`

```php
'contabilidad' => [
    'order' => 3,
    'label' => 'Contabilidad',
    'description' => 'Validación final de contabilidad',
    'allowed_actions' => [
        ValidationAction::APPROVE->value,
        ValidationAction::REJECT->value,
        ValidationAction::SEND_APPROVAL_EMAIL->value,
        ValidationAction::SCHEDULE_MEETING->value,  // 🆕 Nueva acción aquí
    ],
]
```

### Paso 3: Crear Vista para la Acción

**Archivo:** `resources/views/accountings/views/documents/manage.blade.php`

Dentro del card "Acciones de email" (línea 38-152):

```blade
@if($documentConfig['enable_schedule_meeting'] ?? true)
    <div class="mb-3">
        <label class="form-label fw-semibold mb-1">
            <i class="fas fa-calendar me-2"></i>Programar reunión
        </label>
        <p class="text-muted small mb-2">
            {{ $documentConfig['schedule_meeting_description'] ?? 'Programa una reunión con el cliente para discutir los documentos.' }}
        </p>
        <button type="button" class="btn btn-outline-primary w-100"
                data-bs-toggle="modal"
                data-bs-target="#scheduleMeetingModal">
            Programar
        </button>
    </div>
@endif
```

### Paso 4: Crear Modal para Capturar Datos

**Agregarlo antes de cerrar la vista (antes del `@endsection`):**

```blade
<!-- Schedule Meeting Modal -->
<div class="modal fade" id="scheduleMeetingModal" tabindex="-1" aria-labelledby="scheduleMeetingLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleMeetingLabel">
                    <i class="fas fa-calendar me-2"></i>Programar Reunión
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Fecha y Hora</label>
                    <input type="datetime-local" class="form-control" id="meetingDateTime" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" id="meetingDescription" rows="3" placeholder="¿Qué se discutirá?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmScheduleMeetingBtn">Programar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $('#confirmScheduleMeetingBtn').on('click', function() {
        const dateTime = $('#meetingDateTime').val();
        const description = $('#meetingDescription').val();
        const documentUid = '{{ $document->uid }}';

        if (!dateTime) {
            toastr.warning('Selecciona una fecha y hora', 'Atención');
            return;
        }

        $.ajax({
            url: `/accounting/documents/manage/${documentUid}/schedule-meeting`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('[name="_token"]').val()
            },
            dataType: 'json',
            data: JSON.stringify({
                meeting_datetime: dateTime,
                description: description
            }),
            contentType: 'application/json',
            success: function(data) {
                if (data.success) {
                    toastr.success('Reunión programada correctamente', 'Éxito');
                    $('#scheduleMeetingModal').modal('hide');
                    $('#meetingDateTime').val('');
                    $('#meetingDescription').val('');
                } else {
                    toastr.error('Error: ' + (data.message || 'No se pudo programar'), 'Error');
                }
            },
            error: function(error) {
                console.error('Error:', error);
                toastr.error('Error al programar la reunión', 'Error');
            }
        });
    });
</script>
@endpush
```

### Paso 5: Crear Endpoint en Controlador

**Archivo:** `app/Http/Controllers/Accountings/Documents/DocumentsController.php`

```php
public function scheduleMeeting(Request $request, $uid)
{
    $document = Document::where('uid', $uid)->firstOrFail();

    // Validar permiso
    $this->authorize('schedule-meeting', $document);

    $validated = $request->validate([
        'meeting_datetime' => 'required|date_format:Y-m-d\TH:i',
        'description' => 'nullable|string|max:1000',
    ]);

    // Crear evento de reunión (ej. en tabla meetings)
    DocumentMeeting::create([
        'document_id' => $document->id,
        'scheduled_for' => $validated['meeting_datetime'],
        'description' => $validated['description'],
        'created_by' => auth()->id(),
    ]);

    // Registrar acción en historial
    DocumentValidationHistory::create([
        'document_id' => $document->id,
        'action' => ValidationAction::SCHEDULE_MEETING->value,
        'stage' => $document->current_validator_group,
        'user_id' => auth()->id(),
        'data' => $validated,
    ]);

    // Enviar notificación al cliente (opcional)
    // ...

    return response()->json(['success' => true, 'message' => 'Reunión programada']);
}
```

---

## ➕ Cómo Agregar Nueva Etapa

### Paso 1: Crear Enum para ValidatorGroup

**Archivo:** `app/Enums/Document/ValidatorGroupEnum.php` (crear si no existe)

```php
enum ValidatorGroup: string {
    case DOCUMENTACION = 'documentacion';
    case LICENCIAS = 'licencias';
    case CONTABILIDAD = 'contabilidad';
    case AUDITORÍA = 'auditoria';  // 🆕 Nueva etapa
}
```

### Paso 2: Agregar Configuración

**Archivo:** `config/validation-permissions.php`

```php
'auditoria' => [
    'order' => 4,
    'label' => 'Auditoría',
    'description' => 'Auditoría final de cumplimiento regulatorio',
    'allowed_actions' => [
        ValidationAction::APPROVE->value,
        ValidationAction::REJECT->value,
        ValidationAction::ADD_COMMENT->value,
        ValidationAction::SEND_APPROVAL_EMAIL->value,
    ],
    'restricted_actions' => [
        ValidationAction::MOVE_TO_NEXT_STAGE->value,  // Sin siguiente etapa
    ],
    'notes' => 'Etapa final: Auditoría de cumplimiento',
],
```

### Paso 3: Crear Modelo ValidatorGroup (si no existe)

**Archivo:** `app/Models/Validation/ValidatorGroup.php`

```php
<?php

namespace App\Models\Validation;

use Illuminate\Database\Eloquent\Model;

class ValidatorGroup extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'order',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'validator_group_user');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'current_validator_group', 'key');
    }
}
```

### Paso 4: Crear Migración

```bash
php artisan make:migration add_auditoría_stage_to_validation --create
```

```php
public function up()
{
    DB::table('validator_groups')->insert([
        'key' => 'auditoria',
        'label' => 'Auditoría',
        'description' => 'Auditoría final de cumplimiento regulatorio',
        'order' => 4,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

---

## 🎯 Cómo Agregar Opción en Nav

### Paso 1: Actualizar Nav

**Archivo:** `resources/views/accountings/includes/nav.blade.php`

En la sección `<!-- Documentos Section -->`:

```blade
<!-- Documentos Section -->
<nav class="sidebar-nav scroll-sidebar d-none" id="menu-right-mini-2" data-simplebar="init">
    <ul class="sidebar-menu" id="sidebarnav-documentos">
        <li class="nav-small-cap">
            <span class="hide-menu">Documentos</span>
        </li>
        <li class="sidebar-item">
            <a href="{{ route('accounting.documents') }}" class="sidebar-link">
                <span class="hide-menu">Todos los documentos</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="{{ route('accounting.documents.in-review') }}" class="sidebar-link">
                <i class="fas fa-clock me-2"></i>
                <span class="hide-menu">En revisión</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="{{ route('accounting.documents.configuration-tags') }}" class="sidebar-link">
                <i class="fas fa-tags me-2"></i>
                <span class="hide-menu">Configuración de etiquetas</span>
            </a>
        </li>
    </ul>
</nav>
```

### Paso 2: Agregar Ruta

**Archivo:** `routes/accountings.php`

```php
Route::prefix('documents')->group(function () {
    Route::get('/', [DocumentsController::class, 'index'])
        ->name('accounting.documents');

    Route::get('/in-review', [DocumentsController::class, 'inReview'])
        ->name('accounting.documents.in-review');

    Route::get('/configuration-tags', [DocumentTagController::class, 'index'])
        ->name('accounting.documents.configuration-tags');

    Route::post('/tags', [DocumentTagController::class, 'store'])
        ->name('accounting.documents.tags.store');

    // ... otras rutas ...
});
```

### Paso 3: Crear Controlador (si es necesario)

```bash
php artisan make:controller Accountings/Document/DocumentTagController
```

```php
<?php

namespace App\Http\Controllers\Accountings\Documents;

use App\Models\Document\DocumentTag;
use Illuminate\Http\Request;

class DocumentTagController extends Controller
{
    public function index()
    {
        $tags = DocumentTag::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('accountings.views.documents.configuration-tags', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:document_tags',
            'color' => 'required|string',
            'description' => 'nullable|string',
        ]);

        DocumentTag::create($validated);

        return redirect()->route('accounting.documents.configuration-tags')
            ->with('success', 'Etiqueta creada correctamente');
    }
}
```

### Paso 4: Crear Vista

**Archivo:** `resources/views/accountings/views/documents/configuration-tags.blade.php`

```blade
@extends('layouts.accountings')

@section('title', 'Configuración de Etiquetas')

@section('content')

    @include('accountings.includes.card', ['title' => 'Configuración de etiquetas'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Etiquetas disponibles</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Color</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tags as $tag)
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: {{ $tag->color }}">
                                            {{ $tag->name }}
                                        </span>
                                    </td>
                                    <td>{{ $tag->color }}</td>
                                    <td>{{ $tag->description ?? '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning">Editar</button>
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        No hay etiquetas registradas
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Agregar etiqueta</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('accounting.documents.tags.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   name="name" required>
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color"
                                   name="color" value="#90bb13" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Agregar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
```

---

## 🔥 Ejemplos Prácticos

### Ejemplo 1: Agregar Acción "Requerir Videoconferencia"

**Paso 1: Enum**
```php
case REQUIRE_VIDEO_CALL = 'require_video_call';
```

**Paso 2: Configuración**
```php
'documentacion' => [
    'allowed_actions' => [
        // ... acciones existentes ...
        ValidationAction::REQUIRE_VIDEO_CALL->value,
    ],
]
```

**Paso 3: Vista**
```blade
<div class="mb-3">
    <label class="form-label fw-semibold mb-1">
        <i class="fas fa-video me-2"></i>Requerir videoconferencia
    </label>
    <p class="text-muted small mb-2">
        Solicita una llamada por video al cliente para clarificar documentos
    </p>
    <button type="button" class="btn btn-outline-primary w-100"
            data-bs-toggle="modal"
            data-bs-target="#videoCallModal">
        Solicitar llamada
    </button>
</div>
```

### Ejemplo 2: Agregar Filtro por Etiqueta en Index

**Archivo:** `resources/views/accountings/views/documents/index.blade.php`

```blade
<!-- Agregar después del filtro de estado -->
<div class="col-auto">
    <div class="input-group">
        <select class="form-select select2" name="tag_id">
            <option value="">Todas las etiquetas</option>
            @foreach(DocumentTag::where('is_active', true)->get() as $tag)
                <option value="{{ $tag->id }}" {{ ($tagId ?? '') == $tag->id ? 'selected' : '' }}>
                    <span class="badge" style="background-color: {{ $tag->color }}">{{ $tag->name }}</span>
                </option>
            @endforeach
        </select>
    </div>
</div>
```

**Controlador:**
```php
public function index(Request $request)
{
    $tagId = $request->get('tag_id');

    $documents = Document::filterListing($search, null, $dateFrom, $dateTo)
        ->when($statusId, fn ($q) => $q->where('status_id', $statusId))
        ->when($tagId, fn ($q) => $q->whereHas('tags', fn($q) => $q->where('id', $tagId)))
        ->paginate($perPage);

    // ...
}
```

---

## 📚 Checklist para Agregar Nueva Funcionalidad

```
☐ 1. Agregar acción en ValidationAction enum
☐ 2. Permitir en etapa(s) en config/validation-permissions.php
☐ 3. Crear vista/modal en manage.blade.php
☐ 4. Crear endpoint en controlador
☐ 5. Crear ruta en routes/accountings.php
☐ 6. Registrar en DocumentValidationHistory
☐ 7. Enviar notificación si es necesario
☐ 8. Agregar al nav si es sección nueva
☐ 9. Crear tests para validación
☐ 10. Documentar en docs/
```

---

## 🚀 Resumen Rápido

| Elemento | Archivo | Cambios |
|----------|---------|---------|
| Nueva Acción | `app/Enums/Document/ValidationAction.php` | + case |
| Permitir Acción | `config/validation-permissions.php` | Agregar al array |
| UI de Acción | `manage.blade.php` | + div con modal |
| Lógica de Acción | `DocumentsController.php` | + método público |
| Ruta de Acción | `routes/accountings.php` | + Route::post |
| Nueva Sección Nav | `nav.blade.php` | + li sidebar-item |
| Nueva Configuración | `configuration-tags.blade.php` | Vista + Controlador |

