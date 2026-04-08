@extends('layouts.theme')

@section('title', 'Historial de versiones - ' . $template->name)

@section('content')
    @include('core::components.card', ['title' => 'Historial de versiones'])

    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Historial de versiones</h5>
                        <p class="small mb-0 text-muted">{{ $template->name }}</p>
                    </div>
                    <a href="{{ route('settings.reviews.templates.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver a plantillas
                    </a>
                </div>
            </div>

            <div class="card-body p-0" id="versions-container">
                <div class="text-center py-5" id="loading-state">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>

                <div id="versions-content" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 80px;">Version</th>
                                    <th>Contenido</th>
                                    <th style="width: 100px;">Idioma</th>
                                    <th style="width: 160px;">Creado por</th>
                                    <th style="width: 160px;">Fecha</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody id="versions-tbody"></tbody>
                        </table>
                    </div>

                    <div id="pagination-container" class="d-flex justify-content-end p-3"></div>
                </div>

                <div id="empty-state" class="d-none text-center py-5">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay versiones registradas para esta plantilla</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var versionsUrl = '{{ route('settings.reviews.templates.versions', $template) }}';
    var currentPage = 1;

    function loadVersions(page) {
        page = page || 1;

        $.ajax({
            url: versionsUrl,
            method: 'GET',
            data: { page: page },
            headers: { 'Accept': 'application/json' },
            success: function (response) {
                renderVersions(response);
            },
            error: function () {
                toastr.error('Error al cargar el historial de versiones');
                $('#loading-state').addClass('d-none');
                $('#empty-state').removeClass('d-none');
            }
        });
    }

    function renderVersions(response) {
        $('#loading-state').addClass('d-none');

        var data = response.data || [];

        if (data.length === 0) {
            $('#empty-state').removeClass('d-none');
            return;
        }

        var rows = data.map(function (version, index) {
            var collapseId = 'collapse-' + version.id;
            var preview = version.content
                ? version.content.substring(0, 100) + (version.content.length > 100 ? '...' : '')
                : '—';
            var createdBy = version.creator ? version.creator.name : '—';
            var createdAt = version.created_at
                ? new Date(version.created_at).toLocaleDateString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                })
                : '—';

            return '<tr>' +
                '<td class="ps-4"><span class="badge bg-secondary">#' + version.version_number + '</span></td>' +
                '<td>' +
                    '<span class="text-muted">' + $('<span>').text(preview).html() + '</span>' +
                    '<div class="collapse mt-2" id="' + collapseId + '">' +
                        '<div class="p-2 bg-light rounded small">' + $('<span>').text(version.content || '').html() + '</div>' +
                    '</div>' +
                '</td>' +
                '<td><span class="badge bg-light text-dark border">' + (version.language || '—') + '</span></td>' +
                '<td class="small">' + $('<span>').text(createdBy).html() + '</td>' +
                '<td class="small text-muted">' + createdAt + '</td>' +
                '<td>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary btn-ver" ' +
                        'data-bs-toggle="collapse" data-bs-target="#' + collapseId + '" ' +
                        'aria-expanded="false">' +
                        '<i class="fas fa-eye"></i>' +
                    '</button>' +
                '</td>' +
            '</tr>';
        });

        $('#versions-tbody').html(rows.join(''));
        renderPagination(response);
        $('#versions-content').removeClass('d-none');
    }

    function renderPagination(response) {
        if (!response.last_page || response.last_page <= 1) {
            $('#pagination-container').empty();
            return;
        }

        var html = '<nav><ul class="pagination pagination-sm mb-0">';

        for (var p = 1; p <= response.last_page; p++) {
            var active = p === response.current_page ? ' active' : '';
            html += '<li class="page-item' + active + '">' +
                '<a class="page-link page-btn" href="#" data-page="' + p + '">' + p + '</a>' +
            '</li>';
        }

        html += '</ul></nav>';
        $('#pagination-container').html(html);
    }

    $(document).on('click', '.page-btn', function (e) {
        e.preventDefault();
        var page = $(this).data('page');
        $('#loading-state').removeClass('d-none');
        $('#versions-content').addClass('d-none');
        loadVersions(page);
    });

    loadVersions(1);
}());
</script>
@endpush
