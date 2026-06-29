@extends('layouts.theme')

@section('title', 'Dashboard de caché de páginas')

@section('page_header')
    @include('core::components.card', ['title' => 'Dashboard de caché de páginas'])
@endsection

@section('content')
    @include('core::components.alerts')

    {{-- Stat Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 zoom-in bg-primary-subtle shadow-none h-100">
                <div class="card-body text-center p-4">
                    <div class="round-60 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle mx-auto mb-3">
                        <i class="fas fa-chart-pie fs-4 text-primary"></i>
                    </div>
                    <p class="fw-semibold fs-6 text-primary mb-1">Hit Ratio</p>
                    <h4 class="fw-bold text-primary mb-1" id="stat-hit-ratio">—</h4>
                    <small class="text-muted" id="stat-requests">— solicitudes</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 zoom-in bg-success-subtle shadow-none h-100">
                <div class="card-body text-center p-4">
                    <div class="round-60 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle mx-auto mb-3">
                        <i class="fas fa-bolt fs-4 text-success"></i>
                    </div>
                    <p class="fw-semibold fs-6 text-success mb-1">Cache Hits</p>
                    <h4 class="fw-bold text-success mb-1" id="stat-hits">—</h4>
                    <small class="text-muted">del total de solicitudes</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 zoom-in bg-info-subtle shadow-none h-100">
                <div class="card-body text-center p-4">
                    <div class="round-60 d-flex align-items-center justify-content-center rounded-circle bg-info-subtle mx-auto mb-3">
                        <i class="fas fa-file-alt fs-4 text-info"></i>
                    </div>
                    <p class="fw-semibold fs-6 text-info mb-1">Páginas cacheadas</p>
                    <h4 class="fw-bold text-info mb-1" id="stat-pages">—</h4>
                    <small class="text-muted">páginas en total</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 zoom-in bg-warning-subtle shadow-none h-100">
                <div class="card-body text-center p-4">
                    <div class="round-60 d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle mx-auto mb-3">
                        <i class="fas fa-database fs-4 text-warning"></i>
                    </div>
                    <p class="fw-semibold fs-6 text-warning mb-1">Tamaño</p>
                    <h4 class="fw-bold text-warning mb-1" id="stat-size">—</h4>
                    <small class="text-muted">tamaño total</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Controls + Activity --}}
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-bold mb-1">Controles de caché</h6>
                    <p class="small text-muted mb-0">Acciones sobre el caché de páginas</p>
                </div>
                <div class="card-body p-4">
                    <div class="d-grid gap-3">
                        <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-3" id="warm-btn">
                            <i class="fas fa-fire"></i>
                            <span>Calentar caché</span>
                        </button>
                        <button class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2 py-3" id="clear-btn">
                            <i class="fas fa-trash"></i>
                            <span>Limpiar caché</span>
                        </button>
                    </div>
                    <div class="mt-4 p-3 bg-info-subtle rounded">
                        <small class="text-muted">
                            <i class="fas fa-info-circle text-info me-1"></i>
                            <strong>Calentar caché</strong> precarga las páginas activas. <strong>Limpiar caché</strong> elimina todo el contenido almacenado.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-bold mb-1">Actividad reciente</h6>
                    <p class="small text-muted mb-0">Últimas operaciones de caché</p>
                </div>
                <div class="card-body p-0">
                    <div id="audits-list" class="page-audit-list">
                        <div class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-muted" role="status"></div>
                            <small class="text-muted d-block mt-2">Cargando actividad...</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Confirm clear cache modal --}}
    <div class="modal fade" id="clear-cache-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Limpiar caché</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-0">¿Limpiar todo el caché de páginas? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button id="clear-confirm-btn" type="button" class="btn btn-primary w-100 mb-1">Limpiar caché</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('modules/page/css/page-admin.css') }}">
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function formatBytes(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = Math.max(bytes, 0);
        const pow = Math.min(
            Math.floor(size ? Math.log(size) / Math.log(1024) : 0),
            units.length - 1
        );
        size /= Math.pow(1024, pow);
        return Math.round(size * 100) / 100 + ' ' + units[pow];
    }

    function loadStats() {
        $.get('{{ route('pages.cache.stats') }}', function(stats) {
            $('#stat-hit-ratio').text(stats.hit_ratio + '%');
            $('#stat-requests').text(stats.total_requests + ' solicitudes');
            $('#stat-hits').text(stats.total_hits);
            $('#stat-pages').text(stats.cached_pages_count);
            $('#stat-size').text(formatBytes(stats.size_bytes));
        }).fail(function() {
            $('#stat-hit-ratio, #stat-hits, #stat-pages, #stat-size').text('—');
        });
    }

    function actionBadge(action) {
        const map = {
            cached:   { cls: 'bg-success-subtle text-success', icon: 'fa-check-circle' },
            cleared:  { cls: 'bg-danger-subtle text-danger',   icon: 'fa-trash' },
            warmed:   { cls: 'bg-primary-subtle text-primary', icon: 'fa-fire' },
            expired:  { cls: 'bg-warning-subtle text-warning', icon: 'fa-clock' },
        };
        const m = map[action] || { cls: 'bg-secondary-subtle text-secondary', icon: 'fa-circle' };
        return `<span class="badge ${m.cls} me-2"><i class="fas ${m.icon} me-1"></i>${action}</span>`;
    }

    function loadAudits() {
        $.get('{{ route('pages.cache.audits') }}', function(audits) {
            if (!audits.length) {
                $('#audits-list').html('<div class="text-center py-4"><small class="text-muted">Sin actividad reciente</small></div>');
                return;
            }
            const html = audits.map(a => `
                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                    <div class="flex-shrink-0">
                        ${actionBadge(a.action)}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="fw-semibold text-truncate d-block small">${a.slug || '—'}</span>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <small class="text-muted">${a.created_at}</small>
                    </div>
                </div>
            `).join('');
            $('#audits-list').html(html);
        }).fail(function() {
            $('#audits-list').html('<div class="text-center py-4"><small class="text-muted">Error al cargar actividad</small></div>');
        });
    }

    $('#warm-btn').on('click', function() {
        const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Calentando...');
        $.post('{{ route('pages.cache.warm') }}', function() {
            toastr.success('Caché calentado correctamente');
            loadStats();
        }).fail(function() {
            toastr.error('Error al calentar el caché');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-fire"></i> <span>Calentar caché</span>');
        });
    });

    $('#clear-btn').on('click', function() {
        $('#clear-cache-modal').modal('show');
    });

    $('#clear-confirm-btn').on('click', function() {
        $('#clear-cache-modal').modal('hide');
        const $btn = $('#clear-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Limpiando...');
        $.post('{{ route('pages.cache.clear') }}', function() {
            toastr.success('Caché limpiado correctamente');
            loadStats();
            loadAudits();
        }).fail(function() {
            toastr.error('Error al limpiar el caché');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-trash"></i> <span>Limpiar caché</span>');
        });
    });

    loadStats();
    loadAudits();
    setInterval(loadStats, 10000);
    setInterval(loadAudits, 10000);
});
</script>
@endpush
