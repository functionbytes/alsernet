@extends('layouts.theme')
@section('title', 'Formularios · Gestión')
@section('page_header')
    @include('core::components.card', ['title' => 'Formularios · Gestión'])
@endsection

@section('content')

<div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
    <div>
        <h1 class="h4 mb-0 fw-bold">
            <i class="far fa-file-lines text-primary me-2"></i>Gestión de formularios
        </h1>
        <p class="text-muted small mb-0 mt-1">
            Cada fila mapea el <code>form_key</code> que envía alsernetforms (PrestaShop) a una categoría de ticket. Desactivar un formulario aquí hace que sus envíos se rechacen (el cron de alsernetforms los reintentará hasta agotar los intentos).
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('forms.manage.export') }}" class="btn btn-light">Exportar</a>
        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#importModal">Importar</button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" data-mode="create">
            Nuevo formulario
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Form "vacío" (sin envolver la tabla): los checkboxes de cada fila usan
     el atributo HTML5 form="bulkForm" para asociarse sin anidar <form>
     dentro de <form> (cada fila ya tiene sus propios forms de toggle/eliminar,
     y anidar forms es HTML inválido -- el navegador los reordena de forma
     impredecible al parsear). --}}
<form method="POST" action="{{ route('forms.manage.bulk') }}" id="bulkForm">
    @csrf
    <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
</form>

<div id="bulkBar" class="alert alert-primary d-none align-items-center justify-content-between mb-3">
    <span><span id="bulkCount">0</span> seleccionado(s)</span>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-light" data-bulk-action="activate">Activar seleccionados</button>
        <button type="button" class="btn btn-sm btn-light" data-bulk-action="deactivate">Desactivar seleccionados</button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" class="form-check-input" id="bulkSelectAll"></th>
                            <th>Formulario</th>
                            <th>form_key</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forms as $form)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input bulk-checkbox" name="ids[]" value="{{ $form->id }}" form="bulkForm">
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $form->name }}</div>
                                    @if($form->description)
                                        <div class="text-muted small">{{ $form->description }}</div>
                                    @endif
                                </td>
                                <td><code>{{ $form->form_key }}</code></td>
                                <td>
                                    @if($form->category)
                                        {{ $form->category->name }}
                                    @else
                                        <span class="text-danger small">Sin categoría</span>
                                    @endif
                                </td>
                                <td>
                                    @if($form->active)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item form-edit-btn" href="#"
                                                   data-bs-toggle="modal" data-bs-target="#formModal"
                                                   data-mode="edit"
                                                   data-id="{{ $form->id }}"
                                                   data-name="{{ $form->name }}"
                                                   data-form-key="{{ $form->form_key }}"
                                                   data-category-id="{{ $form->category_id }}"
                                                   data-description="{{ $form->description }}"
                                                   data-active="{{ $form->active ? 1 : 0 }}"
                                                   data-update-url="{{ route('forms.manage.update', $form) }}">
                                                    Editar
                                                </a>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('forms.manage.toggle', $form) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        {{ $form->active ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="{{ route('forms.manage.destroy', $form) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item">Eliminar</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay formularios creados todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Historial de cambios reciente</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cuándo</th>
                        <th>Formulario</th>
                        <th>Acción</th>
                        <th>Usuario</th>
                        <th>Cambios</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentChanges as $change)
                        <tr>
                            <td class="text-muted small">{{ $change->created_at?->format('d/m/Y H:i') }}</td>
                            <td><code>{{ $change->form_key }}</code></td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $change->action }}</span>
                            </td>
                            <td class="small">{{ $change->user_name ?? '—' }}</td>
                            <td class="small text-muted">
                                @if($change->changes)
                                    @foreach($change->changes as $field => $values)
                                        <div>{{ $field }}: {{ $values[0] ?? '—' }} → {{ $values[1] ?? '—' }}</div>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Sin cambios registrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('forms.manage.store') }}" id="formModalForm">
                @csrf
                <input type="hidden" name="_method" id="formModalMethod" value="POST">

                <div class="modal-header">
                    <h5 class="modal-title" id="formModalTitle">Nuevo formulario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small" for="fm-name">Nombre</label>
                        <input type="text" name="name" id="fm-name" class="form-control" maxlength="255" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" for="fm-form-key">form_key</label>
                        <input type="text" name="form_key" id="fm-form-key" class="form-control" maxlength="100" required>
                        <div class="form-text">Debe coincidir exactamente con el valor que envía alsernetforms en el campo "type".</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" for="fm-category">Categoría de ticket</label>
                        <select name="category_id" id="fm-category" class="form-select">
                            <option value="">— Ninguna —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" for="fm-description">Descripción (opcional)</label>
                        <textarea name="description" id="fm-description" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" for="fm-active">Estado</label>
                        <select name="active" id="fm-active" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('forms.manage.import') }}" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Importar formularios</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted small">
                        Sube un archivo exportado con el botón "Exportar". Cada fila se aplica por <code>form_key</code>
                        (crea si no existe, actualiza si ya existe); las filas sin <code>form_key</code> o con una
                        categoría desconocida se omiten.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small" for="fm-import-file">Archivo JSON</label>
                        <input type="file" name="file" id="fm-import-file" class="form-control" accept="application/json,.json" required>
                    </div>
                </div>

                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Importar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('formModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            var form = document.getElementById('formModalForm');
            var method = document.getElementById('formModalMethod');
            var title = document.getElementById('formModalTitle');

            if (!trigger || trigger.getAttribute('data-mode') === 'create') {
                title.textContent = 'Nuevo formulario';
                form.setAttribute('action', '{{ route('forms.manage.store') }}');
                method.value = 'POST';
                form.reset();
                document.getElementById('fm-active').value = '1';
                return;
            }

            title.textContent = 'Editar formulario';
            form.setAttribute('action', trigger.getAttribute('data-update-url'));
            method.value = 'PUT';
            document.getElementById('fm-name').value = trigger.getAttribute('data-name') || '';
            document.getElementById('fm-form-key').value = trigger.getAttribute('data-form-key') || '';
            document.getElementById('fm-category').value = trigger.getAttribute('data-category-id') || '';
            document.getElementById('fm-description').value = trigger.getAttribute('data-description') || '';
            document.getElementById('fm-active').value = trigger.getAttribute('data-active') || '1';
        });
    }

    // ── Bulk selection ──────────────────────────────────────────────────
    var selectAll = document.getElementById('bulkSelectAll');
    var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.bulk-checkbox'));
    var bulkBar = document.getElementById('bulkBar');
    var bulkCount = document.getElementById('bulkCount');
    var bulkForm = document.getElementById('bulkForm');
    var bulkActionInput = document.getElementById('bulkActionInput');

    function refreshBulkBar() {
        var checked = checkboxes.filter(function (cb) { return cb.checked; });
        bulkCount.textContent = checked.length;
        bulkBar.classList.toggle('d-none', checked.length === 0);
        bulkBar.classList.toggle('d-flex', checked.length > 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            refreshBulkBar();
        });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', refreshBulkBar);
    });

    document.querySelectorAll('[data-bulk-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            bulkActionInput.value = btn.getAttribute('data-bulk-action');
            bulkForm.submit();
        });
    });
})();
</script>
@endpush
