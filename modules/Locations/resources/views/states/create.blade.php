@extends('layouts.theme')

@section('title', 'Nuevo estado')

@section('content')

    @include('core::components.card', ['title' => 'Nuevo estado'])

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('locations.states.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo estado o provincia</h5>
                        <small class="text-muted">Complete la información del estado o provincia.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">País <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('country_id') is-invalid @enderror"
                                        name="country_id" required>
                                    <option value="">Seleccionar país...</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}"
                                            {{ old('country_id', $selectedCountryId ?? '') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-8">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Jalisco, Antioquia, Cataluña"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Código</label>
                                <input type="text"
                                       class="form-control @error('code') is-invalid @enderror"
                                       name="code"
                                       value="{{ old('code') }}"
                                       placeholder="Ej: JAL, CAT">
                                <small class="form-text text-muted">Opcional</small>
                                @error('code')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Orden</label>
                                <input type="number"
                                       class="form-control @error('order') is-invalid @enderror"
                                       name="order"
                                       value="{{ old('order', 0) }}"
                                       min="0">
                                @error('order')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar estado</button>
                        <a href="{{ route('locations.states.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los estados</h6>
                    <p class="card-text text-muted">
                        Los estados y provincias pertenecen a un país y son el segundo nivel de la jerarquía geográfica. Cada estado puede contener múltiples ciudades.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Campos requeridos</h6>
                    <p class="card-text text-muted mb-0">
                        Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush
