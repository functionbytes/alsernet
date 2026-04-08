@extends('layouts.theme')

@section('title', 'Configuración: ' . $form->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
                <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                    <span class="text-muted">{{ $form->slug }}</span>
                </div>
            </div>

            {{-- Tabs --}}
            @include('forms::settings.partials.tabs', ['active' => 'settings'])

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Configuración general</h6>

                            <form action="{{ route('settings.forms.settings.update', $form) }}" method="POST" id="settingsForm">
                                @csrf
                                @method('PATCH')

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

                                    <div class="col-12">
                                        <hr class="my-1">
                                        <h6 class="fw-semibold mt-3 mb-3">Opciones</h6>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="allow_multiple" value="0">
                                            <input class="form-check-input" type="checkbox" id="allow_multiple" name="allow_multiple"
                                                   value="1" {{ old('allow_multiple', $form->allow_multiple) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_multiple">
                                                Permitir múltiples envíos por usuario
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_multi_step" value="0">
                                            <input class="form-check-input" type="checkbox" id="is_multi_step" name="is_multi_step"
                                                   value="1" {{ old('is_multi_step', $form->is_multi_step) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_multi_step">
                                                Formulario multi-paso
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="floating_label" value="0">
                                            <input class="form-check-input" type="checkbox" id="floating_label" name="floating_label"
                                                   value="1" {{ old('floating_label', $form->floating_label) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="floating_label">
                                                Labels flotantes (Material)
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12 pt-2 border-top">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Guardar configuración
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
