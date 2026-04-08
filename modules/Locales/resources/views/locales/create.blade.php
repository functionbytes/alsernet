@extends('layouts.theme')

@section('title', 'Nuevo idioma')

@section('content')

    @include('core::components.card', ['title' => 'Nuevo idioma'])

    @include('core::components.alerts')

    <form action="{{ route('locales.store') }}" method="POST" id="createForm" novalidate>
        @csrf

        <div class="card">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-0 fw-bold">Datos del idioma</h5>
            </div>
            <div class="card-body">

                <div class="row g-4">

                    {{-- Código --}}
                    <div class="col-md-6">
                        <label for="code" class="form-label fw-semibold">
                            Código <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="code"
                               name="code"
                               class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code') }}"
                               placeholder="es"
                               maxlength="10"
                               autofocus
                               required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text">Código ISO (es, en, pt, fr...)</div>
                        @enderror
                    </div>

                    {{-- Nombre en inglés --}}
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">
                            Nombre en inglés <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="Spanish"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Nombre nativo --}}
                    <div class="col-md-6">
                        <label for="native_name" class="form-label fw-semibold">
                            Nombre nativo <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="native_name"
                               name="native_name"
                               class="form-control @error('native_name') is-invalid @enderror"
                               value="{{ old('native_name') }}"
                               placeholder="Español"
                               required>
                        @error('native_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Bandera --}}
                    <div class="col-md-6">
                        <label for="flag" class="form-label fw-semibold">Bandera</label>
                        <input type="text"
                               id="flag"
                               name="flag"
                               class="form-control @error('flag') is-invalid @enderror"
                               value="{{ old('flag') }}"
                               placeholder="🇪🇸">
                        @error('flag')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text">Emoji de bandera</div>
                        @enderror
                    </div>

                    {{-- Orden --}}
                    <div class="col-md-6">
                        <label for="order" class="form-label fw-semibold">Orden</label>
                        <input type="number"
                               id="order"
                               name="order"
                               class="form-control @error('order') is-invalid @enderror"
                               value="{{ old('order', 0) }}"
                               min="0">
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Activo --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold d-block">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Activo</label>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                    Guardar
                </button>
                <a href="{{ route('locales.index') }}" class="btn btn-secondary w-100">
                    Cancelar
                </a>
            </div>

        </div>

    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#createForm').on('submit', function () {
        $('#submitBtn')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
