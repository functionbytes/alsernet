@extends('layouts.theme')

@section('title', 'Solicitudes de aprobación')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Solicitudes de aprobación</h1>
        <div class="d-flex gap-2">
            <span class="badge bg-warning text-dark">{{ $requests->where('status', 'pending')->count() }} pendientes</span>
            <span class="badge bg-success">{{ $requests->where('status', 'approved')->count() }} aprobadas</span>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Comentario</th>
                            <th>Acción</th>
                            <th>Solicitado por</th>
                            <th>Estado</th>
                            <th>Aprobador</th>
                            <th>Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $approval)
                        <tr data-approval-id="{{ $approval->id }}">
                            <td>
                                <div class="fw-semibold">{{ $approval->comment?->author_name ?? 'N/A' }}</div>
                                <small class="text-muted text-truncate d-block" style="max-width: 250px;">
                                    {{ $approval->comment?->body ?? '-' }}
                                </small>
                                @if($approval->comment?->socialAccount)
                                <small class="badge bg-secondary">{{ $approval->comment->socialAccount->name }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ ucfirst($approval->action_type) }}</span>
                            </td>
                            <td>{{ $approval->requester?->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $approval->status === 'pending' ? 'warning text-dark' : ($approval->status === 'approved' ? 'success' : 'danger') }}">
                                    {{ ucfirst($approval->status) }}
                                </span>
                            </td>
                            <td>{{ $approval->approver?->name ?? '-' }}</td>
                            <td>
                                <small>{{ $approval->created_at->diffForHumans() }}</small>
                            </td>
                            <td class="text-end">
                                @if($approval->status === 'pending')
                                <button type="button" class="btn btn-sm btn-success" onclick="respondApproval({{ $approval->id }}, 'approve')" title="Aprobar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="respondApproval({{ $approval->id }}, 'reject')" title="Rechazar">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif
                                <a href="{{ route('helpdesksocial.inbox.show', $approval->comment) }}" class="btn btn-sm btn-outline-primary" title="Ver comentario">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                                <p>No hay solicitudes de aprobación</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>

<div class="modal fade" id="approvalResponseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approvalResponseForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalResponseModalLabel">Responder solicitud</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="approvalAction">
                    <div class="mb-3">
                        <label class="form-label">Nota del aprobador</label>
                        <textarea name="approver_note" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar respuesta</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    function respondApproval(id, action) {
        $('#approvalResponseForm').attr('action', '{{ url('panel/helpdesk/social/approvals') }}/' + id + '/respond');
        $('#approvalAction').val(action);
        $('#approvalResponseModalLabel').text(action === 'approve' ? 'Aprobar solicitud' : 'Rechazar solicitud');
        $('#approvalResponseModal').modal('show');
    }

    window.respondApproval = respondApproval;
})();
</script>
@endsection
