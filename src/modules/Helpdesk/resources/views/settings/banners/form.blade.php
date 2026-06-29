@extends('layouts.theme')

@section('title', isset($banner) ? 'Editar banner: ' . $banner->title : 'Nuevo banner')

@section('page_header')
    @include('core::components.card', ['title' => isset($banner) ? 'Editar banner' : 'Nuevo banner'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($banner) ? route('settings.helpdesk.banners.update', $banner->id) : route('settings.helpdesk.banners.store') }}"
                      method="POST">
                    @csrf
                    @if(isset($banner))
                        @method('PUT')
                    @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($banner) ? 'Editar: ' . $banner->title : 'Nuevo banner' }}</h5>
                        <small class="text-muted">{{ isset($banner) ? 'Modifica las propiedades del banner' : 'Crea un banner para mostrar mensajes a tus clientes' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Titulo, contenido y tipo del banner</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Titulo <span class="text-danger">*</span></label>
                                    <input type="text" name="title"
                                           class="form-control @error('title') is-invalid @enderror"
                                           value="{{ old('title', $banner->title ?? '') }}"
                                           placeholder="Ej: Mantenimiento programado"
                                           required>
                                    @error('title')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                        <option value="info" {{ old('type', $banner->type ?? '') === 'info' ? 'selected' : '' }}>Info</option>
                                        <option value="success" {{ old('type', $banner->type ?? '') === 'success' ? 'selected' : '' }}>Exito</option>
                                        <option value="warning" {{ old('type', $banner->type ?? '') === 'warning' ? 'selected' : '' }}>Advertencia</option>
                                        <option value="danger" {{ old('type', $banner->type ?? '') === 'danger' ? 'selected' : '' }}>Peligro</option>
                                    </select>
                                    @error('type')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Contenido <span class="text-danger">*</span></label>
                                    <textarea name="body"
                                              class="form-control @error('body') is-invalid @enderror"
                                              rows="4"
                                              placeholder="Escribe el mensaje que veran los clientes"
                                              required>{{ old('body', $banner->body ?? '') }}</textarea>
                                    @error('body')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Boton de accion (opcional)</h6>
                        <p class="text-muted small mb-3">Enlace que se mostrara como boton en el banner</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Texto del boton</label>
                                    <input type="text" name="cta_text"
                                           class="form-control @error('cta_text') is-invalid @enderror"
                                           value="{{ old('cta_text', $banner->cta_text ?? '') }}"
                                           placeholder="Ej: Ver detalles">
                                    @error('cta_text')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">URL del boton</label>
                                    <input type="url" name="cta_url"
                                           class="form-control @error('cta_url') is-invalid @enderror"
                                           value="{{ old('cta_url', $banner->cta_url ?? '') }}"
                                           placeholder="https://...">
                                    @error('cta_url')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Vigencia y comportamiento</h6>
                        <p class="text-muted small mb-3">Fechas de inicio y fin, y si el cliente puede cerrar el banner</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de inicio</label>
                                    <input type="datetime-local" name="starts_at"
                                           class="form-control @error('starts_at') is-invalid @enderror"
                                           value="{{ old('starts_at', isset($banner) && $banner->starts_at ? $banner->starts_at->format('Y-m-d\TH:i') : '') }}">
                                    @error('starts_at')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de fin</label>
                                    <input type="datetime-local" name="ends_at"
                                           class="form-control @error('ends_at') is-invalid @enderror"
                                           value="{{ old('ends_at', isset($banner) && $banner->ends_at ? $banner->ends_at->format('Y-m-d\TH:i') : '') }}">
                                    @error('ends_at')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Cerrable por el cliente</label>
                                    <select name="dismissible" class="form-select">
                                        <option value="1" {{ old('dismissible', $banner->dismissible ?? true) ? 'selected' : '' }}>Si — puede cerrarlo</option>
                                        <option value="0" {{ !old('dismissible', $banner->dismissible ?? true) ? 'selected' : '' }}>No — siempre visible</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                        <option value="1" {{ old('is_active', $banner->is_active ?? true) ? 'selected' : '' }}>Activo — visible para clientes</option>
                                        <option value="0" {{ !old('is_active', $banner->is_active ?? true) ? 'selected' : '' }}>Inactivo — oculto</option>
                                    </select>
                                    @error('is_active')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($banner) ? 'Guardar cambios' : 'Crear banner' }}
                        </button>
                        <a href="{{ route('settings.helpdesk.banners.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los banners</h6>
                    <p class="card-text text-muted">
                        Los banners se muestran en la parte superior del widget de chat para comunicar mensajes importantes a los clientes.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tipos disponibles</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><span class="badge bg-primary-subtle text-primary me-2">Info</span> Mensajes informativos generales</li>
                        <li class="mb-2 text-muted small"><span class="badge bg-success-subtle text-success me-2">Exito</span> Novedades o mejoras</li>
                        <li class="mb-2 text-muted small"><span class="badge bg-warning-subtle text-warning me-2">Advertencia</span> Mantenimientos programados</li>
                        <li class="text-muted small"><span class="badge bg-danger-subtle text-danger me-2">Peligro</span> Incidentes o interrupciones</li>
                    </ul>
                </div>
                @if(isset($banner))
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Informacion del registro</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 text-muted small">
                                <span class="fw-semibold">Creado:</span> {{ $banner->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li class="text-muted small">
                                <span class="fw-semibold">Actualizado:</span> {{ $banner->updated_at->format('d/m/Y H:i') }}
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
