@extends('layouts.theme')

@section('title', 'Editar traducciones')

@section('content')

    @include('core::components.card', ['title' => 'Editar traducciones'])

    @include('core::components.alerts')

    <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-circle-info"></i>
        <span>Los cambios en las traducciones se aplican inmediatamente al sitio.</span>
    </div>

    <form action="{{ route('locales.translations.update', ['locale' => $locale, 'group' => $group]) }}"
          method="POST"
          id="translationsForm"
          novalidate>
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header p-3 bg-white border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">
                        Traducciones —
                        <code class="text-primary fs-6">{{ $group }}</code>
                        /
                        <code class="text-primary fs-6">{{ $locale }}</code>
                    </h5>
                    <span class="ms-auto badge bg-light-secondary text-secondary">
                        {{ count($translations) }} claves
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                @if (empty($translations))
                    <div class="text-center py-5">
                        <h6 class="mb-1">No hay traducciones disponibles</h6>
                        <p class="text-muted">No se encontró el archivo de traducciones para este grupo e idioma.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%">Clave</th>
                                    <th>Traducción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($translations as $key => $value)
                                    <tr>
                                        <td class="align-middle">
                                            <code class="text-muted small">{{ $key }}</code>
                                        </td>
                                        <td>
                                            <input type="text"
                                                   name="translations[{{ $key }}]"
                                                   class="form-control form-control-sm"
                                                   value="{{ old('translations.' . $key, $value) }}"
                                                   placeholder="{{ $key }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if (!empty($translations))
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                        Guardar traducciones
                    </button>
                    <a href="{{ route('locales.translations.index') }}" class="btn btn-secondary w-100">
                        Cancelar
                    </a>
                </div>
            @endif

        </div>

    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#translationsForm').on('submit', function () {
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
