@extends('layouts.theme')

@section('title', 'Nueva ciudad')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva ciudad'])
@endsection

@section('content')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('locations.cities.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva ciudad</h5>
                        <small class="text-muted">Complete la información de la ciudad.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">País <span class="text-danger">*</span></label>
                                <select id="country_id" class="form-select select2 @error('country_id') is-invalid @enderror"
                                        name="country_id">
                                    <option value="">Seleccionar país...</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado <span class="text-danger">*</span></label>
                                <select id="state_id" class="form-select @error('state_id') is-invalid @enderror"
                                        name="state_id" required>
                                    <option value="">Seleccionar estado...</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>
                                            {{ $state->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('state_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Guadalajara, Bogotá, Barcelona"
                                       required>
                                @error('name')
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
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar ciudad</button>
                        <a href="{{ route('locations.cities.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las ciudades</h6>
                    <p class="card-text text-muted">
                        Las ciudades son el tercer nivel de la jerarquía geográfica. Pertenecen a un estado y a un país.
                    </p>
                    <p class="card-text text-muted mb-0">
                        Selecciona primero el país para cargar los estados disponibles.
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
    const statesUrl = '{{ route('api.locations.states') }}';

    $('.select2').select2({ width: '100%' });

    $('#country_id').on('change', function () {
        const countryId = $(this).val();
        const $stateSelect = $('#state_id');

        if (!countryId) {
            $stateSelect.html('<option value="">Seleccionar estado...</option>');
            return;
        }

        $.get(statesUrl, { country_id: countryId }, function (data) {
            let options = '<option value="">Seleccionar estado...</option>';
            data.forEach(function (s) {
                options += `<option value="${s.id}">${s.name}</option>`;
            });
            $stateSelect.html(options);
        });
    });
});
</script>
@endpush
