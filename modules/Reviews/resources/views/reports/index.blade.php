@extends('layouts.theme')

@section('title', 'Generador de reportes')

@section('content')

    @include('core::components.card', ['title' => 'Generador de reportes'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 text-white">
                        <i class="fas fa-chart-bar"></i> Generar nuevo reporte
                    </h5>
                </div>
                <div class="card-body">
                    @include('reviews::reports._generator-form')
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Tipos de reporte
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Resumen por ubicación</h6>
                        <small class="text-muted">Estadísticas agrupadas por cada ubicación de Google.</small>
                    </div>
                    <div class="mb-3">
                        <h6>Análisis de periodo</h6>
                        <small class="text-muted">Tendencias y evolución en el tiempo seleccionado.</small>
                    </div>
                    <div class="mb-3">
                        <h6>Comparación</h6>
                        <small class="text-muted">Compara dos periodos diferentes lado a lado.</small>
                    </div>
                    <div class="mb-3">
                        <h6>Rendimiento de respuestas</h6>
                        <small class="text-muted">Tasas de respuesta y tiempos promedio.</small>
                    </div>
                    <div class="mb-3">
                        <h6>Exportación detallada</h6>
                        <small class="text-muted">Datos completos de todas las reseñas.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i> Reportes recientes
                    </h5>
                </div>
                <div class="card-body">
                    @include('reviews::reports._reports-list')
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var reportsDestroyBase = '{{ url("reviews/reports") }}';
    const $reportType = $('#report_type');
    const $format = $('#format');
    const $dateFields = $('.date-fields');
    const $comparisonFields = $('.comparison-fields');
    const $periodAnalysisFields = $('.period-analysis-fields');

    function updateFormFields() {
        const reportType = $reportType.val();

        $dateFields.hide();
        $comparisonFields.hide();
        $periodAnalysisFields.hide();

        if (reportType === 'comparison') {
            $comparisonFields.show();
        } else if (reportType === 'period_analysis') {
            $dateFields.show();
            $periodAnalysisFields.show();
        } else {
            $dateFields.show();
        }

        // PDF only for certain report types
        const pdfOption = $format.find('option[value="pdf"]');
        if (['location_summary', 'comparison', 'response_performance'].includes(reportType)) {
            pdfOption.prop('disabled', false);
        } else {
            if ($format.val() === 'pdf') {
                $format.val('excel');
            }
            pdfOption.prop('disabled', true);
        }
    }

    $reportType.on('change', updateFormFields);
    updateFormFields();

    // Load reports list
    loadReportsList();

    function loadReportsList() {
        $.ajax({
            url: '{{ route('reviews.reports.list') }}',
            type: 'GET',
            success: function(reports) {
                updateReportsList(reports);
            },
            error: function() {
                toastr.error('Error al cargar la lista de reportes');
            }
        });
    }

    function updateReportsList(reports) {
        const $tbody = $('#reports-table tbody');
        $tbody.empty();

        if (reports.length === 0) {
            $tbody.append(`
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay reportes generados</td>
                </tr>
            `);
            return;
        }

        reports.forEach(function(report) {
            const statusBadge = getStatusBadge(report.status, report.is_expired);
            const downloadBtn = report.download_url && !report.is_expired
                ? `<a href="${report.download_url}" class="btn btn-sm btn-primary"><i class="fas fa-download"></i></a>`
                : '<button class="btn btn-sm btn-secondary" disabled><i class="fas fa-download"></i></button>';

            const deleteBtn = `<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-modal" data-url="${reportsDestroyBase}/${report.id}" data-title="Eliminar reporte"><i class="fas fa-trash"></i></button>`;

            $tbody.append(`
                <tr>
                    <td>${report.filename}</td>
                    <td>${getReportTypeName(report.report_type)}</td>
                    <td><span class="badge bg-secondary">${report.format.toUpperCase()}</span></td>
                    <td>${statusBadge}</td>
                    <td>${report.file_size || '-'}</td>
                    <td>${report.created_at}</td>
                    <td>
                        ${downloadBtn}
                        ${deleteBtn}
                    </td>
                </tr>
            `);
        });

    }

    function getStatusBadge(status, isExpired) {
        if (isExpired) {
            return '<span class="badge bg-warning">Expirado</span>';
        }

        switch (status) {
            case 'completed':
                return '<span class="badge bg-success">Completado</span>';
            case 'processing':
                return '<span class="badge bg-info">Procesando</span>';
            case 'failed':
                return '<span class="badge bg-danger">Fallido</span>';
            default:
                return '<span class="badge bg-secondary">' + status + '</span>';
        }
    }

    function getReportTypeName(type) {
        const names = {
            'location_summary': 'Resumen por ubicación',
            'period_analysis': 'Análisis de periodo',
            'comparison': 'Comparación',
            'response_performance': 'Rendimiento de respuestas',
            'detailed_export': 'Exportación detallada'
        };
        return names[type] || type;
    }

    // Delete modal handler
    $('#delete-modal').on('show.bs.modal', function (e) {
        var $trigger = $(e.relatedTarget);
        $(this).find('.modal-title').text($trigger.data('title'));
        $('#delete-form').attr('action', $trigger.data('url'));
    });

    // Refresh reports list every 30 seconds
    setInterval(loadReportsList, 30000);
});
</script>
@endpush

@include('core::components.delete')
