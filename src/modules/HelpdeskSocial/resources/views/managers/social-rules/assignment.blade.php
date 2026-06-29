@extends('layouts.theme')

@section('title', 'Reglas de asignación')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Reglas de asignación</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignmentModal" onclick="resetAssignmentForm()">
            <i class="fas fa-plus me-2"></i>Nueva regla
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Prioridad</th>
                            <th>Nombre</th>
                            <th>Condiciones</th>
                            <th>Asignado a</th>
                            <th>Estrategia</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                        <tr data-rule-id="{{ $rule->id }}">
                            <td>{{ $rule->priority }}</td>
                            <td>
                                <div class="fw-semibold">{{ $rule->name }}</div>
                            </td>
                            <td>
                                @foreach($rule->conditions ?? [] as $condition)
                                <span class="badge bg-info text-dark">
                                    {{ $condition['field'] }} {{ $condition['operator'] }} {{ $condition['value'] }}
                                </span>
                                @endforeach
                            </td>
                            <td>{{ $rule->assignee?->name ?? 'No asignado' }}</td>
                            <td>
                                <span class="badge bg-light text-dark">{{ ucfirst($rule->assignment_strategy ?? 'direct') }}</span>
                            </td>
                            <td>
                                @if($rule->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editAssignmentRule({{ $rule->id }}, {{ json_encode($rule->name) }}, {{ json_encode($rule->conditions) }}, {{ $rule->assignee_user_id ?? 'null' }}, {{ json_encode($rule->assignment_strategy) }}, {{ $rule->priority }}, {{ $rule->is_active ? 1 : 0 }})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
                                No hay reglas de asignación configuradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $rules->links() }}
        </div>
    </div>

<div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignmentForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignmentModalLabel">Nueva regla de asignación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prioridad</label>
                        <input type="number" name="priority" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Asignado a</label>
                        <select name="assignee_user_id" class="form-select">
                            <option value="">Seleccionar agente</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estrategia</label>
                        <select name="assignment_strategy" class="form-select">
                            <option value="direct">Directa</option>
                            <option value="round_robin">Round Robin</option>
                            <option value="workload">Por carga</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" checked id="assignmentIsActive">
                        <label class="form-check-label" for="assignmentIsActive">Activa</label>
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
    function resetAssignmentForm() {
        $('#assignmentForm').attr('action', '{{ route('helpdesksocial.assignment-rules.store') }}');
        $('#assignmentForm').find('input[name="_method"]').remove();
        $('#assignmentForm')[0].reset();
        $('#assignmentModalLabel').text('Nueva regla de asignación');
    }

    function editAssignmentRule(id, name, conditions, assigneeUserId, strategy, priority, isActive) {
        $('#assignmentForm').attr('action', '{{ url('panel/helpdesk/social/assignment-rules') }}/' + id);
        if ($('#assignmentForm').find('input[name="_method"]').length === 0) {
            $('#assignmentForm').prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $('#assignmentForm input[name="name"]').val(name);
        $('#assignmentForm input[name="priority"]').val(priority);
        $('#assignmentForm select[name="assignee_user_id"]').val(assigneeUserId);
        $('#assignmentForm select[name="assignment_strategy"]').val(strategy);
        $('#assignmentForm input[name="is_active"]').prop('checked', isActive === 1);
        $('#assignmentModalLabel').text('Editar regla de asignación');
        $('#assignmentModal').modal('show');
    }

    window.resetAssignmentForm = resetAssignmentForm;
    window.editAssignmentRule = editAssignmentRule;
})();
</script>
@endsection
