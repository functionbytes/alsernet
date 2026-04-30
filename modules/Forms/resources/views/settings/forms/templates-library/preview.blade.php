@extends('layouts.theme')

@section('title', 'Vista previa: ' . ($template['name'] ?? 'Plantilla'))

@section('page_header')
    @include('core::components.card', ['title' => 'Vista previa de plantilla'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: preview del formulario --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-0 fw-bold">Vista previa</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($template['fields'] ?? [] as $field)
                            @php
                                $type  = $field['type'] ?? 'text';
                                $label = $field['label'] ?? $field['name'] ?? '';
                                $width = ($field['width'] ?? 'full') === 'half' ? 'col-md-6' : 'col-12';
                            @endphp

                            @if(in_array($type, ['section_header', 'divider']))
                                <div class="col-12"><hr></div>
                            @elseif($type === 'html_block')
                                <div class="col-12">
                                    <div class="alert alert-light border small py-2">{!! $field['content'] ?? '' !!}</div>
                                </div>
                            @else
                                <div class="{{ $width }}">
                                    <label class="form-label fw-semibold small">
                                        {{ $label }}
                                        @if(!empty($field['is_required']))<span class="text-danger">*</span>@endif
                                    </label>

                                    @if($type === 'textarea')
                                        <textarea class="form-control" rows="3"
                                                  placeholder="{{ $field['placeholder'] ?? '' }}" disabled></textarea>
                                    @elseif($type === 'select')
                                        <select class="form-select" disabled>
                                            <option>Selecciona una opción</option>
                                            @foreach($field['options'] ?? [] as $opt)
                                                <option>{{ is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($type === 'checkbox')
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" disabled>
                                            <label class="form-check-label small text-muted">{{ $field['placeholder'] ?? $label }}</label>
                                        </div>
                                    @elseif($type === 'consent')
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" disabled>
                                            <label class="form-check-label small text-muted">
                                                {!! $field['consent_text'] ?? $label !!}
                                            </label>
                                        </div>
                                    @elseif($type === 'radio')
                                        @foreach($field['options'] ?? [] as $opt)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" disabled>
                                                <label class="form-check-label small">{{ is_array($opt) ? ($opt['label'] ?? '') : $opt }}</label>
                                            </div>
                                        @endforeach
                                    @else
                                        <input type="{{ $type }}" class="form-control"
                                               placeholder="{{ $field['placeholder'] ?? '' }}" disabled>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button class="btn btn-primary" disabled>Enviar formulario</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna derecha: sidebar --}}
        <div class="col-lg-4">

            {{-- Detalle plantilla --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Detalle plantilla</h6>
                    <p class="small mb-0 text-muted">Información de la plantilla</p>
                </div>
                <div class="card-body">

                    <p class="text-uppercase small fw-bold text-muted mb-1">Nombre</p>
                    <p class="mb-3">{{ $template['name'] }}</p>

                    @if(!empty($template['category']))
                        <p class="text-uppercase small fw-bold text-muted mb-1">Categoría</p>
                        <p class="mb-3">
                            <span class="badge bg-secondary-subtle text-secondary">{{ $template['category'] }}</span>
                        </p>
                    @endif

                    <p class="text-uppercase small fw-bold text-muted mb-1">Campos</p>
                    <p class="mb-3">{{ count($template['fields'] ?? []) }}</p>

                    <p class="text-uppercase small fw-bold text-muted mb-1">Requeridos</p>
                    <p class="mb-3">{{ collect($template['fields'] ?? [])->where('is_required', true)->count() }}</p>

                    @if(!empty($template['description']))
                        <p class="text-uppercase small fw-bold text-muted mb-1">Descripción</p>
                        <p class="mb-0 small text-muted">{{ $template['description'] }}</p>
                    @endif

                </div>
            </div>

            {{-- Acciones rápidas --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Acciones rápidas</h6>
                    <p class="small mb-0 text-muted">Acciones disponibles</p>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#useTemplateModal">
                        <i class="fas fa-plus me-1"></i> Usar esta plantilla
                    </button>
                    <a href="{{ route('settings.forms.templates-library.index') }}" class="btn btn-black w-100">
                        Volver al listado
                    </a>
                </div>
            </div>

            {{-- Campos del formulario --}}
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Campos del formulario</h6>
                    <p class="small mb-0 text-muted">Campos incluidos en la plantilla</p>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($template['fields'] ?? [] as $i => $field)
                        <div class="list-group-item d-flex align-items-center gap-2 py-2">
                            <span class="text-muted fw-bold" style="min-width:20px;">{{ $i + 1 }}</span>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $field['label'] ?? $field['name'] ?? '—' }}</div>
                                <div class="d-flex gap-1 mt-1">
                                    <span class="badge bg-light text-dark border small">{{ $field['type'] ?? 'text' }}</span>
                                    @if(!empty($field['is_required']))
                                        <span class="badge bg-danger-subtle text-danger small">Requerido</span>
                                    @endif
                                    @if(!empty($field['width']))
                                        <span class="badge bg-light text-muted">{{ $field['width'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

    </div>

    {{-- Modal usar plantilla --}}
    <div class="modal fade" id="useTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Usar plantilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Se creará un nuevo formulario basado en:</p>
                    <p class="fw-bold">{{ $template['name'] }}</p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        El formulario se creará con {{ count($template['fields'] ?? []) }} campo(s). Podrás editarlo libremente después.
                    </p>
                </div>
                <form method="POST" action="{{ route('settings.forms.templates-library.use', $templateKey) }}">
                    @csrf
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-plus me-1"></i> Crear formulario
                        </button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
