@extends('layouts.theme')

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Auditoría: impersonaciones</h4>
            <p class="text-muted mb-0">Historial de sesiones de impersonación realizadas por administradores</p>
        </div>
        <div>
            <a href="{{ route('settings.auth.audit.login-attempts') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-right-to-bracket me-1"></i> Ver intentos de login
            </a>
        </div>
    </div>

    <div class="card border">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Admin</th>
                        <th>Usuario impersonado</th>
                        <th>IP</th>
                        <th>Razón</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>
                                @if ($log->impersonator)
                                    <div class="fw-semibold">{{ $log->impersonator->firstname }} {{ $log->impersonator->lastname }}</div>
                                    <small class="text-muted">{{ $log->impersonator->email }}</small>
                                @else
                                    <span class="text-muted">(eliminado)</span>
                                @endif
                            </td>
                            <td>
                                @if ($log->impersonated)
                                    <div class="fw-semibold">{{ $log->impersonated->firstname }} {{ $log->impersonated->lastname }}</div>
                                    <small class="text-muted">{{ $log->impersonated->email }}</small>
                                @else
                                    <span class="text-muted">(eliminado)</span>
                                @endif
                            </td>
                            <td><small class="font-monospace">{{ $log->ip_address ?? '—' }}</small></td>
                            <td><small class="text-muted">{{ $log->reason ?? '—' }}</small></td>
                            <td><small>{{ $log->started_at?->format('d M Y, H:i:s') }}</small></td>
                            <td>
                                @if ($log->ended_at)
                                    <small>{{ $log->ended_at->format('d M Y, H:i:s') }}</small>
                                @else
                                    <span class="badge bg-warning">Activa</span>
                                @endif
                            </td>
                            <td>
                                @if ($log->ended_at)
                                    <small class="text-muted">{{ $log->started_at->diffForHumans($log->ended_at, ['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</small>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No hay impersonaciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
