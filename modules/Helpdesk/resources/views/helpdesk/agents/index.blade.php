@extends('layouts.theme')

@section('title', 'Agentes del helpdesk')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-headset me-2 text-primary"></i>
                Agentes del helpdesk
            </h4>
            <p class="text-muted mb-0">Gestiona los agentes de soporte y su disponibilidad</p>
        </div>
        @if(request('search'))
            <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times me-1"></i> Limpiar búsqueda
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ request()->url() }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" name="search" class="form-control -0"
                                   placeholder="Buscar por nombre o email..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Buscar</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body p-0">
            @if(isset($agents) && $agents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Agente</th>
                                <th scope="col">Email</th>
                                <th scope="col" class="text-center">Disponibilidad</th>
                                <th scope="col" class="text-center">Tickets abiertos</th>
                                <th scope="col" class="text-center">Cerrados hoy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agents as $agent)
                                <tr>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold bv-icon-circle-36"
                                                 >
                                                {{ strtoupper(substr($agent->name ?? $agent->firstname ?? '?', 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $agent->name ?? trim(($agent->firstname ?? '') . ' ' . ($agent->lastname ?? '')) }}</strong>
                                                @if($agent->roles->isNotEmpty())
                                                    <small class="d-block text-muted">
                                                        {{ $agent->roles->first()->name }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-muted">{{ $agent->email }}</td>
                                    <td class="text-center align-middle">
                                        @php
                                            $availability = $agent->agentSettings->accepts_conversations ?? 'no';
                                        @endphp
                                        @if($availability === 'yes')
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-circle me-1 bv-text-tiny"></i> Disponible
                                            </span>
                                        @elseif($availability === 'working_hours')
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="fas fa-clock me-1"></i> Horario laboral
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="fas fa-circle me-1 bv-text-tiny"></i> No disponible
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="fw-semibold">
                                            {{ $agent->openTicketsCount ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="fw-semibold text-success">
                                            {{ $agent->closedTodayCount ?? 0 }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-headset fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">
                        @if(request('search'))
                            Sin resultados para "{{ request('search') }}"
                        @else
                            No hay agentes registrados
                        @endif
                    </h6>
                </div>
            @endif
        </div>

        @if(isset($agents) && method_exists($agents, 'hasPages') && $agents->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Mostrando {{ $agents->firstItem() }}–{{ $agents->lastItem() }} de {{ $agents->total() }}
                </small>
                {{ $agents->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
