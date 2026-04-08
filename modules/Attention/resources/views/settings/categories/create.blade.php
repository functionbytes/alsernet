@extends('layouts.theme')

@section('title', 'Crear categoría')

@section('content')

    @include('core::components.card', ['title' => 'Crear categoría'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.attention.categories.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva categoría</h5>
                        <small class="text-muted">Complete la información para crear una nueva categoría de PQRSF.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{ old('name') }}"
                                   placeholder="ej: Servicios Públicos" required>
                            @error('name')
                                <span class="field-validation-error">{{ $message }}</span>
                            @enderror
                        </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select select2" name="is_active">
                                    <option value="1" selected>Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      name="description" rows="3"
                                      placeholder="Describe el tema que agrupa esta categoría">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="field-validation-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                            <a href="{{ route('settings.attention.categories.index') }}" class="btn btn-light w-100 ">Cancelar</a>
                        </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es una categoría?</h6>
                    <p class="card-text text-muted">
                        Las categorías clasifican las PQRSF por tema (Servicios, Infraestructura, Salud, etc.), facilitando el enrutamiento y los reportes.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas prácticas</h6>
                    <ul class="text-muted ps-3 mb-0">
                        <li class="mb-2">Usa nombres claros y descriptivos</li>
                        <li class="mb-2">Evita categorías duplicadas o muy similares</li>
                        <li>Desactiva las categorías que ya no estén en uso</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
