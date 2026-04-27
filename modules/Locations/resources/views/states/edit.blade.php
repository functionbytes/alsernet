@extends('layouts.theme')

@section('title', 'Editar estado')

@section('content')

    @include('core::components.card', ['title' => 'Editar estado'])

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('locations.states.update', $state) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $state->name }}</h5>
                        <small class="text-muted">Actualiza la información del estado o provincia.</small>
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
                                            {{ old('country_id', $state->country_id) == $country->id ? 'selected' : '' }}>
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
                                       value="{{ old('name', $state->name) }}"
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
                                       value="{{ old('code', $state->code) }}"
                                       placeholder="Ej: JAL, CAT">
                                <small class="form-text text-muted">Opcional</small>
                                @error('code')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', $state->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active', $state->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Orden</label>
                                <input type="number"
                                       class="form-control @error('order') is-invalid @enderror"
                                       name="order"
                                       value="{{ old('order', $state->order) }}"
                                       min="0">
                                @error('order')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Actualizar estado</button>
                        <a href="{{ route('locations.states.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Información del registro</h6>
                    <dl class="mb-0">
                        <dt class="small text-muted">ID</dt>
                        <dd class="mb-2">#{{ $state->id }}</dd>
                        <dt class="small text-muted">País</dt>
                        <dd class="mb-2">{{ $state->country?->flag_emoji }} {{ $state->country?->name }}</dd>
                        <dt class="small text-muted">Creado</dt>
                        <dd class="mb-2">{{ $state->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="small text-muted">Última actualización</dt>
                        <dd class="mb-0">{{ $state->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
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
