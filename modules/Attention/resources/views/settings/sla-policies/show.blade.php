@extends('layouts.theme')

@section('title', 'Detalles Política SLA')

@section('content')

    @include('core::components.card', ['title' => 'Detalles de Política SLA'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $policy->name }}</h5>
                        <div class="d-flex gap-1 mt-1">
                            @if($policy->is_default)
                                <span class="badge bg-primary">Por defecto</span>
                            @endif
                            @if($policy->active)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.attention.sla-policies.edit', $policy->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-pen me-1"></i> Editar
                        </a>
                        <a href="{{ route('settings.attention.sla-policies.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tiempos SLA --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Respuesta</h6>
                                        <h4 class="mb-1 fw-bold">{{ round($policy->response_time / 60, 1) }}h</h4>
                                        <small class="text-muted">{{ number_format($policy->response_time) }} min</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Resolución</h6>
                                        <h4 class="mb-1 fw-bold">{{ round($policy->resolution_time / 60, 1) }}h</h4>
                                        <small class="text-muted">{{ number_format($policy->resolution_time) }} min</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Cierre</h6>
                                        <h4 class="mb-1 fw-bold">{{ round($policy->closure_time / 60, 1) }}h</h4>
                                        <small class="text-muted">{{ number_format($policy->closure_time) }} min</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Estadísticas --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">PQRSF asignadas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $policy->attentions->count() }}</h4>
                                        <small class="text-muted">Con esta política</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Incumplimientos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $breachStats['total'] }}</h4>
                                        <small class="text-muted">Total registrados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Sin resolver</h6>
                                        <h4 class="mb-1 fw-bold">{{ $breachStats['unresolved'] }}</h4>
                                        <small class="text-muted">Pendientes</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Escalados</h6>
                                        <h4 class="mb-1 fw-bold">{{ $breachStats['escalated'] }}</h4>
                                        <small class="text-muted">Requieren atención</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Escalación y Configuración --}}
            <div class="card-body border-bottom">
                <div class="row g-5">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark mb-1">Escalación</h6>
                        <p class="text-muted mb-3">Comportamiento automático cuando se supera el tiempo límite.</p>
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label fw-semibold mb-1">Habilitada</label>
                                <div>
                                    @if($policy->enable_escalation)
                                        <span class="badge bg-success">Sí</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </div>
                            </div>
                            @if($policy->enable_escalation)
                                <div>
                                    <label class="form-label fw-semibold mb-1">Umbral de activación</label>
                                    <div>
                                        <span class="badge bg-warning text-dark fs-6 px-3">{{ $policy->escalation_threshold_percent }}%</span>
                                        <small class="text-muted ms-2">del tiempo SLA consumido</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark mb-1">Configuración</h6>
                        <p class="text-muted mb-3">Parámetros de horario y zona horaria aplicados al cálculo.</p>
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label fw-semibold mb-1">Zona horaria</label>
                                <div><code>{{ $policy->timezone }}</code></div>
                            </div>
                            <div>
                                <label class="form-label fw-semibold mb-1">Solo horario laboral</label>
                                <div>
                                    @if($policy->business_hours_only)
                                        <span class="badge bg-info">Sí</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </div>
                            </div>
                            @if($policy->description)
                                <div>
                                    <label class="form-label fw-semibold mb-1">Descripción</label>
                                    <p class="text-muted mb-0 small">{{ $policy->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Multiplicadores por Tipo --}}
            <div class="card-body border-bottom">
                <h6 class="fw-bold text-dark mb-1">Multiplicadores por tipo</h6>
                <p class="text-muted mb-3">Tiempos SLA ajustados según el tipo de PQRSF.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th class="text-center">Multiplicador</th>
                                <th class="text-center">Respuesta</th>
                                <th class="text-center">Resolución</th>
                                <th class="text-center">Cierre</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['PETICION' => 1.0, 'QUEJA' => 0.7, 'RECLAMO' => 0.7, 'SUGERENCIA' => 1.3, 'FELICITACION' => 1.5] as $type => $defaultMult)
                                @php $multiplier = $policy->getMultiplierForType($type); @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $type }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $multiplier }}x</span>
                                    </td>
                                    <td class="text-center text-muted">{{ round(($policy->response_time * $multiplier) / 60, 1) }}h</td>
                                    <td class="text-center text-muted">{{ round(($policy->resolution_time * $multiplier) / 60, 1) }}h</td>
                                    <td class="text-center text-muted">{{ round(($policy->closure_time * $multiplier) / 60, 1) }}h</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Incumplimientos por Tipo --}}
            @if(!empty($breachStats['by_type']))
                <div class="card-body border-bottom">
                    <h6 class="fw-bold text-dark mb-1">Incumplimientos por tipo</h6>
                    <p class="text-muted mb-3">Distribución de incumplimientos según la categoría de PQRSF.</p>
                    @foreach($breachStats['by_type'] as $type => $count)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-capitalize small fw-semibold">{{ $type }}</span>
                                <strong class="small">{{ $count }}</strong>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-danger" style="width: {{ ($count / $breachStats['total']) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Incumplimientos Recientes --}}
            @if($breachStats['recent']->count() > 0)
                <div class="card-body border-bottom">
                    <h6 class="fw-bold text-dark mb-1">Incumplimientos recientes</h6>
                    <p class="text-muted mb-3">Últimos incumplimientos registrados en esta política.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">PQRSF</th>
                                <th>Tipo</th>
                                <th>Minutos excedidos</th>
                                <th>Escalado</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($breachStats['recent'] as $breach)
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('attention.show', $breach->attention->uid) }}" class="fw-semibold text-decoration-none">
                                            {{ $breach->attention->radicado }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $breach->breach_type }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger-subtle text-danger">{{ $breach->minutes_over }} min</span>
                                    </td>
                                    <td>
                                        @if($breach->escalated)
                                            <span class="badge bg-danger">Sí</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($breach->resolved)
                                            <span class="badge bg-success">Resuelto</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $breach->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>

    </div>

@endsection
