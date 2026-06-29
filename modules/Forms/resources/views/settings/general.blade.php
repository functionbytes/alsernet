@extends('layouts.theme')

@section('title', 'Configuración: ' . $form->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-9">
                <form action="{{ route('settings.forms.settings.update', $form) }}" method="POST" id="settingsForm">
                    @csrf
                    @method('PATCH')

                    <div class="card">

                        <div class="card-header p-4 border-bottom">
                            <h5 class="mb-1 fw-bold">Configuración</h5>
                            <p class="small mb-0 text-muted">Configura el comportamiento general y las opciones del formulario</p>
                        </div>

                        {{-- Respuesta al envío --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Respuesta al envío</h6>
                            <p class="text-muted mb-3">Configura qué ve el usuario tras enviar el formulario.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="success_message" class="form-label">Mensaje de éxito</label>
                                    <textarea id="success_message" name="success_message" class="form-control" rows="3"
                                              placeholder="Mensaje tras el envío del formulario">{{ old('success_message', $form->success_message) }}</textarea>
                                    <div class="form-text">Dejar vacío para usar el mensaje por defecto. Alternativa a URL de redirección.</div>
                                    @error('success_message')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="redirect_url" class="form-label">URL de redirección</label>
                                    <input type="url" id="redirect_url" name="redirect_url" class="form-control"
                                           value="{{ old('redirect_url', $form->redirect_url) }}"
                                           placeholder="https://ejemplo.com/gracias">
                                    <div class="form-text">Si se especifica, redirige al usuario a esta URL en lugar de mostrar el mensaje.</div>
                                    @error('redirect_url')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Comportamiento --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Comportamiento</h6>
                            <p class="text-muted mb-3">Límites y controles sobre los envíos del formulario.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="submit_button_text" class="form-label">Texto del botón enviar</label>
                                    <input type="text" id="submit_button_text" name="submit_button_text" class="form-control"
                                           value="{{ old('submit_button_text', $form->submit_button_text ?? 'Enviar') }}"
                                           placeholder="Enviar">
                                </div>

                                <div class="col-md-6">
                                    <label for="max_submissions" class="form-label">Máximo de envíos</label>
                                    <input type="number" id="max_submissions" name="max_submissions" class="form-control"
                                           value="{{ old('max_submissions', $form->max_submissions) }}"
                                           min="1" placeholder="Sin límite">
                                    <div class="form-text">Dejar vacío para no limitar.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="expires_at" class="form-label">Fecha de expiración</label>
                                    <input type="datetime-local" id="expires_at" name="expires_at" class="form-control"
                                           value="{{ old('expires_at', $form->expires_at ? $form->expires_at->format('Y-m-d\TH:i') : '') }}">
                                    <div class="form-text">Dejar vacío para no expirar.</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Opciones --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Opciones</h6>
                            <p class="text-muted mb-3">Características adicionales del comportamiento del formulario.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="allow_multiple">Múltiples envíos por usuario</label>
                                        <select class="form-select" name="allow_multiple" id="allow_multiple">
                                            <option value="1" {{ old('allow_multiple', $form->allow_multiple) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('allow_multiple', $form->allow_multiple) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="is_multi_step">Formulario multi-paso</label>
                                        <select class="form-select" name="is_multi_step" id="is_multi_step">
                                            <option value="1" {{ old('is_multi_step', $form->is_multi_step) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('is_multi_step', $form->is_multi_step) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="floating_label">Labels flotantes (Material)</label>
                                        <select class="form-select" name="floating_label" id="floating_label">
                                            <option value="1" {{ old('floating_label', $form->floating_label) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('floating_label', $form->floating_label) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer p-4">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <div class="col-lg-3">
                @include('forms::settings.partials.tabs', ['active' => 'settings'])

                <div class="card mt-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Estadísticas</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Slug</span>
                            <code class="small">{{ $form->slug }}</code>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Estado</span>
                            @if ($form->is_active)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Campos</span>
                            <span class="fw-semibold small">{{ $form->fields->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Submissions</span>
                            <a href="{{ route('settings.forms.submissions.index', $form) }}" class="fw-semibold small text-decoration-none">
                                {{ $form->submissions()->count() }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
