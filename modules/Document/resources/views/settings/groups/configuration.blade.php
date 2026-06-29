@extends('layouts.theme')

@section('title', 'Configuración - ' . $group->name)

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Grupo: ' . $group->name])

    <div class="widget-content">
        @include('core::components.alerts')

        <div class="row">

            <!-- Main Content -->
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.documents.groups.update-configuration', $group->uid) }}">
                    @csrf

                    @if($configurations->isNotEmpty())
                        <div class="card mb-3">
                            <div class="card-body">
                                @php
                                    $categoryIcons = [
                                        'email_actions' => 'fa fa-envelope',
                                        'workflow' => 'fa fa-stream',
                                        'notifications' => 'fa fa-bell',
                                    ];
                                    $categoryLabels = [
                                        'email_actions' => 'Acciones de email',
                                        'workflow' => 'Flujo de trabajo',
                                        'notifications' => 'Notificaciones',
                                    ];
                                    $categoryDescriptions = [
                                        'email_actions' => 'Configura cómo el grupo manejará las acciones relacionadas con el correo electrónico.',
                                        'workflow' => 'Define el comportamiento del flujo de trabajo para este grupo.',
                                        'notifications' => 'Controla qué notificaciones recibirán los miembros del grupo.',
                                    ];
                                @endphp

                                @foreach($configurations as $category => $configs)
                                    <!-- Category Header -->
                                    <div class="col-12 mb-4">
                                        <h6 class="mb-1 fw-semibold">
                                            {{ $categoryLabels[$category] ?? $category }}
                                        </h6>
                                        <p class="text-muted small mb-3">
                                            {{ $categoryDescriptions[$category] ?? 'Configuraciones para ' . ($categoryLabels[$category] ?? $category) }}
                                        </p>
                                    </div>

                                    <!-- Configurations -->
                                    <div class="row g-3 mb-3">
                                        @foreach($configs as $config)
                                            <div class="col-md-12">
                                                <div class="border-bottom pb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               name="configurations[{{ $config->id }}]"
                                                               id="config_{{ $config->id }}"
                                                               value="1"
                                                               {{ $config->value ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="config_{{ $config->id }}">
                                                            <strong class="d-block">{{ $config->label }}</strong>
                                                            @if($config->description)
                                                                <p class="text-muted d-block mb-0">{{ $config->description }}</p>
                                                            @endif
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if(!$loop->last)
                                        <hr class="my-4">
                                    @endif
                                @endforeach



                            </div>
                            @if($configurations->isNotEmpty())
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary w-100 mb-1">
                                        Guardar
                                    </button>
                                    <a href="{{ route('settings.documents.groups.index') }}" class="btn btn-secondary w-100">
                                        Cancelar
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-circle-info me-2"></i>
                            <strong>Sin configuraciones</strong>
                            <p class="mb-0 small mt-2">Este grupo no tiene configuraciones disponibles aún.</p>
                        </div>
                    @endif

                    <!-- Action Buttons -->

                </form>

                <!-- Change History Widget -->
                <div class="card mt-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">
                            Historial de cambios
                        </h6>
                    </div>
                    <div class="card-body {{ $history->count() > 0 ? 'p-0' : '' }}">
                        @if($history->count() > 0)
                            <div class="timeline-list">
                                @foreach($history as $entry)
                                    <div class="timeline-item px-3 py-3 border-bottom">
                                        <div class="d-flex gap-3">

                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-start justify-content-between mb-1">
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">{{ $entry->configuration_label }}</h6>
                                                        <small class="text-muted">
                                                            {{ $entry->new_value ? 'Habilitada' : 'Deshabilitada' }}
                                                        </small>
                                                    </div>
                                                    <small class="text-muted text-nowrap ms-2">
                                                        {{ $entry->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                                <small class="d-block text-muted">
                                                    Por: <strong>{{ $entry->user?->firstname }} {{ $entry->user?->lastname }}</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="border rounded p-3">
                                <p class="text-muted text-center mb-0">No hay cambios registrados</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Información del Grupo -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Información del grupo</h5>
                        <p class="small mb-0 text-muted">Datos básicos del grupo</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted">Nombre</label>
                                <p class="mb-0">{{ $group->name }}</p>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted">Clave</label>
                                <p class="mb-0"><code class="text-primary">{{ $group->key }}</code></p>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted">Estado</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $group->is_active ? 'success' : 'danger' }}">
                                        {{ $group->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted">Modo de asignación</label>
                                @php
                                    $modeLabels = [
                                        'manual' => 'Manual',
                                        'round_robin' => 'Round Robin',
                                        'load_balanced' => 'Balance de Carga'
                                    ];
                                @endphp
                                <p class="mb-0">{{ $modeLabels[$group->assignment_mode] ?? $group->assignment_mode }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Miembros del Grupo -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Miembros</h5>
                            <p class="small mb-0 text-muted">{{ $group->users->count() }} {{ $group->users->count() === 1 ? 'miembro' : 'miembros' }}</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($group->users->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($group->users as $user)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">{{ $user->firstname }} {{ $user->lastname }}
                                                    @if($user->pivot->priority === 'primary')
                                                        <span class="badge bg-primary ms-1">Principal</span>
                                                    @else
                                                        <span class="badge bg-secondary ms-1">Respaldo</span>
                                                    @endif
                                                </h6>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">
                                <p class="mb-0">No hay miembros asignados a este grupo</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Acciones -->
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Acciones</h5>
                        <p class="small mb-0 text-muted">Opciones disponibles</p>
                    </div>
                    <div class="card-body">
                            <a href="{{ route('settings.documents.groups.edit', $group->uid) }}" class="btn btn-primary w-100 mb-1">
                                Editar
                            </a>
                            <a href="{{ route('settings.documents.groups.index') }}" class="btn btn-secondary  w-100">
                                Volver
                            </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
