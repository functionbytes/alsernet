@extends('template::layouts.default')

@section('title', 'Seguimiento PQRSF')

@section('content')


<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">

                {{-- Formulario de busqueda --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:70px;height:70px;background:linear-gradient(135deg,#90bb13 0%,#13C672 100%);box-shadow:0 4px 20px rgba(144,187,19,0.3);">
                                <i class="fas fa-magnifying-glass fa-2x text-white"></i>
                            </div>
                            <h4 class="fw-bold mb-2">Consulte el estado de su PQRSF</h4>
                            <p class="text-muted mb-0">Ingrese su numero de radicado para ver el estado actual</p>
                        </div>

                        <form action="{{ route('pqrsf.tracking') }}" method="GET" class="mx-auto" style="max-width:550px;">
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1 position-relative">
                                    <i class="fas fa-search position-absolute" style="left:1.25rem;top:50%;transform:translateY(-50%);color:#90bb13;z-index:5;"></i>
                                    <input type="text" class="form-control form-control-lg" name="radicado"
                                           style="padding-left:3rem;border-radius:0.75rem;border:2px solid #e0e0e0;"
                                           value="{{ $radicado ?? request('radicado') }}"
                                           placeholder="Ej: PQRSF-2026-000001" required>
                                </div>
                                <button class="btn btn-primary btn-lg px-4" type="submit" style="border-radius:0.75rem;white-space:nowrap;">
                                    Consultar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if(isset($radicado))
                    @if(isset($attention) && $attention)

                        {{-- Cabecera del radicado --}}
                        <div class="card mb-4">
                            <div class="card-header p-3" style="background:linear-gradient(135deg,#2a3042,#1a2030);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1 text-white fw-bold">Radicado: {{ $attention->radicado }}</h5>
                                        <p class="mb-0 small text-white-50">
                                            {{ $attention->type->name ?? '' }}
                                            @if($attention->category)— {{ $attention->category->name }}@endif
                                        </p>
                                    </div>
                                    <span class="badge bg-white fs-6" style="color:{{ $attention->status->color() }};">
                                        {{ $attention->status->label() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1 small">Asunto</p>
                                        <p class="fw-semibold mb-0">{{ $attention->subject }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1 small">Fecha de radicacion</p>
                                        <p class="fw-semibold mb-0">{{ $attention->created_at->format('d/m/Y H:i:s') }}</p>
                                    </div>
                                    @if($attention->sede)
                                        <div class="col-md-6 mb-3">
                                            <p class="text-muted mb-1 small">Sede</p>
                                            <p class="fw-semibold mb-0">{{ $attention->sede->name }}</p>
                                        </div>
                                    @endif
                                    @if($attention->department)
                                        <div class="col-md-6 mb-3">
                                            <p class="text-muted mb-1 small">Departamento asignado</p>
                                            <p class="fw-semibold mb-0">{{ $attention->department->name }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Timeline de estados --}}
                        <div class="card mb-4">
                            <div class="card-header bg-white p-3 border-bottom">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-list-check me-2" style="color:#90bb13;"></i>Historial de estados
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                @php
                                    $statusValue = $attention->status->value;
                                    $isInProcess = in_array($statusValue, ['in_process', 'resolved', 'closed']);
                                    $isResolved  = in_array($statusValue, ['resolved', 'closed']);
                                    $isClosed    = $statusValue === 'closed';
                                @endphp

                                <div class="tracking-timeline">
                                    <div class="timeline-step completed">
                                        <div class="timeline-icon"><i class="fas fa-inbox"></i></div>
                                        <div class="timeline-body">
                                            <h6 class="mb-1">Recibido</h6>
                                            <p class="text-muted mb-0 small">
                                                Su PQRSF ha sido radicado exitosamente<br>
                                                {{ $attention->created_at->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="timeline-step {{ $isInProcess ? 'completed' : ($statusValue === 'received' ? 'pending' : '') }} {{ $statusValue === 'in_process' ? 'active' : '' }}">
                                        <div class="timeline-icon"><i class="fas fa-gears"></i></div>
                                        <div class="timeline-body">
                                            <h6 class="mb-1">En proceso</h6>
                                            <p class="text-muted mb-0 small">
                                                @if($isInProcess)
                                                    Su solicitud esta siendo atendida
                                                    @if($attention->department) por {{ $attention->department->name }}@endif
                                                @else
                                                    Pendiente de asignacion
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="timeline-step {{ $isResolved ? 'completed' : 'pending' }} {{ $statusValue === 'resolved' ? 'active' : '' }}">
                                        <div class="timeline-icon"><i class="fas fa-check-circle"></i></div>
                                        <div class="timeline-body">
                                            <h6 class="mb-1">Resuelto</h6>
                                            <p class="text-muted mb-0 small">
                                                @if($isResolved)
                                                    Su PQRSF ha sido resuelto
                                                    @if($attention->resolved_at)<br>{{ $attention->resolved_at->format('d/m/Y H:i') }}@endif
                                                @else
                                                    Pendiente de resolucion
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="timeline-step {{ $isClosed ? 'completed' : 'pending' }}">
                                        <div class="timeline-icon"><i class="fas fa-lock"></i></div>
                                        <div class="timeline-body">
                                            <h6 class="mb-1">Cerrado</h6>
                                            <p class="text-muted mb-0 small">
                                                @if($isClosed)
                                                    El caso ha sido cerrado
                                                    @if($attention->closed_at)<br>{{ $attention->closed_at->format('d/m/Y H:i') }}@endif
                                                @else
                                                    Pendiente de cierre
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Respuesta oficial --}}
                        @if($attention->resolution)
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white p-3">
                                    <h5 class="mb-0 text-white fw-bold">
                                        <i class="fas fa-reply me-2"></i>Respuesta oficial
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($attention->response_type)
                                        <p class="mb-2">
                                            <strong>Tipo de respuesta:</strong>
                                            <span class="badge bg-light text-dark">{{ $attention->response_type->label() }}</span>
                                        </p>
                                    @endif
                                    <div class="bg-light rounded p-3">
                                        {!! nl2br(e($attention->resolution)) !!}
                                    </div>
                                </div>
                            </div>
                        @endif

                    @else
                        {{-- No encontrado --}}
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center p-5">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                                     style="width:100px;height:100px;background:rgba(254,201,15,0.15);">
                                    <i class="fas fa-circle-exclamation text-warning" style="font-size:3rem;"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Radicado no encontrado</h4>
                                <p class="text-muted mb-4">
                                    No se encontro ninguna solicitud con el radicado
                                    <strong class="text-dark">{{ $radicado }}</strong>.
                                    <br>Verifique el numero e intentelo nuevamente.
                                </p>
                                <div class="alert alert-light border text-start">
                                    <p class="mb-2"><strong>Verifique que:</strong></p>
                                    <ul class="mb-0 text-muted small">
                                        <li>El numero de radicado este escrito correctamente</li>
                                        <li>Incluya los guiones (Ej: PQRSF-2026-000001)</li>
                                        <li>El radicado haya sido generado en nuestro sistema</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="text-center mt-4">
                    <a href="{{ route('pqrsf.form') }}" class="btn btn-outline-primary">
                        <i class="fas fa-file-pen me-2"></i>Radicar nueva solicitud
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

@push('css')
<style>
    .tracking-timeline {
        position: relative;
        padding-left: 60px;
    }

    .tracking-timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 5px;
        bottom: 5px;
        width: 3px;
        background: #e0e0e0;
        border-radius: 3px;
    }

    .timeline-step {
        position: relative;
        padding-bottom: 30px;
        opacity: 0.4;
    }

    .timeline-step:last-child { padding-bottom: 0; }

    .timeline-step.completed,
    .timeline-step.active { opacity: 1; }

    .timeline-icon {
        position: absolute;
        left: -48px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 16px;
        z-index: 1;
        border: 3px solid #fff;
    }

    .timeline-step.completed .timeline-icon { background: #13C672; color: #fff; }

    .timeline-step.active .timeline-icon {
        background: #FEC90F;
        color: #fff;
        animation: pulse-step 2s infinite;
    }

    .timeline-step.pending .timeline-icon { background: #e9ecef; color: #adb5bd; }

    .timeline-body {
        background: #f8f9fa;
        padding: 12px 16px;
        border-radius: 8px;
        border-left: 3px solid #e0e0e0;
    }

    .timeline-step.completed .timeline-body { border-left-color: #13C672; }
    .timeline-step.active .timeline-body { border-left-color: #FEC90F; background: #fffde6; }

    @keyframes pulse-step {
        0%   { box-shadow: 0 0 0 0 rgba(254,201,15,0.7); }
        70%  { box-shadow: 0 0 0 10px rgba(254,201,15,0); }
        100% { box-shadow: 0 0 0 0 rgba(254,201,15,0); }
    }
</style>
@endpush

@endsection
