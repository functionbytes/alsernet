@extends('layouts.theme')

@section('title', 'Editar sede')

@section('content')

    @include('core::components.card', ['title' => 'Editar sede'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.attention.sedes.update', $sede->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar sede</h5>
                        <small class="text-muted">Modifique la información de la sede de atención.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                       value="{{ old('name', $sede->name) }}" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Dirección <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="address"
                                       value="{{ old('address', $sede->address) }}" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Correo electrico</label>
                                <input type="email" class="form-control" name="email"
                                       value="{{ old('email', $sede->email) }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" name="city"
                                       value="{{ old('city', $sede->city) }}" placeholder="Bogotá">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="phone"
                                       value="{{ old('phone', $sede->phone) }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Horario de atención</label>
                                <input type="text" class="form-control" name="schedule"
                                       value="{{ old('schedule', $sede->schedule) }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select select2" name="is_active">
                                    <option value="1" {{ old('is_active', $sede->is_active) == 1 ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active', $sede->is_active) == 0 ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                            <a href="{{ route('settings.attention.sedes.index') }}" class="btn btn-light  w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es una sede?</h6>
                    <p class="card-text text-muted">
                        Las sedes representan los puntos físicos donde los ciudadanos pueden presentar sus solicitudes PQRSF de forma presencial.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Información útil</h6>
                    <ul class="text-muted ps-3 mb-0">
                        <li class="mb-2">Incluye el horario exacto para guiar a los ciudadanos</li>
                        <li class="mb-2">El teléfono y email permiten contacto directo con la sede</li>
                        <li>Desactiva la sede si deja de operar temporalmente</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
