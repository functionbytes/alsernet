@extends('layouts.theme')

@section('title', 'Editar grupo de suscriptores')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .form-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        padding: 2rem;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    .info-box {
        background-color: #e7f3ff;
        border-left: 4px solid #0d6efd;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 1.5rem;
    }
    .sync-stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .sync-stats-card h6 {
        margin: 0;
        opacity: 0.9;
        font-size: 0.875rem;
    }
    .sync-stats-card .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0.5rem 0;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2">
                        <i class="fas fa-edit"></i> Editar grupo de suscriptores
                    </h1>
                    <p class="text-muted">Modifica la información del grupo "{{ $group->name }}"</p>
                </div>
                <a href="{{ route('mailrelay.settings.groups.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
            </div>

            <!-- Sync Stats Card -->
            @if($group->mailrelay_group_id)
            <div class="sync-stats-card">
                <div class="row">
                    <div class="col-md-4">
                        <h6><i class="fas fa-cloud"></i> Estado de sincronización</h6>
                        <p class="stat-value">
                            @if($group->last_synced_at)
                                Sincronizado
                            @else
                                Pendiente
                            @endif
                        </p>
                        <small>ID Mailrelay: {{ $group->mailrelay_group_id }}</small>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-clock"></i> Última sincronización</h6>
                        <p class="stat-value">
                            @if($group->last_synced_at)
                                {{ $group->last_synced_at->diffForHumans() }}
                            @else
                                Nunca
                            @endif
                        </p>
                        <small>
                            @if($group->last_synced_at)
                                {{ $group->last_synced_at->format('d/m/Y H:i') }}
                            @endif
                        </small>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-users"></i> Suscriptores</h6>
                        <p class="stat-value">{{ $group->subscribers_count ?? 0 }}</p>
                        <form action="{{ route('mailrelay.settings.groups.sync', $group) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light mt-2">
                                <i class="fas fa-sync"></i> Sincronizar ahora
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> Los cambios se sincronizarán automáticamente con Mailrelay
                si la sincronización automática está activada.
            </div>

            <!-- Error Messages -->
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6 class="alert-heading">
                    <i class="fas fa-exclamation-triangle"></i> Por favor corrige los siguientes errores:
                </h6>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Form Card -->
            <div class="form-card">
                <form action="{{ route('mailrelay.settings.groups.update', $group) }}" method="POST" id="groupForm">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Información básica
                    </h6>

                    <div class="mb-3">
                        <label for="name" class="form-label required-field">Nombre del grupo</label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name', $group->name) }}"
                            required
                            placeholder="Ej: Clientes VIP, Newsletter semanal..."
                        >
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Nombre descriptivo para identificar el grupo
                        </small>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea
                            class="form-control @error('description') is-invalid @enderror"
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Describe el propósito de este grupo..."
                        >{{ old('description', $group->description) }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Información adicional sobre el grupo (opcional)
                        </small>
                    </div>

                    <!-- Mailrelay Integration -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">
                        <i class="fas fa-cloud me-2"></i>Integración con Mailrelay
                    </h6>

                    <div class="mb-3">
                        <label for="mailrelay_group_id" class="form-label">ID de grupo en Mailrelay</label>
                        <input
                            type="number"
                            class="form-control @error('mailrelay_group_id') is-invalid @enderror"
                            id="mailrelay_group_id"
                            name="mailrelay_group_id"
                            value="{{ old('mailrelay_group_id', $group->mailrelay_group_id) }}"
                            placeholder="Ej: 12345"
                            min="1"
                        >
                        @error('mailrelay_group_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            <i class="fas fa-lightbulb text-warning"></i>
                            Dejar vacío para crear un nuevo grupo en Mailrelay automáticamente
                        </small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="auto_sync"
                                name="auto_sync"
                                value="1"
                                {{ old('auto_sync', $group->auto_sync) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="auto_sync">
                                <strong>Sincronización automática</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted ms-4">
                            Sincronizar automáticamente los cambios con Mailrelay
                        </small>
                    </div>

                    <!-- Status -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">
                        <i class="fas fa-toggle-on me-2"></i>Estado
                    </h6>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="active"
                                name="active"
                                value="1"
                                {{ old('active', $group->active) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="active">
                                <strong>Grupo activo</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted ms-4">
                            Los grupos inactivos no recibirán campañas de email
                        </small>
                    </div>

                    <!-- Metadata Section -->
                    @if($group->created_at)
                    <div class="alert alert-light border mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-plus"></i>
                                    <strong>Creado:</strong> {{ $group->created_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-edit"></i>
                                    <strong>Última modificación:</strong> {{ $group->updated_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <a href="{{ route('mailrelay.settings.groups.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </a>
                        <div>
                            @if($group->mailrelay_group_id)
                            <button type="button"
                                    class="btn btn-info me-2"
                                    onclick="document.getElementById('sync-form').submit()">
                                <i class="fas fa-sync"></i> Sincronizar ahora
                            </button>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Actualizar grupo
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Hidden sync form -->
                @if($group->mailrelay_group_id)
                <form id="sync-form"
                      action="{{ route('mailrelay.settings.groups.sync', $group) }}"
                      method="POST"
                      style="display: none;">
                    @csrf
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
