@extends('layouts.theme')

@section('title', isset($incident) ? 'Editar incidente: ' . $incident->title : 'Nuevo incidente')

@section('page_header')
    @include('core::components.card', ['title' => isset($incident) ? 'Editar incidente' : 'Nuevo incidente'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($incident) ? route('settings.helpdesk.status.incidents.update', $incident->id) : route('settings.helpdesk.status.incidents.store') }}"
                      method="POST">
                    @csrf
                    @if(isset($incident))
                        @method('PUT')
                    @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($incident) ? 'Editar: ' . $incident->title : 'Nuevo incidente' }}</h5>
                        <small class="text-muted">{{ isset($incident) ? 'Actualiza el estado y detalle del incidente' : 'Registra un incidente en la pagina de estado' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion del incidente</h6>
                        <p class="text-muted small mb-3">Titulo y descripcion visible para los usuarios</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Titulo <span class="text-danger">*</span></label>
                                <input type="text" name="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $incident->title ?? '') }}"
                                       placeholder="Ej: Degradacion del servicio de API"
                                       required>
                                @error('title')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion <span class="text-danger">*</span></label>
                                <textarea name="body"
                                          class="form-control @error('body') is-invalid @enderror"
                                          rows="4"
                                          placeholder="Describe el incidente, impacto y acciones tomadas..."
                                          required>{{ old('body', $incident->body ?? '') }}</textarea>
                                @error('body')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Estado y severidad</h6>
                        <p class="text-muted small mb-3">Clasifica el impacto y estado actual del incidente</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Severidad <span class="text-danger">*</span></label>
                                <select name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                                    <option value="">Seleccionar severidad...</option>
                                    <option value="minor" {{ old('severity', $incident->severity ?? '') === 'minor' ? 'selected' : '' }}>Menor</option>
                                    <option value="major" {{ old('severity', $incident->severity ?? '') === 'major' ? 'selected' : '' }}>Mayor</option>
                                    <option value="critical" {{ old('severity', $incident->severity ?? '') === 'critical' ? 'selected' : '' }}>Critico</option>
                                </select>
                                @error('severity')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="">Seleccionar estado...</option>
                                    <option value="investigating" {{ old('status', $incident->status ?? '') === 'investigating' ? 'selected' : '' }}>Investigando</option>
                                    <option value="identified" {{ old('status', $incident->status ?? '') === 'identified' ? 'selected' : '' }}>Identificado</option>
                                    <option value="monitoring" {{ old('status', $incident->status ?? '') === 'monitoring' ? 'selected' : '' }}>Monitoreando</option>
                                    <option value="resolved" {{ old('status', $incident->status ?? '') === 'resolved' ? 'selected' : '' }}>Resuelto</option>
                                </select>
                                @error('status')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        @if($components->count() > 0)
                            <h6 class="fw-semibold mb-1 border-bottom pb-2">Componentes afectados</h6>
                            <p class="text-muted small mb-3">Selecciona los servicios impactados por este incidente</p>
                            <div class="row g-2 mb-3">
                                @foreach($components as $component)
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="affected_components[]"
                                                   value="{{ $component->id }}"
                                                   id="comp_{{ $component->id }}"
                                                   {{ in_array($component->id, old('affected_components', $incident->affected_components ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="comp_{{ $component->id }}">
                                                {{ $component->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($incident) ? 'Guardar cambios' : 'Crear incidente' }}
                        </button>
                        <a href="{{ route('settings.helpdesk.status.incidents') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Niveles de severidad</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><span class="badge bg-warning-subtle text-warning me-2">Menor</span> Impacto limitado, sin interrupcion total</li>
                        <li class="mb-2 text-muted small"><span class="badge bg-danger-subtle text-danger me-2">Mayor</span> Servicio degradado o parcialmente caido</li>
                        <li class="mb-2 text-muted small"><span class="badge bg-dark-subtle text-dark me-2">Critico</span> Servicio completamente inaccesible</li>
                    </ul>
                </div>
                @if(isset($incident))
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Informacion del registro</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 text-muted small">
                                <span class="fw-semibold">Iniciado:</span> {{ $incident->started_at?->format('d/m/Y H:i') ?? '—' }}
                            </li>
                            <li class="text-muted small">
                                <span class="fw-semibold">Resuelto:</span> {{ $incident->resolved_at?->format('d/m/Y H:i') ?? 'Pendiente' }}
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
