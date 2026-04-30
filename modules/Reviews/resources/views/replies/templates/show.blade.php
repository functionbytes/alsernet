@extends('layouts.theme')

@section('title', 'Detalle de plantilla')

@section('page_header')
    @include('core::components.card', ['title' => 'Detalle de plantilla'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">

                <div class="card">

                    {{-- Información general --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Información general</h6>
                        <p class="text-muted mb-3">Datos principales de la plantilla de respuesta.</p>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre de la plantilla</label>
                                <input type="text" class="form-control" value="{{ $template->name }}" disabled>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Categoría</label>
                                @php
                                    $categoryLabels = [
                                        'positive' => ['label' => 'Positiva', 'color' => 'success'],
                                        'negative' => ['label' => 'Negativa', 'color' => 'danger'],
                                        'neutral'  => ['label' => 'Neutral',  'color' => 'warning'],
                                        'general'  => ['label' => 'General',  'color' => 'info'],
                                    ];
                                    $category = $categoryLabels[$template->category] ?? ['label' => 'General', 'color' => 'secondary'];
                                @endphp
                                <div class="mt-1">
                                    <span class="badge bg-{{ $category['color'] }}">{{ $category['label'] }}</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Estado</label>
                                <div class="mt-1">
                                    @if($template->is_active)
                                        <span class="badge bg-success">Activa</span>
                                    @else
                                        <span class="badge bg-secondary">Inactiva</span>
                                    @endif
                                </div>
                            </div>

                            @if(isset($locations) && $locations->count() > 0 && $template->review_google_location_id)
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Ubicación</label>
                                    <input type="text" class="form-control"
                                           value="{{ $locations->find($template->review_google_location_id)?->name ?? 'Global (todas las ubicaciones)' }}"
                                           disabled>
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Cuerpo de la plantilla --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Cuerpo de la respuesta</h6>
                        <p class="text-muted mb-3">Contenido de la plantilla con las variables definidas.</p>

                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $template->body }}</pre>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Metadatos --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Información adicional</h6>
                        <p class="text-muted mb-3">Datos de creación y uso de la plantilla.</p>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Creado por</label>
                                <input type="text" class="form-control"
                                       value="{{ $template->createdBy?->name ?? 'Sistema' }}"
                                       disabled>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Fecha de creación</label>
                                <input type="text" class="form-control"
                                       value="{{ $template->created_at->format('d/m/Y H:i') }}"
                                       disabled>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Veces utilizada</label>
                                <input type="text" class="form-control"
                                       value="{{ $template->usage_count }}"
                                       disabled>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        @can('update', $template)
                            <a href="{{ route('settings.reviews.templates.edit', $template) }}" class="btn btn-primary w-100 mb-2">
                                Editar plantilla
                            </a>
                        @endcan
                        <a href="{{ route('settings.reviews.templates.index') }}" class="btn btn-secondary w-100 mb-2">
                            Volver al listado
                        </a>
                        @can('delete', $template)
                            <button type="button" class="btn btn-secondary w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#delete-modal"
                                    data-url="{{ route('settings.reviews.templates.destroy', $template) }}"
                                    data-title="Eliminar plantilla: {{ e($template->name) }}">
                                Eliminar plantilla
                            </button>
                        @endcan
                    </div>

                </div>

            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Variables disponibles</h6>
                        <p class="text-muted mb-3">Variables que pueden usarse para personalizar las respuestas.</p>
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

                    <hr class="my-0">

                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Ejemplo de uso</h6>
                        <div class="text-muted bg-light p-3 rounded">
                            <p class="mb-0">
                                Hola {reviewer_name}, muchas gracias por tu reseña de {star_rating} estrellas en {location_name}.
                                Tu opinión es muy importante para nosotros y nos ayuda a mejorar cada día.
                            </p>
                        </div>
                    </div>

                    @if($template->usage_count > 0)
                        <hr class="my-0">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Estadisticas de uso</h6>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-muted">Veces utilizada</span>
                                <strong>{{ $template->usage_count }}</strong>
                            </div>
                        </div>
                    @endif

                    @if($template->updated_at->ne($template->created_at))
                        <hr class="my-0">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Ultima actualizacion</h6>
                            <p class="text-muted mb-0">
                                {{ $template->updated_at->diffForHumans() }}<br>
                                <small>{{ $template->updated_at->format('d/m/Y H:i:s') }}</small>
                            </p>
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>

    @can('delete', $template)
        @include('core::components.delete')
    @endcan

@endsection

@push('scripts')
<script>
$(function () {
    $('#delete-modal').on('show.bs.modal', function (e) {
        var $trigger = $(e.relatedTarget);
        $(this).find('.modal-title').text($trigger.data('title'));
        $('#delete-form').attr('action', $trigger.data('url'));
    });

    @if (session('success'))
        toastr.success(@json(session('success')), 'Éxito');
    @endif
    @if (session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif
});
</script>
@endpush
