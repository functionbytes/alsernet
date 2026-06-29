@extends('layouts.theme')

@section('title', 'Administrador de traducciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Administrador de traducciones'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Administrador de traducciones</h5>
                        <p class="small mb-0 text-muted">Gestiona todas las traducciones de la plataforma por idioma y archivo</p>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="card-body">
                {{-- Buscador --}}
                <div class="row mb-3">
                    <div class="col-12 col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control -0"
                                   id="searchTranslations"
                                   placeholder="Buscar archivos o idiomas..."
                                   value="{{ $searchQuery }}">
                        </div>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <small><strong>Nota:</strong> Los cambios se guardarán directamente en los archivos de lenguaje del sistema.</small>
                </div>

                @if(empty($translationsByFile))
                    <div class="text-center py-5">
                        <i class="fas fa-file-code fa-4x text-muted opacity-50 mb-3 d-block"></i>
                        <p class="text-muted mb-0">No hay archivos de traducción disponibles.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="translationsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Archivo</th>
                                    <th class="text-center">Claves</th>
                                    <th class="text-center">Idiomas</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($translationsByFile as $file => $fileData)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-language text-primary fs-5"></i>
                                                <div>
                                                    <span class="fw-semibold d-block">{{ $fileData['label'] }}</span>
                                                    <small class="text-muted"><code>{{ $file }}.php</code></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">
                                                {{ count($fileData['locales']) > 0 ? $fileData['locales'][0]['count'] : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                @foreach($fileData['locales'] as $localeData)
                                                    <span class="badge bg-primary">{{ strtoupper($localeData['locale']) }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @foreach($fileData['locales'] as $localeData)
                                                    <a href="{{ route('manager.backups.translations.edit', [$localeData['locale'], $file]) }}"
                                                       class="btn btn-outline-primary"
                                                       title="Editar {{ $localeData['locale_label'] }}">
                                                        <i class="fas fa-edit me-1"></i>{{ strtoupper($localeData['locale']) }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#searchTranslations').on('input', function () {
        var query = $(this).val().toLowerCase();
        $('#translationsTable tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(query));
        });
    });
});
</script>
@endpush
