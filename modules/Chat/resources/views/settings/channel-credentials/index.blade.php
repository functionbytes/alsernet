@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="card bg-info-subtle mb-4">
    <div class="card-body">
        <h5 class="card-title mb-2">Credenciales de canales</h5>
        <p class="card-text text-muted mb-0">
            Verifica el estado de configuración de cada canal
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Canal</th>
                        <th>Estado</th>
                        <th>Credenciales faltantes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($channels as $channelKey => $channel)
                        <tr>
                            <td>
                                <i class="{{ $channel['icon'] ?? 'fas fa-circle' }} me-2"></i>
                                {{ $channel['name'] }}
                            </td>
                            <td>
                                @if($channel['configured'])
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check-circle"></i> Configurado
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Incompleto
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if(!$channel['configured'] && !empty($channel['required']))
                                    <small class="text-muted">{{ implode(', ', $channel['required']) }}</small>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('settings.chat.channel-credentials.show', $channelKey) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-book"></i> Ver guía
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No hay canales disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
