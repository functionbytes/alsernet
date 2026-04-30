@extends('layouts.theme')

@section('title', 'Nueva campaña')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva campaña'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('manager.helpdesk-campaigns.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva campaña</h5>
                        <small class="text-muted">Crea una nueva campaña de marketing para el chat en vivo</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Informacion basica --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y descripcion visible de la campana</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Promocion de verano 2025"
                                       required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion</label>
                                <textarea name="description" rows="3"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Describe el objetivo de esta campana...">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Configuracion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                        <p class="text-muted small mb-3">Tipo de visualizacion y estado inicial de la campana</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Tipo de campaña <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="popup" {{ old('type') === 'popup' ? 'selected' : '' }}>Pop-up — ventana emergente</option>
                                    <option value="banner" {{ old('type') === 'banner' ? 'selected' : '' }}>Banner — barra superior o inferior</option>
                                    <option value="slide-in" {{ old('type') === 'slide-in' ? 'selected' : '' }}>Slide-in — deslizar desde esquina</option>
                                    <option value="full-screen" {{ old('type') === 'full-screen' ? 'selected' : '' }}>Pantalla completa — overlay</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Borrador — sin publicar</option>
                                    <option value="scheduled" {{ old('status') === 'scheduled' ? 'selected' : '' }}>Programada — publicacion diferida</option>
                                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Activa — visible para visitantes</option>
                                    <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Pausada — temporalmente detenida</option>
                                    <option value="ended" {{ old('status') === 'ended' ? 'selected' : '' }}>Finalizada — campana completada</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Crear campaña</button>
                        <a href="{{ route('manager.helpdesk-campaigns.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las campañas</h6>
                    <p class="card-text text-muted">
                        Las campañas permiten mostrar mensajes personalizados a los visitantes del chat en momentos clave de su navegacion.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres descriptivos que identifiquen el objetivo</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Comienza en estado Borrador para revisar antes de publicar</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Elige el tipo segun donde quieres que aparezca el mensaje</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Configura condiciones para segmentar a quien ve la campana</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
