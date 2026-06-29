@extends('layouts.theme')

@section('title', 'Agregar competidor')

@section('page_header')
    @include('core::components.card', ['title' => 'Agregar competidor'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('reviews.competitors.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo competidor</h5>
                        <small class="text-muted">Complete la información para registrar un nuevo competidor.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Ubicacion <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="location_id" required>
                                        <option value="">Seleccionar ubicacion...</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Ubicacion de tu negocio a comparar</small>
                                    @error('location_id')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del competidor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           name="name" value="{{ old('name') }}"
                                           maxlength="150" required placeholder="ej: Restaurante La Competencia">
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">URL de Google Maps <span class="text-danger">*</span></label>
                                    <input type="url" class="form-control @error('google_maps_url') is-invalid @enderror"
                                           name="google_maps_url" value="{{ old('google_maps_url') }}"
                                           maxlength="500" required placeholder="https://maps.google.com/...">
                                    @error('google_maps_url')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Google Place ID <small class="text-muted">(opcional)</small></label>
                                    <input type="text" class="form-control @error('place_id') is-invalid @enderror"
                                           name="place_id" value="{{ old('place_id') }}"
                                           maxlength="200" placeholder="ChIJ...">
                                    <small class="form-text text-muted">Permite actualizar el rating automaticamente via Google Places API</small>
                                    @error('place_id')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Agregar competidor</button>
                        <a href="{{ route('reviews.competitors.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es un competidor?</h6>
                    <p class="card-text text-muted">
                        Un competidor es otro negocio en Google Maps con el que deseas comparar tu rating y número de reseñas.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">URL de Google Maps</h6>
                    <p class="card-text text-muted mb-0">
                        Abre el negocio en Google Maps y copia la URL de la barra de dirección del navegador.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Google Place ID</h6>
                    <p class="card-text text-muted mb-0">
                        El Place ID permite actualizar automáticamente el rating del competidor. Puedes encontrarlo en Google Maps Platform.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('select[name="location_id"]').select2('destroy').select2({
        width: '100%',
        placeholder: 'Seleccionar ubicacion...',
    });
});
</script>
@endpush
