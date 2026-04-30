@extends('layouts.theme')

@section('title', $integration->app_id)

@section('content')

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <!-- Integration Details Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $integration->app_id }}</h5>
                            @if($integration->status === 1)
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-info-subtle text-info">Inactiva</span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('settings.chat.integrations.edit', $integration) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Información básica</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">ID de aplicación</h6>
                            <p class="mb-0"><strong>{{ $integration->app_id }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Tipo de hook</h6>
                            <p class="mb-0">
                                @if($integration->hook_type == 0)
                                    <span class="badge bg-info-subtle text-info">Entrante</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Saliente</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Bandeja</h6>
                            <p class="mb-0">{{ $integration->inbox->name ?? 'Nivel de cuenta' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Estado</h6>
                            <p class="mb-0">
                                @if($integration->status === 1)
                                    <span class="badge bg-success-subtle text-success">Activa</span>
                                @else
                                    <span class="badge bg-info-subtle text-info">Inactiva</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($integration->reference_id || $integration->access_token)
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Conexión</h6>

                        <div class="row g-3 mb-4">
                            @if($integration->reference_id)
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">ID de referencia</h6>
                                    <p class="mb-0"><code>{{ $integration->reference_id }}</code></p>
                                </div>
                            @endif
                            @if($integration->access_token)
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Token de acceso</h6>
                                    <p class="mb-0"><code>{{ Str::mask($integration->access_token, '*', 10) }}</code></p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($integration->settings)
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Configuración adicional</h6>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <pre class="mb-0 small">{{ json_encode($integration->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Statistics Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Creado</h6>
                        <p class="mb-0">{{ $integration->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $integration->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Última actualización</h6>
                        <p class="mb-0">{{ $integration->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $integration->updated_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Acciones</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.chat.integrations.edit', $integration) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar integración
                        </a>
                        <a href="{{ route('settings.chat.integrations.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                        <hr>
                        <button type="button"
                            class="btn btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                            data-url="{{ route('settings.chat.integrations.destroy', $integration) }}"
                            data-title="Eliminar integración: {{ $integration->app_id }}">
                            <i class="fas fa-trash"></i> Eliminar integración
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Delete modal functionality
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const deleteUrl = $(this).data('url');
                const deleteTitle = $(this).data('title');

                $('#delete-modal .modal-title').text(deleteTitle);
                $('#delete-form').attr('action', deleteUrl);

                const deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
                deleteModal.show();
            });

            @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
            @endif

            @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
            @endif
        });
    </script>
@endpush
