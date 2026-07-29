@extends('layouts.theme')

@section('content')
    @include('core::components.card', ['title' => 'Historial de verificaciones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Historial de verificaciones</h5>
                        <p class="small mb-0 text-muted">Registro histórico de las verificaciones de salud del sistema</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select  select2" id="periodFilter" style="width: auto;">
                            <option value="1">Último día</option>
                            <option value="7" selected>Últimos 7 días</option>
                            <option value="30">Últimos 30 días</option>
                            <option value="90">Últimos 90 días</option>
                        </select>
                        <button type="button" class="btn btn-primary btn-sm" onclick="loadHistory()">
                            <i class="fas fa-sync me-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stats Section --}}
            <div class="card-body border-bottom">
                <div class="row">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="card bg-light-secondary">
                            <div class="card-body">
                                <p class="mb-1 text-muted small">Total registros</p>
                                <h4 class="mb-0 fw-bold" id="totalRecords">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="card bg-light-secondary">
                            <div class="card-body">
                                <p class="mb-1 text-success small">Exitosos</p>
                                <h4 class="mb-0 fw-bold text-success" id="successRecords">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="card bg-light-secondary">
                            <div class="card-body">
                                <p class="mb-1 text-warning small">Advertencias</p>
                                <h4 class="mb-0 fw-bold text-warning" id="warningRecords">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary">
                            <div class="card-body">
                                <p class="mb-1  small">Errores</p>
                                <h4 class="mb-0 fw-bold " id="errorRecords">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- History Table --}}
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">Verificación</th>
                                <th width="15%">Estado</th>
                                <th width="35%">Resumen</th>
                                <th width="25%">Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@push('scripts')
<script>
    $(document).ready(function() {
        loadHistory();

        $('#periodFilter').on('change', function() {
            loadHistory();
        });
    });

    function loadHistory() {
        const $btn = $('button[onclick="loadHistory()"]');
        const originalHtml = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span>');
        $btn.prop('disabled', true);

        const days = $('#periodFilter').val();

        $.ajax({
            url: '{{ route('settings.health.history') }}',
            method: 'GET',
            data: { days: days },
            headers: {
                'Accept': 'application/json'
            },
            dataType: 'json',
            success: function(response) {
                updateStats(response.history);
                renderHistoryTable(response.history);
                toastr.success('Historial actualizado', 'Éxito', {
                    positionClass: 'toast-bottom-right'
                });
            },
            error: function(xhr, status, error) {
                toastr.error('Error al cargar el historial', 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                console.error('History load error:', error);
                $('#historyTableBody').html('<tr><td colspan="4" class="text-center  py-4">Error al cargar el historial</td></tr>');
            },
            complete: function() {
                $btn.html(originalHtml);
                $btn.prop('disabled', false);
            }
        });
    }

    function updateStats(history) {
        let total = 0;
        let success = 0;
        let warning = 0;
        let error = 0;

        $.each(history, function(checkName, items) {
            $.each(items, function(index, item) {
                total++;
                if (item.status === 'ok') success++;
                else if (item.status === 'warning') warning++;
                else error++;
            });
        });

        $('#totalRecords').text(total);
        $('#successRecords').text(success);
        $('#warningRecords').text(warning);
        $('#errorRecords').text(error);
    }

    function renderHistoryTable(history) {
        const $tbody = $('#historyTableBody');

        if (Object.keys(history).length === 0) {
            $tbody.html('<tr><td colspan="4" class="text-center text-muted py-4">No hay registros en el período seleccionado</td></tr>');
            return;
        }

        let html = '';
        $.each(history, function(checkName, items) {
            $.each(items, function(index, item) {
                const statusBadge = getStatusBadge(item.status);
                const checkLabel = formatCheckName(checkName);
                const date = new Date(item.created_at);
                const formattedDate = date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');

                html += `<tr>
                    <td>${checkLabel}</td>
                    <td>${statusBadge}</td>
                    <td><small class="text-muted">${item.short_summary || '-'}</small></td>
                    <td><small>${formattedDate}</small></td>
                </tr>`;
            });
        });

        $tbody.html(html);
    }

    function getStatusBadge(status) {
        switch(status) {
            case 'ok':
                return '<span class="badge bg-success-subtle text-success">OK</span>';
            case 'warning':
                return '<span class="badge bg-warning-subtle text-warning">Advertencia</span>';
            case 'failed':
                return '<span class="badge bg-danger-subtle text-white">Error</span>';
            default:
                return '<span class="badge bg-secondary-subtle text-secondary">' + status + '</span>';
        }
    }

    function formatCheckName(name) {
        // Convert "DatabaseCheck" to "Database"
        return name.replace(/Check$/, '').replace(/([A-Z])/g, ' $1').trim();
    }
</script>
@endpush
@endsection

<style>
    .bg-light-secondary {
        background-color: #f8f9fa;
    }

    .bg-success-subtle {
        background-color: rgba(19, 198, 114, 0.1);
    }

    .bg-danger-subtle {
        background-color: rgba(250, 137, 107, 0.1);
    }

    .bg-warning-subtle {
        background-color: rgba(254, 201, 15, 0.1);
    }

    .bg-secondary-subtle {
        background-color: rgba(108, 117, 125, 0.1);
    }
</style>
