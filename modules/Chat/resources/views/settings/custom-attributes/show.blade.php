@extends('layouts.theme')

@section('title', $customAttribute->attribute_display_name)

@section('content')

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <!-- Attribute Details Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $customAttribute->attribute_display_name }}</h5>
                            @if($customAttribute->attribute_model === 0)
                                <span class="badge bg-info-subtle text-info">Clientes</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Conversaciones</span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('settings.chat.custom-attributes.edit', $customAttribute) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Información básica</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Nombre para mostrar</h6>
                            <p class="mb-0">{{ $customAttribute->attribute_display_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Clave</h6>
                            <p class="mb-0"><code>{{ $customAttribute->attribute_key }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Descripción</h6>
                            <p class="mb-0">{{ $customAttribute->attribute_description ?? 'Sin descripción' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Modelo</h6>
                            <p class="mb-0">
                                @if($customAttribute->attribute_model === 0)
                                    <span class="badge bg-info-subtle text-info">Clientes</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Conversaciones</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Tipo y validación</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Tipo de visualización</h6>
                            @php
                                $types = ['Texto', 'Número', 'Moneda', 'Porcentaje', 'Enlace', 'Fecha', 'Lista', 'Checkbox'];
                            @endphp
                            <p class="mb-0">
                                <span class="badge bg-secondary">{{ $types[$customAttribute->attribute_display_type] ?? 'Desconocido' }}</span>
                            </p>
                        </div>
                        @if($customAttribute->regex_pattern)
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Patrón de validación</h6>
                                <p class="mb-0"><code>{{ $customAttribute->regex_pattern }}</code></p>
                            </div>
                        @endif
                        @if($customAttribute->regex_cue)
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Pista de validación</h6>
                                <p class="mb-0">{{ $customAttribute->regex_cue }}</p>
                            </div>
                        @endif
                    </div>

                    @if($customAttribute->attribute_display_type === 6 && !empty($customAttribute->attribute_values))
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Valores de lista ({{ count($customAttribute->attribute_values) }})</h6>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach($customAttribute->attribute_values as $value)
                                <span class="badge bg-light text-dark border">{{ $value }}</span>
                            @endforeach
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
                        <p class="mb-0">{{ $customAttribute->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $customAttribute->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Última actualización</h6>
                        <p class="mb-0">{{ $customAttribute->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $customAttribute->updated_at->diffForHumans() }}</small>
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
                        <a href="{{ route('settings.chat.custom-attributes.edit', $customAttribute) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar atributo
                        </a>
                        <a href="{{ route('settings.chat.custom-attributes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                        <hr>
                        <button type="button"
                            class="btn btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                            data-url="{{ route('settings.chat.custom-attributes.destroy', $customAttribute) }}"
                            data-title="Eliminar atributo: {{ $customAttribute->attribute_display_name }}">
                            <i class="fas fa-trash"></i> Eliminar atributo
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
