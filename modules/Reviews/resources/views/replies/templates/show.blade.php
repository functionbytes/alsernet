@extends('layouts.theme')

@section('title', 'Detalles de plantilla')

@section('content')
    <div class="row">
        <!-- Panel principal (izquierda) -->
        <div class="col-lg-8">
            <div class="card w-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-2">{{ $template->name }}</h5>
                            <p class="card-subtitle mb-0">
                                Detalles de la plantilla de respuesta
                            </p>
                        </div>
                        <div>
                            @if($template->is_active)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Categoría -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label fw-bold">Categoría</label>
                                <div class="mt-1">
                                    @php
                                        $categoryLabels = [
                                            'positive' => ['label' => 'Positiva', 'color' => 'success'],
                                            'negative' => ['label' => 'Negativa', 'color' => 'danger'],
                                            'neutral' => ['label' => 'Neutral', 'color' => 'warning'],
                                            'general' => ['label' => 'General', 'color' => 'info'],
                                        ];
                                        $category = $categoryLabels[$template->category] ?? ['label' => 'General', 'color' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $category['color'] }}">{{ $category['label'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Veces utilizada -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label fw-bold">Veces utilizada</label>
                                <div class="mt-1">
                                    <span class="badge bg-primary">{{ $template->usage_count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Cuerpo de la plantilla -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label fw-bold">Cuerpo de la respuesta</label>
                                <div class="mt-2 p-3 bg-light rounded">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $template->body }}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Creado por -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label fw-bold">Creado por</label>
                                <div class="mt-1">
                                    @if($template->createdBy)
                                        <span class="text-muted">{{ $template->createdBy->name }}</span>
                                    @else
                                        <span class="text-muted">Sistema</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Fecha de creación -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label fw-bold">Fecha de creación</label>
                                <div class="mt-1">
                                    <span class="text-muted">{{ $template->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    @can('update', $template)
                        <a href="{{ route('settings.reviews.templates.edit', $template) }}" class="btn btn-info px-4 waves-effect waves-light">
                            <i class="fas fa-edit me-1"></i>
                            Editar
                        </a>
                    @endcan
                    <a href="{{ route('settings.reviews.templates.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver al listado
                    </a>
                    @can('delete', $template)
                        <button type="button" class="btn btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-1"></i>
                            Eliminar
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Panel informativo (derecha) -->
        <div class="col-lg-4">
            <!-- Variables disponibles -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Variables disponibles
                    </h6>
                    <p class="card-text text-muted mb-3">
                        Esta plantilla puede utilizar las siguientes variables para personalizar las respuestas.
                    </p>
                    <div class="small">
                        <div class="mb-2">
                            <code class="bg-light px-2 py-1 rounded">{reviewer_name}</code>
                            <span class="text-muted d-block ps-2">Nombre del reviewer</span>
                        </div>
                        <div class="mb-2">
                            <code class="bg-light px-2 py-1 rounded">{location_name}</code>
                            <span class="text-muted d-block ps-2">Nombre del negocio/ubicación</span>
                        </div>
                        <div class="mb-2">
                            <code class="bg-light px-2 py-1 rounded">{star_rating}</code>
                            <span class="text-muted d-block ps-2">Calificación en estrellas</span>
                        </div>
                        <div class="mb-2">
                            <code class="bg-light px-2 py-1 rounded">{comment_summary}</code>
                            <span class="text-muted d-block ps-2">Resumen del comentario</span>
                        </div>
                        <div class="mb-0">
                            <code class="bg-light px-2 py-1 rounded">{date}</code>
                            <span class="text-muted d-block ps-2">Fecha de la reseña</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de uso -->
            @if($template->usage_count > 0)
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            Estadísticas de uso
                        </h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-chart-line text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <p class="mb-0 text-muted">Veces utilizada</p>
                                <h4 class="mb-0">{{ $template->usage_count }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Última actualización -->
            @if($template->updated_at->ne($template->created_at))
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            Última actualización
                        </h6>
                        <p class="text-muted mb-0">
                            {{ $template->updated_at->diffForHumans() }}
                            <br>
                            <small>{{ $template->updated_at->format('d/m/Y H:i:s') }}</small>
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    @can('delete', $template)
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de que desea eliminar esta plantilla?</p>
                        <p class="text-muted mb-0">
                            <strong>{{ $template->name }}</strong>
                        </p>
                        @if($template->usage_count > 0)
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Esta plantilla ha sido utilizada {{ $template->usage_count }} {{ $template->usage_count === 1 ? 'vez' : 'veces' }}
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('settings.reviews.templates.destroy', $template) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection
