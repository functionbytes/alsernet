@extends('layouts.theme')

@section('title', 'Salud del Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Salud del Helpdesk'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Diagnóstico operativo</h5>
            <p class="text-muted mb-0">Estado de las dependencias necesarias para recibir y responder mensajes.</p>
        </div>
        <a href="{{ route('settings.helpdesk.health') }}" class="btn btn-outline-primary">
            <i class="fas fa-rotate-right me-1"></i> Actualizar
        </a>
    </div>

    @php
        $status = $health['status'] ?? 'unknown';
        $statusClass = match ($status) {
            'ok' => 'success',
            'degraded' => 'warning',
            default => 'danger',
        };
    @endphp

    <div class="alert alert-{{ $statusClass }} d-flex align-items-center gap-2" role="status">
        <i class="fas {{ $status === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
        <span>Estado global: <strong>{{ strtoupper($status) }}</strong></span>
        <small class="ms-auto">{{ $health['timestamp'] ?? '' }}</small>
    </div>

    <div class="row g-3">
        @foreach(($health['checks'] ?? []) as $name => $check)
            @php $ok = (bool) ($check['ok'] ?? false); @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border-{{ $ok ? 'success' : 'danger' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0">{{ str_replace('_', ' ', ucfirst($name)) }}</h6>
                            <span class="badge bg-{{ $ok ? 'success' : 'danger' }}">{{ $ok ? 'OK' : 'ERROR' }}</span>
                        </div>
                        @if(isset($check['latency_ms']))
                            <small class="text-muted">Latencia: {{ $check['latency_ms'] }} ms</small>
                        @endif
                        @if(isset($check['count']))
                            <small class="text-muted d-block">Pendientes: {{ number_format((int) $check['count']) }}</small>
                        @endif
                        @if(isset($check['status']))
                            <small class="text-muted d-block">HTTP: {{ $check['status'] }}</small>
                        @endif
                        @if(isset($check['error']))
                            <small class="text-brand d-block mt-2">{{ $check['error'] }}</small>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
