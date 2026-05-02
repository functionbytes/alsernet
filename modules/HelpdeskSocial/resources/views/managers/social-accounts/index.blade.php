@extends('theme::layouts.admin')

@section('title', 'Cuentas sociales conectadas')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Cuentas sociales conectadas</h1>
        <a href="{{ route('helpdesksocial.accounts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Conectar cuenta
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Plataforma</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Comentarios</th>
                            <th>Mensajes</th>
                            <th>Auto-respuesta</th>
                            <th>Última sincronización</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $account->platform === 'facebook' ? 'primary' : ($account->platform === 'instagram' ? 'danger' : 'success') }}">
                                    <i class="fab fa-{{ $account->platform }} me-1"></i>
                                    {{ ucfirst($account->platform) }}
                                </span>
                            </td>
                            <td>{{ $account->name }}</td>
                            <td>{{ $account->username ?? '-' }}</td>
                            <td>
                                @if($account->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                                @if($account->needsTokenRefresh())
                                    <span class="badge bg-warning text-dark" title="Token expira pronto">!</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $account->comments_enabled ? 'success' : 'secondary' }}">
                                    {{ $account->comments_enabled ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $account->messages_enabled ? 'success' : 'secondary' }}">
                                    {{ $account->messages_enabled ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $account->auto_reply_enabled ? 'info' : 'secondary' }}">
                                    {{ $account->auto_reply_enabled ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td>
                                {{ $account->last_synced_at ? $account->last_synced_at->diffForHumans() : 'Nunca' }}
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleCrisisMode({{ $account->id }})" title="Modo crisis">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </button>
                                <a href="{{ route('helpdesksocial.accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No hay cuentas sociales conectadas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $accounts->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    function toggleCrisisMode(accountId) {
        if (!confirm('¿Activar/desactivar modo crisis para esta cuenta? Las respuestas automáticas se pausarán.')) {
            return;
        }

        $.ajax({
            url: '{{ url('panel/helpdesk/social/accounts') }}/' + accountId + '/crisis-mode',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (window.toastr) {
                    toastr.success(response.message || 'Modo crisis actualizado.');
                }
                window.location.reload();
            },
            error: function () {
                if (window.toastr) {
                    toastr.error('No se pudo actualizar el modo crisis.');
                }
            }
        });
    }

    window.toggleCrisisMode = toggleCrisisMode;
})();
</script>
@endsection
