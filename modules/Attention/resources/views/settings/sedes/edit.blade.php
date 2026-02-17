@extends('layouts.theme')
@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <form action="{{ route('settings.attention.sedes.update', $sede->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-1 fw-bold">Editar sede</h5>
                </div>
                <div class="card-body">
                    @include('core::components.alerts')
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $sede->name) }}" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Dirección <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address" value="{{ old('address', $sede->address) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $sede->phone) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $sede->email) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Horario de atención</label>
                            <input type="text" class="form-control" name="schedule" value="{{ old('schedule', $sede->schedule) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="is_active">
                                <option value="1" {{ old('is_active', $sede->is_active) == 1 ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('is_active', $sede->is_active) == 0 ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar</button>
                    <a href="{{ route('settings.attention.sedes.index') }}" class="btn btn-secondary w-100">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
