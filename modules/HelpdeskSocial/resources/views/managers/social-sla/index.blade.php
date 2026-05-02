@extends('theme::layouts.admin')

@section('title', 'Políticas SLA')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Políticas SLA</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#slaModal" onclick="resetSlaForm()">
            <i class="fas fa-plus me-2"></i>Nueva política
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Plataforma</th>
                            <th>Prioridad</th>
                            <th>Tiempo de respuesta</th>
                            <th>Tiempo de resolución</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                        <tr data-policy-id="{{ $policy->id }}">
                            <td>
                                <div class="fw-semibold">{{ $policy->name }}</div>
                            </td>
                            <td>
                                @if($policy->platform)
                                    <span class="badge bg-secondary">{{ ucfirst($policy->platform) }}</span>
                                @else
                                    <span class="badge bg-light text-dark">Todas</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $policy->priority === 'critical' ? 'dark' : ($policy->priority === 'high' ? 'danger' : ($policy->priority === 'medium' ? 'warning text-dark' : 'success')) }}">
                                    {{ ucfirst($policy->priority) }}
                                </span>
                            </td>
                            <td>{{ $policy->response_time_minutes }} min</td>
                            <td>{{ $policy->resolution_time_minutes }} min</td>
                            <td>
                                @if($policy->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editSla({{ $policy->id }}, {{ json_encode($policy->name) }}, {{ json_encode($policy->platform) }}, {{ json_encode($policy->priority) }}, {{ $policy->response_time_minutes }}, {{ $policy->resolution_time_minutes }}, {{ $policy->is_active ? 1 : 0 }})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-clock fa-2x mb-2 d-block"></i>
                                No hay políticas SLA configuradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $policies->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="slaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="slaForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="slaModalLabel">Nueva política SLA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plataforma</label>
                        <select name="platform" class="form-select">
                            <option value="">Todas</option>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="twitter">Twitter</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prioridad</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Baja</option>
                            <option value="medium">Media</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiempo de respuesta (min)</label>
                            <input type="number" name="response_time_minutes" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiempo de resolución (min)</label>
                            <input type="number" name="resolution_time_minutes" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" checked id="slaIsActive">
                        <label class="form-check-label" for="slaIsActive">Activa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    function resetSlaForm() {
        $('#slaForm').attr('action', '{{ route('helpdesksocial.sla.store') }}');
        $('#slaForm').find('input[name="_method"]').remove();
        $('#slaForm')[0].reset();
        $('#slaModalLabel').text('Nueva política SLA');
    }

    function editSla(id, name, platform, priority, responseTime, resolutionTime, isActive) {
        $('#slaForm').attr('action', '{{ url('panel/helpdesk/social/sla') }}/' + id);
        if ($('#slaForm').find('input[name="_method"]').length === 0) {
            $('#slaForm').prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $('#slaForm input[name="name"]').val(name);
        $('#slaForm select[name="platform"]').val(platform);
        $('#slaForm select[name="priority"]').val(priority);
        $('#slaForm input[name="response_time_minutes"]').val(responseTime);
        $('#slaForm input[name="resolution_time_minutes"]').val(resolutionTime);
        $('#slaForm input[name="is_active"]').prop('checked', isActive === 1);
        $('#slaModalLabel').text('Editar política SLA');
        $('#slaModal').modal('show');
    }

    window.resetSlaForm = resetSlaForm;
    window.editSla = editSla;
})();
</script>
@endsection
