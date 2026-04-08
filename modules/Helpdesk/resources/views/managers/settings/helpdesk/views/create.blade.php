@extends('layouts.theme')

@section('title', 'Crear Vista')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">Crear Nueva Vista</h4>
                    <p class="text-muted mb-0">Define filtros personalizados para organizar conversaciones</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('manager.helpdesk.backups.tickets.views.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Cancelar</a>
                    <button type="submit" form="viewForm" class="btn btn-primary"><i class="fa fa-check"></i> Crear Vista</button>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.views.store') }}" id="viewForm">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Vista</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: Tickets Abiertos">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional">{{ old('description') }}</textarea>
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
                                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Grupo</label>
                                <select name="filters[group_id]" class="form-select">
                                    <option value="">Todos los grupos</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
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
                            <input type="checkbox" name="is_public" class="form-check-input" id="publicCheck" value="1">
                            <label class="form-check-label" for="publicCheck"><strong>Vista Pública</strong><small class="d-block text-muted">Visible para todos los agentes</small></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_default" class="form-check-input" id="defaultCheck" value="1">
                            <label class="form-check-label" for="defaultCheck"><strong>Vista Por defecto</strong><small class="d-block text-muted">Tu vista predeterminada</small></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
