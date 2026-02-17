@extends('layouts.theme')

@section('title', 'Detalles Política SLA')

@section('content')

    @include('core::components.card', ['title' => 'Detalles de Política SLA'])

    <div class="row">
        <!-- Policy Details Card -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $policy->name }}</h5>
                            @if($policy->is_default)
                                <span class="badge bg-primary">Por defecto</span>
                            @endif
                            @if($policy->active)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('settings.attention.sla-policies.edit', $policy->id) }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-pen"></i> Editar
                            </a>
                            <a href="{{ route('settings.attention.sla-policies.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fa fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if($policy->description)
                        <div class="alert alert-info mb-4">
                            {{ $policy->description }}
                        </div>
                    @endif

                    <h6 class="fw-bold mb-3">Tiempos SLA</h6>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="text-muted small mb-1">Respuesta</div>
                                <h4 class="mb-0 text-info">{{ round($policy->response_time / 60, 1) }}h</h4>
                                <small class="text-muted">{{ $policy->response_time }} min</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="text-muted small mb-1">Resolución</div>
                                <h4 class="mb-0 text-warning">{{ round($policy->resolution_time / 60, 1) }}h</h4>
                                <small class="text-muted">{{ $policy->resolution_time }} min</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="text-muted small mb-1">Cierre</div>
                                <h4 class="mb-0 text-success">{{ round($policy->closure_time / 60, 1) }}h</h4>
                                <small class="text-muted">{{ $policy->closure_time }} min</small>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Multiplicadores por Tipo</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
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
                                    @php
                                        $multiplier = $policy->getMultiplierForType($type);
                                    @endphp
                                    <tr>
                                        <td>{{ $type }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $multiplier }}x</span>
                                        </td>
                                        <td class="text-center">{{ round(($policy->response_time * $multiplier) / 60, 1) }}h</td>
                                        <td class="text-center">{{ round(($policy->resolution_time * $multiplier) / 60, 1) }}h</td>
                                        <td class="text-center">{{ round(($policy->closure_time * $multiplier) / 60, 1) }}h</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mb-3">Escalación</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Habilitada:</strong>
                                @if($policy->enable_escalation)
                                    <span class="badge bg-success">Sí</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </p>
                        </div>
                        @if($policy->enable_escalation)
                            <div class="col-md-6">
                                <p><strong>Umbral:</strong>
                                    <span class="badge bg-warning">{{ $policy->escalation_threshold_percent }}%</span>
                                </p>
                            </div>
                        @endif
                    </div>

                    <h6 class="fw-bold mb-3">Configuración</h6>
                    <p><strong>Zona Horaria:</strong> {{ $policy->timezone }}</p>
                    <p><strong>Solo Horario Laboral:</strong>
                        @if($policy->business_hours_only)
                            <span class="badge bg-info">Sí</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistics Card -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">PQRSF con esta política</span>
                            <strong>{{ $policy->attentions->count() }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total incumplimientos</span>
                            <strong class="text-danger">{{ $breachStats['total'] }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Sin resolver</span>
                            <strong class="text-warning">{{ $breachStats['unresolved'] }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Escalados</span>
                            <strong class="text-danger">{{ $breachStats['escalated'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Breaches by Type -->
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Incumplimientos por Tipo</h6>
                </div>
                <div class="card-body">
                    @if(!empty($breachStats['by_type']))
                        @foreach($breachStats['by_type'] as $type => $count)
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-capitalize">{{ $type }}</span>
                                    <strong>{{ $count }}</strong>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-danger" style="width: {{ ($count / $breachStats['total']) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No hay incumplimientos registrados</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Breaches -->
    @if($breachStats['recent']->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Incumplimientos Recientes</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>PQRSF</th>
                                        <th>Tipo</th>
                                        <th>Minutos Excedidos</th>
                                        <th>Escalado</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($breachStats['recent'] as $breach)
                                        <tr>
                                            <td>
                                                <a href="{{ route('attention.show', $breach->attention->uid) }}">
                                                    {{ $breach->attention->radicado }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $breach->breach_type }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">{{ $breach->minutes_over }} min</span>
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
                                                    <span class="badge bg-warning">Pendiente</span>
                                                @endif
                                            </td>
                                            <td>{{ $breach->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
