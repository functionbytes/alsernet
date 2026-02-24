@extends('layouts.theme')

@section('title', 'Campañas')

@section('content')
<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    {{-- Main Card --}}
    <div class="card">
        {{-- Header Section --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Campañas de email</h5>
                    <p class="small mb-0 text-muted">Gestiona y monitorea tus campañas de email marketing</p>
                </div>
                <a href="{{ route('mailrelay.campaigns.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Crear campaña
                </a>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="card-body border-bottom">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == '' ? 'active' : '' }}" href="{{ route('mailrelay.campaigns.index') }}">
                        Todas las campañas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'draft' ? 'active' : '' }}" href="{{ route('mailrelay.campaigns.index', ['status' => 'draft']) }}">
                        Borradores
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'scheduled' ? 'active' : '' }}" href="{{ route('mailrelay.campaigns.index', ['status' => 'scheduled']) }}">
                        Programadas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'sent' ? 'active' : '' }}" href="{{ route('mailrelay.campaigns.index', ['status' => 'sent']) }}">
                        Enviadas
                    </a>
                </li>
            </ul>
        </div>

        {{-- Campaigns Grid --}}
        <div class="card-body">
            @if($campaigns->isEmpty())
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-inbox fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay campañas todavía</h6>
                        <p class="text-muted mb-3">Crea tu primera campaña de email para comenzar</p>
                        <a href="{{ route('mailrelay.campaigns.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Crear primera campaña
                        </a>
                    </div>
                </div>
            @else
                <div class="row">
                    @foreach($campaigns as $campaign)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0 fw-bold">{{ $campaign->name }}</h5>
                                    @if($campaign->status === 'draft')
                                        <span class="badge bg-secondary-subtle text-secondary rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-pencil"></i> Borrador
                                        </span>
                                    @elseif($campaign->status === 'scheduled')
                                        <span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-clock"></i> Programada
                                        </span>
                                    @elseif($campaign->status === 'sending')
                                        <span class="badge bg-warning-subtle text-warning rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-hourglass-half"></i> Enviando
                                        </span>
                                    @elseif($campaign->status === 'sent')
                                        <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-check-circle"></i> Enviada
                                        </span>
                                    @elseif($campaign->status === 'failed')
                                        <span class="badge bg-danger-subtle text-danger rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-times-circle"></i> Fallida
                                        </span>
                                    @endif
                                </div>

                                <p class="card-text text-muted small mb-3">
                                    {{ Str::limit($campaign->subject, 60) }}
                                </p>

                                @if($campaign->status === 'scheduled')
                                <div class="alert alert-info py-2 mb-3 border-0">
                                    <i class="fas fa-calendar-alt"></i>
                                    <small>{{ $campaign->scheduled_at->format('M d, Y H:i') }}</small>
                                </div>
                                @endif

                                {{-- Campaign Stats --}}
                                @if($campaign->status === 'sent')
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="text-center p-2 bg-light-secondary rounded">
                                            <div class="fw-bold text-primary">{{ number_format($campaign->recipients_count ?? 0) }}</div>
                                            <small class="text-muted">Enviados</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 bg-light-secondary rounded">
                                            <div class="fw-bold text-success">{{ number_format($campaign->opens ?? 0) }}</div>
                                            <small class="text-muted">Aperturas</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 bg-light-secondary rounded">
                                            <div class="fw-bold text-info">{{ number_format($campaign->clicks ?? 0) }}</div>
                                            <small class="text-muted">Clics</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 bg-light-secondary rounded">
                                            <div class="fw-bold text-warning">
                                                {{ $campaign->recipients_count > 0 ? number_format(($campaign->opens / $campaign->recipients_count) * 100, 1) : 0 }}%
                                            </div>
                                            <small class="text-muted">Tasa apertura</small>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="text-muted small mb-3">
                                    <i class="fas fa-calendar"></i> Creada {{ $campaign->created_at->diffForHumans() }}
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="{{ route('mailrelay.campaigns.show', $campaign->id) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    @if($campaign->status === 'draft')
                                    <a href="{{ route('mailrelay.campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-pencil"></i> Editar
                                    </a>
                                    @endif
                                    <div class="dropdown">
                                        <a href="#" class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('mailrelay.campaigns.show', $campaign->id) }}">
                                                    Ver detalles
                                                </a>
                                            </li>
                                            @if($campaign->status === 'draft')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('mailrelay.campaigns.edit', $campaign->id) }}">
                                                    Editar
                                                </a>
                                            </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item confirm-delete"
                                                   data-href="{{ route('mailrelay.campaigns.destroy', $campaign->id) }}">
                                                    Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if($campaigns->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="result-body">
                        <span>Mostrando {{ $campaigns->firstItem() }}-{{ $campaigns->lastItem() }} de {{ $campaigns->total() }} resultados</span>
                    </div>
                    <nav>
                        {{ $campaigns->links() }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
