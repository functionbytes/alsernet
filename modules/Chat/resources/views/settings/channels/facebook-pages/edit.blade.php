@extends('layouts.theme')

@section('title', 'Editar página de Facebook')

@section('content')

    @include('core::components.card', ['title' => 'Editar Facebook'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <form action="{{ route('settings.chat.channels.facebook-pages.update', $facebookPage) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-header p-4 border-bottom border-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">Editar página de Facebook</h5>
                                    <p class="small mb-0 text-muted">Actualiza la configuración de tu canal de Facebook</p>
                                </div>
                                <div>
                                    <span class="badge bg-primary-subtle text-primary">
                                        <i class="fab fa-facebook me-1"></i>Facebook
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Configuración básica -->
                            <div class="mb-4">
                                <h6 class="fw-semibold mb-3">Configuración básica</h6>

                                <div class="mb-3">
                                    <label for="inbox_name" class="control-label col-form-label">
                                        Nombre de bandeja <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('inbox_name') is-invalid @enderror"
                                           id="inbox_name"
                                           name="inbox_name"
                                           value="{{ old('inbox_name', $facebookPage->inbox->name ?? '') }}"
                                           placeholder="Ej: Atención al cliente Facebook"
                                           required>
                                    <small class="form-text text-muted">
                                        Este nombre se mostrará en el panel de conversaciones
                                    </small>
                                    @error('inbox_name')
                                        <span class="field-validation-error">
                                            <i class="fa fa-circle-exclamation"></i> {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Información de la página -->
                            <div class="mb-4">
                                <h6 class="fw-semibold mb-3">Información de la página</h6>

                                <div class="card bg-light-subtle border-0">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start gap-2">
                                                    <i class="fab fa-facebook text-primary mt-1"></i>
                                                    <div>
                                                        <small class="text-muted d-block mb-1">Nombre de página</small>
                                                        <strong class="d-block">{{ $facebookPage->page_name }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start gap-2">
                                                    <i class="fa fa-hashtag text-muted mt-1"></i>
                                                    <div>
                                                        <small class="text-muted d-block mb-1">Page ID</small>
                                                        <code class="bg-white px-2 py-1 rounded border">{{ $facebookPage->page_id }}</code>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <hr class="my-2">
                                                <div class="d-flex align-items-center gap-2 mt-3">
                                                    <i class="fa fa-plug text-success"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Estado de conexión</small>
                                                        <span class="badge bg-success-subtle text-success">
                                                            <i class="fa fa-circle-check me-1"></i>Conectado
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="alert alert-info border-0 mb-0">
                                <div class="d-flex gap-2">
                                    <i class="fa fa-circle-info mt-1"></i>
                                    <div>
                                        <h6 class="mb-1 fw-semibold">Sobre la integración</h6>
                                        <p class="small mb-0">
                                            Esta página está conectada mediante OAuth 2.0. Los mensajes y conversaciones se sincronizan automáticamente.
                                            Si experimentas problemas de conexión, utiliza la opción de reautorización en el panel lateral.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer border-top bg-white">
                            <div class="d-flex gap-2 justify-content-between align-items-center">
                                <a href="{{ route('settings.chat.channels.facebook-pages.index') }}"
                                   class="btn btn-light">
                                    <i class="fa fa-arrow-left me-1"></i> Volver al listado
                                </a>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('settings.chat.channels.facebook-pages.show', $facebookPage) }}"
                                       class="btn btn-secondary">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check me-1"></i> Guardar cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Reautorización -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-warning-subtle border-bottom border-light">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fa fa-triangle-exclamation me-2"></i>Reautorización
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="small mb-3">
                            Si tus credenciales de Facebook son inválidas o han expirado, puedes reautorizar tu cuenta.
                        </p>

                        <form action="{{ route('settings.chat.channels.facebook-pages.reauthorize', $facebookPage) }}"
                              method="POST"
                              onsubmit="return confirm('¿Estás seguro de que deseas reautorizar esta cuenta?');">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fa fa-arrows-rotate me-2"></i> Reautorizar cuenta
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Ayuda -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-info-subtle border-bottom border-light">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fa fa-circle-question me-2"></i>Ayuda
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="small fw-semibold mb-2">¿Qué puedo hacer aquí?</h6>
                            <ul class="small mb-0 ps-3">
                                <li class="mb-2">Cambiar el nombre de la bandeja de entrada</li>
                                <li class="mb-2">Ver información de la página conectada</li>
                                <li>Reautorizar si hay problemas de conexión</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card">
                    <div class="card-header p-3 bg-light-subtle border-bottom border-light">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fa fa-bolt me-2"></i>Acciones rápidas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('settings.chat.channels.facebook-pages.show', $facebookPage) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-eye me-1"></i> Ver detalles completos
                            </a>
                            <a href="{{ route('settings.chat.channels.facebook-pages.index') }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-list me-1"></i> Todos los canales
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
