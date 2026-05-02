@extends('theme::layouts.admin')

@section('title', 'Competidores')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Competidores</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#competitorModal" onclick="resetCompetitorForm()">
            <i class="fas fa-plus me-2"></i>Nuevo competidor
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
                            <th>Cuenta relacionada</th>
                            <th>Usuario</th>
                            <th>Métricas recientes</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($competitors as $competitor)
                        <tr data-competitor-id="{{ $competitor->id }}">
                            <td>
                                <div class="fw-semibold">{{ $competitor->name }}</div>
                                @if($competitor->profile_url)
                                <small>
                                    <a href="{{ $competitor->profile_url }}" target="_blank" class="text-muted">
                                        <i class="fas fa-external-link-alt me-1"></i>Perfil
                                    </a>
                                </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $competitor->platform === 'facebook' ? 'primary' : 'danger' }}">
                                    <i class="fab fa-{{ $competitor->platform }} me-1"></i>
                                    {{ ucfirst($competitor->platform) }}
                                </span>
                            </td>
                            <td>{{ $competitor->account?->name ?? '-' }}</td>
                            <td>{{ $competitor->username ?? '-' }}</td>
                            <td>
                                @if($competitor->metrics->count() > 0)
                                <div class="d-flex gap-2">
                                    @foreach($competitor->metrics->sortByDesc('captured_at')->take(3) as $metric)
                                    <span class="badge bg-light text-dark" title="{{ $metric->metric_type }}: {{ $metric->value }} ({{ $metric->captured_at?->format('d/m') }})">
                                        {{ strtoupper(substr($metric->metric_type, 0, 3)) }} {{ number_format($metric->value, 0) }}
                                    </span>
                                    @endforeach
                                </div>
                                <small class="text-muted sparkline" data-competitor="{{ $competitor->id }}">
                                    <!-- Sparkline placeholder -->
                                    <span class="text-muted">{{ $competitor->metrics->sortBy('captured_at')->pluck('value')->implode(', ') }}</span>
                                </small>
                                @else
                                <span class="text-muted">Sin métricas</span>
                                @endif
                            </td>
                            <td>
                                @if($competitor->is_active)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editCompetitor({{ $competitor->id }}, {{ json_encode($competitor->name) }}, {{ json_encode($competitor->platform) }}, {{ json_encode($competitor->username) }}, {{ json_encode($competitor->profile_url) }}, {{ $competitor->is_active ? 1 : 0 }})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                No hay competidores registrados
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $competitors->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="competitorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="competitorForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="competitorModalLabel">Nuevo competidor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plataforma</label>
                        <select name="platform" class="form-select" required>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="twitter">Twitter</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="username" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL del perfil</label>
                        <input type="url" name="profile_url" class="form-control">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" checked id="competitorIsActive">
                        <label class="form-check-label" for="competitorIsActive">Activo</label>
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
    function resetCompetitorForm() {
        $('#competitorForm').attr('action', '{{ route('helpdesksocial.competitors.store') }}');
        $('#competitorForm').find('input[name="_method"]').remove();
        $('#competitorForm')[0].reset();
        $('#competitorModalLabel').text('Nuevo competidor');
    }

    function editCompetitor(id, name, platform, username, profileUrl, isActive) {
        $('#competitorForm').attr('action', '{{ url('panel/helpdesk/social/competitors') }}/' + id);
        if ($('#competitorForm').find('input[name="_method"]').length === 0) {
            $('#competitorForm').prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $('#competitorForm input[name="name"]').val(name);
        $('#competitorForm select[name="platform"]').val(platform);
        $('#competitorForm input[name="username"]').val(username);
        $('#competitorForm input[name="profile_url"]').val(profileUrl);
        $('#competitorForm input[name="is_active"]').prop('checked', isActive === 1);
        $('#competitorModalLabel').text('Editar competidor');
        $('#competitorModal').modal('show');
    }

    window.resetCompetitorForm = resetCompetitorForm;
    window.editCompetitor = editCompetitor;
})();
</script>
@endsection
