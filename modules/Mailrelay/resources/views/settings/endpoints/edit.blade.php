@extends('layouts.theme')

@section('title', 'Editar endpoint')

@section('content')
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Editar endpoint</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('settings.mailrelay.endpoints.index') }}">Endpoints</a></li>
                    <li class="breadcrumb-item active">{{ $endpoint->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('settings.mailrelay.endpoints.logs', $endpoint->id) }}" class="btn btn-info">
                <i class="fas fa-list me-2"></i>Ver logs
            </a>
            <form action="{{ route('settings.mailrelay.endpoints.destroy', $endpoint->id) }}" method="POST" class="d-inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
            </form>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-light-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary p-3 me-3">
                            <i class="fas fa-paper-plane text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Total enviados</h6>
                            <h4 class="mb-0">{{ number_format($stats['total'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-light-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success p-3 me-3">
                            <i class="fas fa-check-circle text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Exitosos</h6>
                            <h4 class="mb-0">{{ number_format($stats['success'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-light-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-danger p-3 me-3">
                            <i class="fas fa-times-circle text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Fallidos</h6>
                            <h4 class="mb-0">{{ number_format($stats['failed'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-light-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info p-3 me-3">
                            <i class="fas fa-percentage text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Tasa de éxito</h6>
                            <h4 class="mb-0">{{ number_format($stats['success_rate'] ?? 0, 1) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Additional Stats --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Información de uso</h6>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Último uso</small>
                                <strong>
                                    @if($endpoint->last_used_at)
                                        {{ $endpoint->last_used_at->format('d/m/Y H:i') }}
                                        <small class="text-muted">({{ $endpoint->last_used_at->diffForHumans() }})</small>
                                    @else
                                        <span class="text-muted">Nunca usado</span>
                                    @endif
                                </strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Creado</small>
                                <strong>{{ $endpoint->created_at->format('d/m/Y H:i') }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Rate limit</small>
                                <strong>{{ $endpoint->rate_limit }} requests/minuto</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">IPs permitidas</small>
                                <strong>
                                    @if($endpoint->allowed_ips && count($endpoint->allowed_ips) > 0)
                                        {{ count($endpoint->allowed_ips) }} IP(s)
                                    @else
                                        <span class="text-muted">Todas</span>
                                    @endif
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Actividad reciente (últimas 24h)</h6>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Requests hoy</small>
                                <strong class="fs-4">{{ number_format($stats['today'] ?? 0) }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Promedio/hora</small>
                                <strong class="fs-4">{{ number_format($stats['avg_per_hour'] ?? 0, 1) }}</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-0">
                                <small class="text-muted d-block mb-2">IPs únicas hoy</small>
                                <strong>{{ $stats['unique_ips_today'] ?? 0 }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('settings.mailrelay.endpoints.update', $endpoint->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('mailrelay::settings.endpoints._form')
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-form').on('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar este endpoint? Se eliminarán también todos sus logs y estadísticas.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
