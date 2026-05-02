@extends('layouts.admin')

@section('title', 'Aprobación de Publicaciones')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-check-square me-2"></i>Aprobación de Publicaciones
                        </h5>
                        <p class="small mb-0 text-muted">Revisa y aprueba publicaciones del equipo</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-warning stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Pendientes</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['pending'] }}</h4>
                                        <small class="text-muted">Por revisar</small>
                                    </div>
                                    <i class="fas fa-pause-circle fs-1 text-warning opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-success stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Aprobadas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['approved'] }}</h4>
                                        <small class="text-muted">Listas para publicar</small>
                                    </div>
                                    <i class="fas fa-check-circle fs-1 text-success opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-danger stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-danger mb-2">Rechazadas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['rejected'] }}</h4>
                                        <small class="text-muted">Requieren cambios</small>
                                    </div>
                                    <i class="fas fa-times-circle fs-1 text-danger opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Estado</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="pending_review" {{ $status === 'pending_review' ? 'selected' : '' }}>Pendientes de Revisión</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Aprobadas</option>
                            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rechazadas</option>
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todas</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Posts List -->
            <div class="card-body p-0">
                @if($posts->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fs-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No hay publicaciones {{ $status === 'all' ? 'en el sistema' : 'en este estado' }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Contenido</th>
                                    <th>Creador</th>
                                    <th>Redes</th>
                                    <th>Fecha Programada</th>
                                    <th>Estado</th>
                                    <th>Enviado</th>
                                    <th width="200" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($posts as $post)
                                    <tr>
                                        <td>
                                            @if($post->approval_status->value === 'pending_review')
                                                <input type="checkbox" name="post_ids[]" value="{{ $post->id }}" class="form-check-input post-checkbox">
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-start">
                                                @if($post->media->isNotEmpty())
                                                    <img src="{{ $post->media->first()->url }}"
                                                         class="rounded me-2"
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                @endif
                                                <div>
                                                    <p class="mb-1">{{ Str::limit($post->content, 100) }}</p>
                                                    @if($post->campaign)
                                                        <span class="badge bg-light-primary text-primary small">
                                                            <i class="fas fa-folder me-1"></i>{{ $post->campaign->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-title rounded-circle bg-light-info text-info">
                                                        {{ substr($post->creator->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <span class="small">{{ $post->creator->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @foreach($post->socialAccounts as $account)
                                                    @php
                                                        $iconClass = match($account->network->value) {
                                                            'facebook' => 'ti-brand-facebook text-primary',
                                                            'instagram' => 'ti-brand-instagram text-danger',
                                                            'twitter' => 'ti-brand-x text-dark',
                                                            'linkedin' => 'ti-brand-linkedin text-info',
                                                            default => 'ti-share'
                                                        };
                                                    @endphp
                                                    <i class="ti {{ $iconClass }} fs-5" title="{{ $account->username }}"></i>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            @if($post->scheduled_at)
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>{{ $post->scheduled_at->format('d/m/Y H:i') }}
                                                </small>
                                            @else
                                                <small class="text-muted">Sin programar</small>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match($post->approval_status->value) {
                                                    'pending_review' => 'bg-warning',
                                                    'approved' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $post->approval_status->label() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($post->submitted_at)
                                                <small class="text-muted">{{ $post->submitted_at->diffForHumans() }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.social.publishing.edit', $post) }}"
                                                   class="btn btn-light"
                                                   title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                @if($post->approval_status->value === 'pending_review')
                                                    <button type="button"
                                                            class="btn btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#approveModal{{ $post->id }}"
                                                            title="Aprobar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#rejectModal{{ $post->id }}"
                                                            title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif

                                                @if($post->approval_status->value === 'rejected' && $post->created_by === auth()->id())
                                                    <form action="{{ route('admin.social.approval.return-to-draft', $post) }}"
                                                          method="POST"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-warning"
                                                                title="Volver a Borrador">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                @if($post->approval_status->value === 'approved' && $post->reviewer)
                                                    <button type="button"
                                                            class="btn btn-info"
                                                            data-bs-toggle="tooltip"
                                                            title="Aprobado por {{ $post->reviewer->name }} el {{ $post->reviewed_at->format('d/m/Y H:i') }}">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Approve Modal -->
                                    @if($post->approval_status->value === 'pending_review')
                                        <div class="modal fade" id="approveModal{{ $post->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('admin.social.approval.approve', $post) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Aprobar Publicación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Estás seguro de que quieres aprobar esta publicación?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Notas (opcional)</label>
                                                                <textarea name="review_notes" class="form-control" rows="3" placeholder="Comentarios para el creador..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-check me-1"></i>Aprobar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal{{ $post->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('admin.social.approval.reject', $post) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Rechazar Publicación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Indica el motivo del rechazo para que el creador pueda hacer los cambios necesarios.</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Motivo del Rechazo <span class="text-danger">*</span></label>
                                                                <textarea name="review_notes" class="form-control" rows="4" placeholder="Explica qué debe cambiar..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-times me-1"></i>Rechazar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="card-footer border-top d-none" id="bulk-actions">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <span id="selected-count">0</span> publicaciones seleccionadas
                            </span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-success btn-sm" onclick="bulkApprove()">
                                    <i class="fas fa-check me-1"></i>Aprobar Seleccionadas
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#bulkRejectModal">
                                    <i class="fas fa-times me-1"></i>Rechazar Seleccionadas
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer">
                        {{ $posts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Bulk Reject Modal -->
    <div class="modal fade" id="bulkRejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.social.approval.bulk-reject') }}" method="POST" id="bulk-reject-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Rechazar Múltiples Publicaciones</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Todas las publicaciones seleccionadas serán rechazadas con el mismo motivo.</p>
                        <div class="mb-3">
                            <label class="form-label">Motivo del Rechazo <span class="text-danger">*</span></label>
                            <textarea name="review_notes" class="form-control" rows="4" placeholder="Explica qué debe cambiar..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Rechazar Todas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.post-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    // Select/Deselect All
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
    }

    // Individual checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.post-checkbox:checked');
        const count = selected.length;

        if (count > 0) {
            bulkActions.classList.remove('d-none');
            selectedCount.textContent = count;
        } else {
            bulkActions.classList.add('d-none');
        }
    }

    // Bulk Approve
    window.bulkApprove = function() {
        const selected = Array.from(document.querySelectorAll('.post-checkbox:checked')).map(cb => cb.value);

        if (selected.length === 0) {
            alert('Selecciona al menos una publicación');
            return;
        }

        if (confirm(`¿Aprobar ${selected.length} publicaciones?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.social.approval.bulk-approve") }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'post_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    };

    // Bulk Reject - Add selected IDs to form
    const bulkRejectForm = document.getElementById('bulk-reject-form');
    if (bulkRejectForm) {
        bulkRejectForm.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.post-checkbox:checked');

            // Remove any existing post_ids
            bulkRejectForm.querySelectorAll('input[name="post_ids[]"]').forEach(i => i.remove());

            // Add selected IDs
            selected.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'post_ids[]';
                input.value = cb.value;
                bulkRejectForm.appendChild(input);
            });
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
