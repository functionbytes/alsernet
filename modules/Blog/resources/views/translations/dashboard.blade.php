@extends('layouts.theme')

@section('title', 'Traducciones')

@section('content')
    @include('core::components.card', ['title' => 'Blog - Dashboard de traducciones'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        {{-- Cobertura por idioma --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Cobertura de traducciones</h5>
                        <p class="small mb-0 text-muted">Estado de las traducciones por idioma ({{ $totalPosts }} posts totales)</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('blog.translations.export') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-1"></i> Exportar CSV
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($locales as $locale)
                        @php $stats = $coverage[$locale]; @endphp
                        <div class="col-md-4">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0">{{ strtoupper($locale) }}</h6>
                                        <span class="badge bg-{{ $stats['percentage'] >= 80 ? 'success' : ($stats['percentage'] >= 50 ? 'warning' : 'danger') }}">
                                            {{ $stats['percentage'] }}%
                                        </span>
                                    </div>

                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $totalPosts > 0 ? ($stats['reviewed'] / $totalPosts * 100) : 0 }}%"
                                             title="Revisadas: {{ $stats['reviewed'] }}"></div>
                                        <div class="progress-bar bg-info" style="width: {{ $totalPosts > 0 ? ($stats['auto_translated'] / $totalPosts * 100) : 0 }}%"
                                             title="Auto-traducidas: {{ $stats['auto_translated'] }}"></div>
                                        <div class="progress-bar bg-warning" style="width: {{ $totalPosts > 0 ? ($stats['stale'] / $totalPosts * 100) : 0 }}%"
                                             title="Obsoletas: {{ $stats['stale'] }}"></div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2">
                                        <small><span class="badge bg-success">{{ $stats['reviewed'] }}</span> revisadas</small>
                                        <small><span class="badge bg-info">{{ $stats['auto_translated'] }}</span> auto</small>
                                        <small><span class="badge bg-warning">{{ $stats['stale'] }}</span> obsoletas</small>
                                        <small><span class="badge bg-danger">{{ $stats['missing'] }}</span> sin traducir</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Métricas de uso --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-1 fw-bold">Uso de DeepL (últimos 30 días)</h5>
                <p class="small mb-0 text-muted">Caracteres consumidos y traducciones realizadas</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Traducciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($metrics['total_translations']) }}</h4>
                                <small class="text-muted">Solicitudes realizadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Caracteres</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($metrics['total_characters']) }}</h4>
                                <small class="text-muted">Caracteres traducidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Por idioma</h6>
                                @foreach($metrics['by_locale'] as $loc => $data)
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">{{ strtoupper($loc) }}</small>
                                        <small class="fw-bold">{{ $data['count'] }}</small>
                                    </div>
                                @endforeach
                                @if(empty($metrics['by_locale']))
                                    <small class="text-muted">Sin datos</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Por tipo</h6>
                                @foreach($metrics['by_action'] as $action => $count)
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">{{ $action }}</small>
                                        <small class="fw-bold">{{ $count }}</small>
                                    </div>
                                @endforeach
                                @if(empty($metrics['by_action']))
                                    <small class="text-muted">Sin datos</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Import CSV --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-1 fw-bold">Importar traducciones</h5>
                <p class="small mb-0 text-muted">Sube un archivo CSV con traducciones para actualizar masivamente</p>
            </div>
            <div class="card-body">
                <form id="import-form" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Archivo CSV</label>
                            <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                            <small class="form-text text-muted">Formato: post_id, source_title, locale, title, slug, description, content, seo_title, seo_description, seo_keywords</small>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100" id="btn-import">
                                <i class="fas fa-upload me-1"></i> Importar
                                <span id="import-spinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                            </button>
                        </div>
                    </div>
                </form>
                <div id="import-results" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    $('#import-form').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $btn = $('#btn-import');
        $btn.prop('disabled', true);
        $('#import-spinner').removeClass('d-none');

        $.ajax({
            url: '{{ route("blog.translations.import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                var html = '<div class="alert alert-success">';
                html += '<strong>' + res.imported + '</strong> traducciones importadas, ';
                html += '<strong>' + res.skipped + '</strong> omitidas.';
                if (res.errors && res.errors.length) {
                    html += '<br><small class="text-danger">' + res.errors.join('<br>') + '</small>';
                }
                html += '</div>';
                $('#import-results').html(html).show();
                toastr.success(res.imported + ' traducciones importadas.');
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Error al importar.';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
                $('#import-spinner').addClass('d-none');
            }
        });
    });
});
</script>
@endpush
