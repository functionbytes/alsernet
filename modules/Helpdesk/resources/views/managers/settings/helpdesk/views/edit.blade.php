@extends('layouts.theme')

@section('title', 'Editar Vista - ' . $view->name)

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">Editar Vista</h4>
                    <p class="text-muted mb-0">{{ $view->name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('manager.helpdesk.backups.tickets.views.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Cancelar</a>
                    <button type="submit" form="viewForm" class="btn btn-primary" id="saveBtn" disabled><i class="fa fa-check"></i> Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.views.update', $view->id) }}" id="viewForm">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Vista</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $view->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $view->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="filters[status_id]" class="form-select">
                                    <option value="">Todos los estados</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->id }}" {{ ($view->filters['status_id'] ?? '') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Grupo</label>
                                <select name="filters[group_id]" class="form-select">
                                    <option value="">Todos los grupos</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ ($view->filters['group_id'] ?? '') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-cog"></i> Opciones</h5></div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_public" class="form-check-input" id="publicCheck" value="1" {{ $view->is_public ? 'checked' : '' }}>
                            <label class="form-check-label" for="publicCheck"><strong>Vista Pública</strong><small class="d-block text-muted">Visible para todos los agentes</small></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_default" class="form-check-input" id="defaultCheck" value="1" {{ $view->is_default ? 'checked' : '' }}>
                            <label class="form-check-label" for="defaultCheck"><strong>Vista Por defecto</strong><small class="d-block text-muted">Tu vista predeterminada</small></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const form = $('#viewForm');
    const saveBtn = $('#saveBtn');
    let originalFormData = form.serialize();

    function checkFormDirty() {
        saveBtn.prop('disabled', originalFormData === form.serialize());
    }

    form.on('change input', 'input, select, textarea', checkFormDirty);
    form.on('submit', function() { saveBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...'); });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Vista actualizada');
        setTimeout(function() { originalFormData = form.serialize(); checkFormDirty(); }, 100);
    @endif
});
</script>
@endpush
