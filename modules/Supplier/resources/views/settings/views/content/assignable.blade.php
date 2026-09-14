@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')
    <div class="widget-content">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header border-bottom p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Revisores de contenido</h5>
                        <p class="small mb-0 text-muted">Estado actual de asignaciones y progreso por revisor</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.content.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats globales --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Sin asignar</h6>
                                <h4 class="mb-1 fw-bold">{{ $unassignedCount }}</h4>
                                <small class="text-muted">Pendientes sin revisor</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Revisores activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $users->count() }}</h4>
                                <small class="text-muted">Con acceso al módulo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total generado</h6>
                                <h4 class="mb-1 fw-bold">{{ $totalContent }}</h4>
                                <small class="text-muted">Contenidos generados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabla de revisores --}}
            <div class="card-body">
                @if($users->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-users fs-2 mb-3 d-block opacity-25"></i>
                        <p class="mb-0">No hay usuarios con acceso para revisar contenido.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Revisor</th>
                                    <th class="text-center" style="width:100px">Asignados</th>
                                    <th class="text-center" style="width:100px">Pendientes</th>
                                    <th class="text-center" style="width:100px">Aprobados</th>
                                    <th class="text-center" style="width:100px">Rechazados</th>
                                    <th class="text-center" style="width:100px">Publicados</th>
                                    <th style="min-width:180px">Progreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    @php
                                        $done = $user->assigned_count - $user->pending_count;
                                        $pct  = $user->assigned_count > 0
                                                    ? round($done / $user->assigned_count * 100)
                                                    : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $user->full_name }}</strong>
                                            <br><small class="text-muted">{{ $user->email }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary px-2">{{ $user->assigned_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($user->pending_count > 0)
                                                <span class="badge bg-primary text-white px-2">{{ $user->pending_count }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($user->approved_count > 0)
                                                <span class="badge bg-success px-2">{{ $user->approved_count }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($user->rejected_count > 0)
                                                <span class="badge bg-danger px-2">{{ $user->rejected_count }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($user->published_count > 0)
                                                <span class="badge bg-info px-2">{{ $user->published_count }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->assigned_count > 0)
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height:6px">
                                                        <div class="progress-bar bg-success"
                                                             role="progressbar"
                                                             style="width:{{ $pct }}%"
                                                             aria-valuenow="{{ $pct }}"
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted text-nowrap">{{ $pct }}%</small>
                                                </div>
                                            @else
                                                <span class="text-muted small">Sin asignaciones</span>
                                            @endif
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

@endsection
