@extends('layouts.theme')

@section('title', 'Editar ciudad')

@section('content')

    @include('core::components.card', ['title' => 'Editar ciudad'])

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('locations.cities.update', $city) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $city->name }}</h5>
                        <small class="text-muted">Actualiza la información de la ciudad.</small>
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
                                        <option value="{{ $country->id }}" {{ old('country_id', $city->country_id) == $country->id ? 'selected' : '' }}>
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
                                        <option value="{{ $state->id }}" {{ old('state_id', $city->state_id) == $state->id ? 'selected' : '' }}>
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
                                       value="{{ old('name', $city->name) }}"
                                       placeholder="Ej: Guadalajara, Bogotá, Barcelona"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', $city->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active', $city->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Orden</label>
                                <input type="number"
                                       class="form-control @error('order') is-invalid @enderror"
                                       name="order"
                                       value="{{ old('order', $city->order) }}"
                                       min="0">
                                @error('order')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Actualizar ciudad</button>
                        <a href="{{ route('locations.cities.index') }}" class="btn btn-light w-100">Cancelar</a>
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
                        <dd class="mb-2">#{{ $city->id }}</dd>
                        <dt class="small text-muted">País</dt>
                        <dd class="mb-2">{{ $city->country?->flag_emoji }} {{ $city->country?->name }}</dd>
                        <dt class="small text-muted">Estado</dt>
                        <dd class="mb-2">{{ $city->state?->name }}</dd>
                        <dt class="small text-muted">Creado</dt>
                        <dd class="mb-2">{{ $city->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="small text-muted">Última actualización</dt>
                        <dd class="mb-0">{{ $city->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const statesUrl = '{{ route('api.locations.states') }}';
    const currentStateId = '{{ old('state_id', $city->state_id) }}';

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
                const selected = s.id == currentStateId ? ' selected' : '';
                options += `<option value="${s.id}"${selected}>${s.name}</option>`;
            });
            $stateSelect.html(options);
        });
    });
});
</script>
@endpush
