@extends('layouts.theme')

@section('title', 'Detalle del registro')

@section('page_header')
    @include('core::components.card', ['title' => 'Detalle del registro'])
@endsection

@section('content')

    @php
        $properties = $activity->properties?->toArray() ?? [];
        $old        = $properties['old'] ?? [];
        $attributes = $properties['attributes'] ?? [];
        $eventColors = ['created' => 'success', 'updated' => 'primary', 'deleted' => 'danger'];
        $color = $eventColors[$activity->event] ?? 'secondary';
        $allFields = array_unique(array_merge(array_keys($old), array_keys($attributes)));
    @endphp

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: contenido principal --}}
        <div class="col-lg-8">

            <div class="card">

                <div class="card-body">

                    {{-- Información general --}}
                    <h6 class="fw-bold text-dark mb-1">Información general</h6>
                    <p class="text-muted mb-3">Datos principales del evento registrado en el sistema</p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Evento</label>
                            <input type="text" class="form-control" value="{{ $activity->event }}" disabled>
                        </div>
                        @if($activity->subject_type)
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Modelo</label>
                                <input type="text" class="form-control" value="{{ class_basename($activity->subject_type) }}{{ $activity->subject_id ? ' #'.$activity->subject_id : '' }}" disabled>
                            </div>
                        @endif
                        @if($activity->log_name)
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Log</label>
                                <input type="text" class="form-control" value="{{ $activity->log_name }}" disabled>
                            </div>
                        @endif
                        @if(!empty($properties['ip']))
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">IP</label>
                                <input type="text" class="form-control font-monospace" value="{{ $properties['ip'] }}" disabled>
                            </div>
                        @endif
                        @if($activity->description)
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción</label>
                                <textarea class="form-control" rows="2" disabled>{{ $activity->description }}</textarea>
                            </div>
                        @endif
                    </div>

                </div>

                <hr class="my-0">

                {{-- Usuario --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Usuario</h6>
                    <p class="text-muted mb-3">Responsable de la acción registrada</p>

                    <div class="row g-3">
                        @if($activity->causer)
                            @if($activity->causer->name)
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Nombre</label>
                                    <input type="text" class="form-control" value="{{ $activity->causer->name }}" disabled>
                                </div>
                            @endif
                            @if($activity->causer->email)
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="text" class="form-control" value="{{ $activity->causer->email }}" disabled>
                                </div>
                            @endif
                        @else
                            <div class="col-12">
                                <input type="text" class="form-control" value="Sistema (sin usuario)" disabled>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Cambios realizados --}}
                @if(!empty($old) || !empty($attributes))
                    <hr class="my-0">

                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Cambios realizados</h6>
                        <p class="text-muted mb-3">Comparación de valores anteriores y nuevos para cada campo modificado</p>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered w-100 text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Campo</th>
                                        @if(!empty($old))<th>Valor anterior</th>@endif
                                        @if(!empty($attributes))<th>Valor nuevo</th>@endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allFields as $field)
                                        @php
                                            $changed = isset($old[$field], $attributes[$field]) && $old[$field] != $attributes[$field];
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $field }}</td>
                                            @if(!empty($old))
                                                <td class="{{ $changed ? 'text-danger' : '' }}">
                                                    {{ $old[$field] ?? '—' }}
                                                </td>
                                            @endif
                                            @if(!empty($attributes))
                                                <td class="{{ $changed ? 'text-success fw-semibold' : '' }}">
                                                    {{ $attributes[$field] ?? '—' }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="card-footer">
                    <a href="{{ route('activity.logs') }}" class="btn btn-primary w-100">
                        Volver al listado
                    </a>
                </div>

            </div>

        </div>

        {{-- Columna derecha: sidebar informativo --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Resumen</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">ID</span>
                        <span class="fw-bold">#{{ $activity->id }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Fecha</span>
                        <span class="fw-bold">{{ $activity->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Hora</span>
                        <span class="fw-bold">{{ $activity->created_at->format('H:i:s') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Hace</span>
                        <span class="fw-bold">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Campos afectados</span>
                        <span class="fw-bold">{{ count($allFields) }}</span>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-1 fw-semibold">Exportar registros</h6>
                    <p class="text-muted mb-0">
                        Descarga el historial completo de cambios en formato CSV para análisis externo.
                    </p>
                    <a href="{{ route('activity.export') }}" class="btn btn-info w-100 mt-2">Exportar CSV</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre los registros</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Cada registro captura quién realizó la acción, qué modelo fue afectado y los valores modificados.</li>
                        <li class="mb-2">Los eventos pueden ser: <strong>created</strong>, <strong>updated</strong> o <strong>deleted</strong>.</li>
                        <li class="mb-2">Los valores anteriores y nuevos permiten rastrear el historial completo de cambios.</li>
                        <li>La IP del usuario se registra automáticamente cuando está disponible.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

@endsection
