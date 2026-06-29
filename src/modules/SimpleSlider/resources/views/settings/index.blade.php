@extends('layouts.theme')

@section('title', 'Controles deslizantes - Ajustes')

@section('content')
    <h4>Controles deslizantes simples</h4>
    <p class="text-muted small">Configuración</p>

    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.simple-sliders.update') }}">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-body">

                <div class="form-check mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="use_default_config"
                        id="use_default_config"
                        value="1"
                        {{ $get('use_default_config', '1') === '1' ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="use_default_config">
                        ¿Utilizar configuración predeterminada?
                    </label>
                </div>

                <div class="bg-dark text-white rounded p-3 small font-monospace">
                    /vendor/core/plugins/simple-slider/libraries/owl-carousel/owl.carousel.css<br>
                    /vendor/core/plugins/simple-slider/css/simple-slider.css<br>
                    /vendor/core/plugins/simple-slider/libraries/owl-carousel/owl.carousel.js<br>
                    /vendor/core/plugins/simple-slider/js/simple-slider.js
                </div>

                <p class="text-muted small mt-2 mb-0">Si usas configuración predeterminada, los siguientes scripts se agregarán automáticamente al sitio.</p>

            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">
            <i class="fas fa-save me-1"></i> Guardar ajustes
        </button>

    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if (session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});
</script>
@endpush
