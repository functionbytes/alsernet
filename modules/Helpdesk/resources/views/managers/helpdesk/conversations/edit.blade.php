@extends('layouts.theme')

@section('title', 'Editar Conversación - Helpdesk')

@section('content')

    @include('core::components.card', ['title' => 'Editar Conversación'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form method="POST" action="{{ route('manager.helpdesk.conversations.update', $conversation) }}">
            @csrf
            @method('PUT')

            <!-- Main Card -->
            <div class="card">
                <!-- Header Section -->
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Editar Conversación #{{ $conversation->id }}</h5>
                            <p class="small mb-0 text-muted">Modifica los detalles de la conversación</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('manager.helpdesk.conversations.show', $conversation) }}" class="btn btn-light">
                                <i class="fa-solid fa-xmark me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status Cards -->
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-{{ $conversation->status->color }}-subtle h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-{{ $conversation->status->color }} mb-2">
                                                <i class="fa-solid fa-flag me-1"></i>
                                                Estado
                                            </h6>
                                            <h5 class="mb-1 fw-bold">{{ $conversation->status->name }}</h5>
                                            <small class="text-muted">{{ $conversation->isOpen() ? 'Abierta' : 'Cerrada' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-primary mb-2">
                                                <i class="fa-solid fa-user me-1"></i>
                                                Asignado
                                            </h6>
                                            <h6 class="mb-1 fw-bold">{{ $conversation->assignee ? $conversation->assignee->name : 'Sin asignar' }}</h6>
                                            <small class="text-muted">{{ $conversation->assigned_at ? $conversation->assigned_at->diffForHumans() : 'No asignado' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-info mb-2">
                                                <i class="fa-solid fa-comments me-1"></i>
                                                Mensajes
                                            </h6>
                                            <h5 class="mb-1 fw-bold">{{ $conversation->getMessageCount() }}</h5>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-success mb-2">
                                                <i class="fa-solid fa-clock me-1"></i>
                                                Actualizada
                                            </h6>
                                            <h6 class="mb-1 fw-bold">{{ $conversation->updated_at->diffForHumans() }}</h6>
                                            <small class="text-muted">{{ $conversation->updated_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information (Read-only) -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Cliente</h6>
                        <p class="text-muted mb-0">Información del cliente (no editable)</p>
                    </div>
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                        <img src="{{ $conversation->customer->getAvatarUrl() }}" class="rounded-circle" width="48" height="48" alt="{{ $conversation->customer->name }}">
                        <div>
                            <div class="fw-semibold">{{ $conversation->customer->name }}</div>
                            <small class="text-muted">{{ $conversation->customer->email }}</small>
                            @if($conversation->customer->phone)
                                <br><small class="text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $conversation->customer->phone }}</small>
                            @endif
                        </div>
                        <a href="{{ route('manager.helpdesk.customers.show', $conversation->customer) }}"
                           class="btn btn-sm btn-light ms-auto"
                           target="_blank">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver perfil
                        </a>
                    </div>
                </div>

                <!-- Conversation Details -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Detalles de la conversación</h6>
                        <p class="text-muted mb-0">Información básica de la conversación</p>
                    </div>

                    <div class="row g-3">
                        <!-- Subject -->
                        <div class="col-12">
                            <label for="subject" class="form-label fw-semibold">
                                Asunto <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="subject"
                                   name="subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   placeholder="Ej: Consulta sobre facturación"
                                   value="{{ old('subject', $conversation->subject) }}"
                                   required
                                   autofocus>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label for="status_id" class="form-label fw-semibold">
                                Estado <span class="text-danger">*</span>
                            </label>
                            <select id="status_id"
                                    name="status_id"
                                    class="form-select @error('status_id') is-invalid @enderror"
                                    required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}"
                                            {{ old('status_id', $conversation->status_id) == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Priority -->
                        <div class="col-md-6">
                            <label for="priority" class="form-label fw-semibold">
                                Prioridad <span class="text-danger">*</span>
                            </label>
                            <select id="priority"
                                    name="priority"
                                    class="form-select @error('priority') is-invalid @enderror"
                                    required>
                                <option value="low" {{ old('priority', $conversation->priority) === 'low' ? 'selected' : '' }}>
                                    🟢 Baja
                                </option>
                                <option value="normal" {{ old('priority', $conversation->priority) === 'normal' ? 'selected' : '' }}>
                                    🔵 Normal
                                </option>
                                <option value="high" {{ old('priority', $conversation->priority) === 'high' ? 'selected' : '' }}>
                                    🟡 Alta
                                </option>
                                <option value="urgent" {{ old('priority', $conversation->priority) === 'urgent' ? 'selected' : '' }}>
                                    🔴 Urgente
                                </option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Assignee -->
                        <div class="col-md-6">
                            <label for="assignee_id" class="form-label fw-semibold">
                                Asignar a
                            </label>
                            <select id="assignee_id"
                                    name="assignee_id"
                                    class="form-select select2 @error('assignee_id') is-invalid @enderror">
                                <option value="">— Sin asignar —</option>
                                @foreach(\App\Models\User::where('active', true)->orderBy('firstname')->get() as $user)
                                    <option value="{{ $user->id }}"
                                            {{ old('assignee_id', $conversation->assignee_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('assignee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Agente de soporte responsable de esta conversación
                            </small>
                        </div>

                        <!-- Archive Status -->
                        <div class="col-md-6">
                            <label for="is_archived" class="form-label fw-semibold">
                                Archivo
                            </label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="is_archived"
                                       name="is_archived"
                                       value="1"
                                       {{ old('is_archived', $conversation->is_archived) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_archived">
                                    Archivar esta conversación
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Las conversaciones archivadas se ocultan Por defecto
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="card-footer d-flex justify-content-between align-items-center p-4">
                    <div class="d-flex gap-2">
                        <a href="{{ route('manager.helpdesk.conversations.show', $conversation) }}" class="btn btn-light">
                            <i class="fa-solid fa-arrow-left me-1"></i> Volver
                        </a>

                        @if($conversation->isOpen())
                            <form action="{{ route('manager.helpdesk.conversations.close', $conversation) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa-solid fa-lock me-1"></i> Cerrar conversación
                                </button>
                            </form>
                        @else
                            <form action="{{ route('manager.helpdesk.conversations.reopen', $conversation) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fa-solid fa-lock-open me-1"></i> Reabrir conversación
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="d-flex gap-2">
                        <form action="{{ route('manager.helpdesk.conversations.destroy', $conversation) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta conversación?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-trash me-1"></i> Eliminar
                            </button>
                        </form>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 for assignee dropdown
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: '— Sin asignar —',
            allowClear: true
        });
    });
</script>
@endsection
