@extends('layouts.theme')

@section('title', 'Reportes de contactos')

@push('css')
    <style>
        .avatar-placeholder-32 { width: 32px; height: 32px; }
    </style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Reportes de contactos'])
@endsection

@section('content')

    @include('core::components.alerts')

    {{-- Stats cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h4 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h4>
                    <small class="text-muted">Total activos</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-circle-check fa-2x text-success mb-2"></i>
                    <h4 class="fw-bold mb-0">{{ number_format($stats['verified']) }}</h4>
                    <small class="text-muted">Verificados</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h4 class="fw-bold mb-0">{{ number_format($stats['inactive']) }}</h4>
                    <small class="text-muted">Sin actividad 30+ días</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-ban fa-2x text-danger mb-2"></i>
                    <h4 class="fw-bold mb-0">{{ number_format($stats['banned']) }}</h4>
                    <small class="text-muted">Suspendidos</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Contactos en riesgo --}}
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-triangle-exclamation text-warning me-2"></i>
                        Contactos en riesgo
                    </h5>
                    <small class="text-muted">Sin actividad en los últimos 30 días</small>
                </div>

                <div class="card-body p-0">
                    @if ($atRisk->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-circle-check fa-3x mb-3 text-success"></i>
                            <p class="mb-0">No hay contactos en riesgo</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Contacto</th>
                                        <th>Última visita</th>
                                        <th>Canal principal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($atRisk as $customer)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if ($customer->avatar_url)
                                                        <img src="{{ $customer->avatar_url }}"
                                                             class="rounded-circle"
                                                             width="32" height="32"
                                                             alt="{{ $customer->name }}"
                                                             loading="lazy">
                                                    @else
                                                        <div class="avatar-placeholder-32 rounded-circle bg-secondary d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-user text-white small"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-semibold small">{{ $customer->name }}</div>
                                                        @if ($customer->email)
                                                            <div class="text-muted small">{{ $customer->email }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($customer->last_seen_at)
                                                    <span class="text-muted small">
                                                        {{ $customer->last_seen_at->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted">Nunca</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($customer->whatsapp_phone)
                                                    <span class="badge bg-success bg-opacity-10 text-success">
                                                        <i class="fab fa-whatsapp me-1"></i>WhatsApp
                                                    </span>
                                                @elseif ($customer->email)
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                        <i class="fas fa-envelope me-1"></i>Email
                                                    </span>
                                                @elseif ($customer->facebook_psid)
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        <i class="fab fa-facebook me-1"></i>Facebook
                                                    </span>
                                                @elseif ($customer->instagram_id)
                                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                                        <i class="fab fa-instagram me-1"></i>Instagram
                                                    </span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('contacts.show', $customer) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    Ver ficha
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Más activos --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-fire text-primary me-2"></i>
                        Más activos
                    </h5>
                    <small class="text-muted">Por número de conversaciones</small>
                </div>

                <div class="card-body p-0">
                    @if ($topActive->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-comment-slash fa-3x mb-3"></i>
                            <p class="mb-0">Sin datos disponibles</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Contacto</th>
                                        <th class="text-end">Conversaciones</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topActive as $customer)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold small">{{ $customer->name }}</div>
                                                @if ($customer->email)
                                                    <div class="text-muted small">{{ $customer->email }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary">
                                                    {{ number_format($customer->total_conversations) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('contacts.show', $customer) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    Ver ficha
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
