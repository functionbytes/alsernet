@extends('layouts.theme')

@section('title', 'Historial Global de Emails')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">

            {{-- Header --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="fa-duotone fa-envelope-open-text text-primary me-2"></i>
                                Historial Global de Emails
                            </h4>
                            <p class="mb-0 text-muted">Todos los emails enviados del sistema PQRSF</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success" id="exportEmailsBtn">
                                <i class="fa-duotone fa-file-excel me-2"></i>Exportar
                            </button>
                            <button type="button" class="btn btn-info" id="showStatsBtn">
                                <i class="fa-duotone fa-chart-pie me-2"></i>Estadísticas
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Advanced Filters --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-duotone fa-filter me-2"></i>
                        Filtros Avanzados
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('emails.history') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Radicado PQRSF</label>
                                <input type="text" name="radicado" class="form-control form-control-sm"
                                       value="{{ request('radicado') }}"
                                       placeholder="Buscar por radicado...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Tipo de Email</label>
                                <select class="select2" name="type" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="confirmation" {{ request('type') === 'confirmation' ? 'selected' : '' }}>Confirmación</option>
                                    <option value="assigned" {{ request('type') === 'assigned' ? 'selected' : '' }}>Asignación</option>
                                    <option value="resolution" {{ request('type') === 'resolution' ? 'selected' : '' }}>Resolución</option>
                                    <option value="custom" {{ request('type') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Estado</label>
                                <select class="select2" name="status" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Enviado</option>
                                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Desde</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                       value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Hasta</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                       value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-duotone fa-search me-1"></i>Buscar
                                </button>
                                <a href="{{ route('emails.history') }}" class="btn btn-secondary btn-sm">
                                    <i class="fa-duotone fa-redo me-1"></i>Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Email List Table --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-duotone fa-list me-2"></i>
                        Emails ({{ $mails->total() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">Estado</th>
                                    <th>Radicado</th>
                                    <th>Asunto</th>
                                    <th>Tipo</th>
                                    <th>Destinatario</th>
                                    <th>Fecha Envío</th>
                                    <th class="text-center" style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mails as $mail)
                                    <tr>
                                        <td class="text-center">
                                            @if($mail->status === 'sent')
                                                <i class="fa-duotone fa-check-circle fa-lg text-success" title="Enviado"></i>
                                            @elseif($mail->status === 'failed')
                                                <i class="fa-duotone fa-times-circle fa-lg text-danger" title="Fallido"></i>
                                            @else
                                                <i class="fa-duotone fa-clock fa-lg text-warning" title="Pendiente"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('attention.show', $mail->attention->uid) }}"
                                               class="text-decoration-none fw-semibold">
                                                {{ $mail->attention->radicado }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 300px;" title="{{ $mail->subject }}">
                                                {{ $mail->subject }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($mail->email_type) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $mail->recipient_email }}</small>
                                        </td>
                                        <td>
                                            <small>
                                                {{ $mail->sent_at ? $mail->sent_at->format('d/m/Y H:i') : ($mail->created_at ? $mail->created_at->format('d/m/Y H:i') : 'N/A') }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('attention.emails.preview', [$mail->attention->uid, $mail->uid]) }}"
                                                   class="btn btn-outline-primary"
                                                   title="Ver Preview"
                                                   target="_blank">
                                                    <i class="fa-duotone fa-eye"></i>
                                                </a>
                                                @if($mail->status === 'sent' || $mail->status === 'failed')
                                                    <button type="button"
                                                            class="btn btn-outline-success resend-email-btn"
                                                            data-attention-uid="{{ $mail->attention->uid }}"
                                                            data-mail-uid="{{ $mail->uid }}"
                                                            title="Reenviar">
                                                        <i class="fa-duotone fa-redo"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fa-duotone fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">No se encontraron emails con los filtros aplicados</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($mails->hasPages())
                    <div class="card-footer bg-white">
                        {{ $mails->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Handle resend email
    $('.resend-email-btn').on('click', function() {
        const attentionUid = $(this).data('attention-uid');
        const mailUid = $(this).data('mail-uid');
        const $btn = $(this);

        if (!confirm('¿Está seguro de reenviar este email?')) {
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: `/attentions/${attentionUid}/emails/${mailUid}/resend`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Email reenviado correctamente', 'Éxito');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'No se pudo reenviar el email', 'Error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al reenviar el email';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg, 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa-duotone fa-redo"></i>');
            }
        });
    });

    // Handle export
    $('#exportEmailsBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Exportando...');

        $.ajax({
            url: '/api/emails/export',
            method: 'POST',
            data: {
                radicado: $('input[name="radicado"]').val(),
                type: $('select[name="type"]').val(),
                status: $('select[name="status"]').val(),
                date_from: $('input[name="date_from"]').val(),
                date_to: $('input[name="date_to"]').val(),
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Exportación completada', 'Éxito');
                } else {
                    toastr.info(response.message || 'Funcionalidad pendiente de implementar', 'Info');
                }
            },
            error: function(xhr) {
                toastr.error('Error al exportar', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa-duotone fa-file-excel me-2"></i>Exportar');
            }
        });
    });

    // Handle show stats
    $('#showStatsBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Cargando...');

        $.ajax({
            url: '/api/emails/stats',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    // Display stats in a modal or alert
                    let statsHtml = '<div class="stats-info">';
                    statsHtml += '<h5>Estadísticas Generales</h5>';
                    statsHtml += `<p><strong>Total:</strong> ${response.data.total}</p>`;
                    statsHtml += `<p><strong>Enviados:</strong> ${response.data.sent}</p>`;
                    statsHtml += `<p><strong>Fallidos:</strong> ${response.data.failed}</p>`;
                    statsHtml += `<p><strong>Pendientes:</strong> ${response.data.pending}</p>`;
                    statsHtml += `<p><strong>Hoy:</strong> ${response.data.today}</p>`;
                    statsHtml += `<p><strong>Esta semana:</strong> ${response.data.this_week}</p>`;
                    statsHtml += `<p><strong>Este mes:</strong> ${response.data.this_month}</p>`;
                    statsHtml += '</div>';

                    // Use SweetAlert or Bootstrap modal
                    alert(statsHtml); // Replace with better modal
                } else {
                    toastr.error('No se pudieron cargar las estadísticas', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error al cargar estadísticas', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa-duotone fa-chart-pie me-2"></i>Estadísticas');
            }
        });
    });
});
</script>
@endpush
