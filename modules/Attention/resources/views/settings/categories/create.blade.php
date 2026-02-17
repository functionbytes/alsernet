@extends('layouts.theme')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('settings.attention.categories.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-1 fw-bold">Crear categoría</h5>
                        <p class="mb-0 text-muted small">Complete la información para crear una nueva categoría.</p>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="ej: Servicios Públicos" required>
                                @error('name')<span class="field-validation-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')<span class="field-validation-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-6 mb-3">
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
                        <a href="{{ route('settings.attention.categories.index') }}" class="btn btn-secondary w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
