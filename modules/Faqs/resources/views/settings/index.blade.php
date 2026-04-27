@extends('layouts.theme')

@section('title', 'FAQ - Ajustes')

@section('content')

    @include('core::components.card', ['title' => 'FAQ'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <h4 class="fw-bold mb-0">FAQ</h4>
        <p class="text-muted small mb-3">Configuración de preguntas frecuentes</p>

        <form method="POST" action="{{ route('settings.faqs.update') }}">
            @csrf
            @method('PATCH')

            <div class="card mb-3">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="faqs_enabled" value="1"
                               id="faqs_enabled"
                               {{ ($settings['faqs_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="faqs_enabled">
                            ¿Habilitar preguntas frecuentes?
                        </label>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        Leer más: <a href="https://developers.google.com/search/docs/data-types/faqpage" target="_blank">https://developers.google.com/search/docs/data-types/faqpage</a>
                    </p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
        </form>

    </div>

@endsection

@push('scripts')
<script>
(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
})();
</script>
@endpush
