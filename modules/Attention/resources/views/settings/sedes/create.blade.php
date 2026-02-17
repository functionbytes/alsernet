@extends('layouts.theme')
@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <form action="{{ route('settings.attention.sedes.store') }}" method="POST">
                @csrf
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-1 fw-bold">Crear sede</h5>
                </div>
                <div class="card-body">
                    @include('core::components.alerts')
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="ej: Sede Principal" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Dirección <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address" value="{{ old('address') }}" placeholder="Calle 123 #45-67" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="(1) 234-5678">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="sede@entidad.gov.co">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Horario de atención</label>
                            <input type="text" class="form-control" name="schedule" value="{{ old('schedule') }}" placeholder="Lun-Vie 8:00-17:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="is_active">
                                <option value="1" selected>Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Crear</button>
                    <a href="{{ route('settings.attention.sedes.index') }}" class="btn btn-secondary w-100">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
