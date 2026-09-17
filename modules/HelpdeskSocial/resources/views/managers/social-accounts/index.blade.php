@extends('layouts.theme')

@section('title', 'Cuentas sociales conectadas')

@section('page_header')
    @include('core::components.card', ['title' => 'Cuentas sociales conectadas'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Cuentas sociales conectadas</h1>
        <a href="{{ route('helpdesksocial.accounts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Conectar cuenta
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="accounts-table">
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
                                <button type="button" class="btn btn-sm {{ $account->crisis_mode_active ? 'btn-outline-primary' : 'btn-outline-secondary' }}"
                                        onclick="toggleCrisisMode({{ $account->id }}, {{ $account->crisis_mode_active ? 'true' : 'false' }})"
                                        title="Modo crisis {{ $account->crisis_mode_active ? '(activo)' : '' }}">
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

    {{-- EnterCrisisModeRequest exige un motivo — este modal lo recoge en
         vez de usar window.__confirm() (que no acepta texto libre). Salir
         de modo crisis no requiere motivo, así que no lo usa. --}}
    <div class="modal fade" id="crisisModeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="crisisModeForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Activar modo crisis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Las respuestas automáticas de esta cuenta se pausarán.</p>
                        <label class="form-label">Motivo</label>
                        <textarea id="crisisModeReason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Activar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('modules/helpdesksocial/js/social-accounts-index.js') }}?v={{ filemtime(public_path('modules/helpdesksocial/js/social-accounts-index.js')) }}"></script>
@endpush
