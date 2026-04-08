@extends('layouts.theme')

@section('title', 'Respuestas pendientes de aprobacion')

@section('content')
    @include('core::components.card', ['title' => 'Respuestas pendientes de aprobacion'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Respuestas pendientes de aprobacion</h5>
                        <p class="small mb-0 text-muted">Revisa y aprueba o rechaza las respuestas enviadas por el equipo</p>
                    </div>
                    <div>
                        <span class="badge bg-info fs-6">{{ $replies->total() }} pendiente(s)</span>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                @if($replies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Resena</th>
                                    <th>Respuesta</th>
                                    <th>Solicitado por</th>
                                    <th>Fecha de solicitud</th>
                                    <th>Nota</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($replies as $reply)
                                    <tr>
                                        <td style="min-width: 200px;">
                                            <div class="fw-semibold">{{ $reply->review->reviewer_name }}</div>
                                            <div class="mt-1">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star{{ $i <= ($reply->review->star_rating->toNumeric() ?? 0) ? '' : '-o' }} text-warning small"></i>
                                                @endfor
                                            </div>
                                            @if($reply->review->location)
                                                <small class="text-muted d-block">{{ $reply->review->location->name }}</small>
                                            @endif
                                        </td>
                                        <td style="min-width: 300px; max-width: 400px;">
                                            <p class="mb-0 small">{{ Str::limit($reply->reply_text, 200) }}</p>
                                            @if(strlen($reply->reply_text) > 200)
                                                <button type="button"
                                                        class="btn btn-link btn-sm p-0 mt-1 text-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#reply-modal-{{ $reply->id }}">
                                                    Ver completo
                                                </button>
                                            @endif
                                            @if($reply->rejection_reason)
                                                <div class="alert alert-warning alert-sm py-1 px-2 mt-2 mb-0 small">
                                                    <strong>Rechazo anterior:</strong> {{ $reply->rejection_reason }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold small">{{ $reply->approvalRequestedBy?->name ?? '—' }}</div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $reply->approval_requested_at?->diffForHumans() }}
                                            </small>
                                        </td>
                                        <td style="max-width: 180px;">
                                            @if($reply->approval_note)
                                                <small class="text-muted fst-italic">{{ Str::limit($reply->approval_note, 80) }}</small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td class="text-center" style="min-width: 160px;">
                                            <div class="d-flex gap-2 justify-content-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-success approve-btn"
                                                        data-reply-id="{{ $reply->id }}"
                                                        data-url="{{ route('reviews.replies.approve', $reply) }}">
                                                    <i class="fas fa-check me-1"></i> Aprobar
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger reject-btn"
                                                        data-reply-id="{{ $reply->id }}"
                                                        data-url="{{ route('reviews.replies.reject', $reply) }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#reject-modal">
                                                    <i class="fas fa-times me-1"></i> Rechazar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($replies->hasPages())
                        <div class="card-footer">{{ $replies->links() }}</div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-4x text-success opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay respuestas pendientes de aprobacion</h5>
                        <p class="text-muted">Todas las respuestas han sido revisadas.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Reply full text modal --}}
    @foreach($replies as $reply)
        @if(strlen($reply->reply_text) > 200)
            <div id="reply-modal-{{ $reply->id }}" class="modal fade">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Respuesta completa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>{{ $reply->reply_text }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Reject modal --}}
    <div id="reject-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rechazar respuesta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        El autor sera notificado con el motivo del rechazo y podra corregir y reenviar la respuesta.
                    </p>
                    <div class="mb-3">
                        <label for="rejection-reason" class="form-label fw-semibold">Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea id="rejection-reason"
                                  class="form-control"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Explica por que se rechaza esta respuesta..."></textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small class="text-muted"><span id="reason-count">0</span>/500</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirm-reject-btn">
                        <i class="fas fa-times me-1"></i> Confirmar rechazo
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    let currentRejectUrl = null;

    // Approve
    $(document).on('click', '.approve-btn', function () {
        const $btn = $(this);
        const url = $btn.data('url');

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Aprobando...');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message);
                    $btn.closest('tr').fadeOut(400, function () { $(this).remove(); });
                    updatePendingCount(-1);
                } else {
                    toastr.error(res.message);
                    $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Aprobar');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'Ha ocurrido un error al aprobar la respuesta.';
                toastr.error(msg);
                $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Aprobar');
            }
        });
    });

    // Open reject modal
    $(document).on('click', '.reject-btn', function () {
        currentRejectUrl = $(this).data('url');
        $('#rejection-reason').val('');
        $('#reason-count').text('0');
    });

    // Character counter
    $('#rejection-reason').on('input', function () {
        $('#reason-count').text($(this).val().length);
    });

    // Confirm reject
    $('#confirm-reject-btn').on('click', function () {
        const reason = $('#rejection-reason').val().trim();

        if (!reason) {
            toastr.warning('Por favor ingresa el motivo del rechazo.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Rechazando...');

        $.ajax({
            url: currentRejectUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { reason: reason },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#reject-modal').modal('hide');

                    // Remove the row for this reply
                    const replyId = currentRejectUrl.match(/replies\/(\d+)\/reject/)?.[1];
                    if (replyId) {
                        $('[data-reply-id="' + replyId + '"]').closest('tr').fadeOut(400, function () { $(this).remove(); });
                    }
                    updatePendingCount(-1);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'Ha ocurrido un error al rechazar la respuesta.';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-times me-1"></i> Confirmar rechazo');
            }
        });
    });

    function updatePendingCount(delta) {
        const $badge = $('.badge.bg-info.fs-6');
        const current = parseInt($badge.text()) || 0;
        const next = Math.max(0, current + delta);
        $badge.text(next + ' pendiente(s)');

        if (next === 0) {
            $('tbody').closest('.table-responsive').fadeOut(300, function () {
                $(this).replaceWith(
                    '<div class="text-center py-5">' +
                    '<div class="mb-4"><i class="fas fa-check-circle fa-4x text-success opacity-50"></i></div>' +
                    '<h5 class="text-muted mb-2">No hay respuestas pendientes de aprobacion</h5>' +
                    '<p class="text-muted">Todas las respuestas han sido revisadas.</p>' +
                    '</div>'
                );
            });
        }
    }
});
</script>
@endpush
