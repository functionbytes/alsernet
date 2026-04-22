@extends('layouts.theme')

@section('title', 'Crear grupo de suscriptores')

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
                        <i class="fas fa-users-plus"></i> Crear grupo de suscriptores
                    </h1>
                    <p class="text-muted">Añade un nuevo grupo para organizar tus suscriptores</p>
                </div>
                <a href="{{ route('mailrelay.settings.groups.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> Los suscriptores se sincronizarán automáticamente con Mailrelay
                si activas la sincronización automática.
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

            <!-- Form Card -->
            <div class="form-card">
                <form action="{{ route('mailrelay.settings.groups.store') }}" method="POST" id="groupForm">
                    @csrf

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
                            value="{{ old('name') }}"
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
                        >{{ old('description') }}</textarea>
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
                            value="{{ old('mailrelay_group_id') }}"
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
                        <label class="form-label" for="auto_sync">Sincronización automática</label>
                        <select class="form-select" name="auto_sync" id="auto_sync">
                            <option value="1" {{ old('auto_sync', 1) == 1 ? 'selected' : '' }}>Activado</option>
                            <option value="0" {{ old('auto_sync', 1) == 0 ? 'selected' : '' }}>Desactivado</option>
                        </select>
                        <small class="form-text text-muted">Sincronizar automáticamente los cambios con Mailrelay</small>
                    </div>

                    <!-- Status -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">
                        <i class="fas fa-toggle-on me-2"></i>Estado
                    </h6>

                    <div class="mb-4">
                        <label class="form-label" for="active">Estado del grupo</label>
                        <select class="form-select" name="active" id="active">
                            <option value="1" {{ old('active', 1) == 1 ? 'selected' : '' }}>Activado</option>
                            <option value="0" {{ old('active', 1) == 0 ? 'selected' : '' }}>Desactivado</option>
                        </select>
                        <small class="form-text text-muted">Los grupos inactivos no recibirán campañas de email</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <a href="{{ route('mailrelay.settings.groups.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Guardar grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
