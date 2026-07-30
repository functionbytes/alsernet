@extends('layouts.theme')

@section('title', isset($component) ? 'Editar componente: ' . $component->name : 'Nuevo componente')

@section('page_header')
    @include('core::components.card', ['title' => isset($component) ? 'Editar componente' : 'Nuevo componente'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ isset($component) ? route('settings.helpdesk.status.components.update', $component->id) : route('settings.helpdesk.status.components.store') }}"
                      method="POST">
                    @csrf
                    @if(isset($component))
                        @method('PUT')
                    @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ isset($component) ? 'Editar: ' . $component->name : 'Nuevo componente del sistema' }}</h5>
                        <small class="text-muted">{{ isset($component) ? 'Modifica el estado y visibilidad del componente' : 'Agrega un servicio o sistema a la pagina de estado' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion del componente</h6>
                        <p class="text-muted small mb-3">Nombre y descripcion visible en la pagina de estado</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $component->name ?? '') }}"
                                           placeholder="Ej: API de pagos"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea name="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="2"
                                              placeholder="Breve descripcion del servicio">{{ old('description', $component->description ?? '') }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Estado y configuracion</h6>
                        <p class="text-muted small mb-3">Estado operativo actual y visibilidad en la pagina publica</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                        @foreach(\Modules\Helpdesk\Models\StatusComponent::STATUSES as $value => $label)
                                            <option value="{{ $value }}" {{ old('status', $component->status ?? 'operational') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Orden</label>
                                    <input type="number" name="order"
                                           class="form-control @error('order') is-invalid @enderror"
                                           value="{{ old('order', $component->order ?? 0) }}"
                                           min="0">
                                    <small class="form-text text-muted">Menor = aparece primero</small>
                                    @error('order')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Visibilidad</label>
                                    <select name="is_visible" class="form-select">
                                        <option value="1" {{ old('is_visible', $component->is_visible ?? true) ? 'selected' : '' }}>Visible en pagina publica</option>
                                        <option value="0" {{ !old('is_visible', $component->is_visible ?? true) ? 'selected' : '' }}>Oculto</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ isset($component) ? 'Guardar cambios' : 'Crear componente' }}
                        </button>
                        <a href="{{ route('settings.helpdesk.status.components') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estados disponibles</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach(\Modules\Helpdesk\Models\StatusComponent::STATUSES as $key => $label)
                            @php $color = \Modules\Helpdesk\Models\StatusComponent::STATUS_COLORS[$key] ?? 'secondary'; @endphp
                            <li class="mb-2 text-muted small">
                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} me-2">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @if(isset($component))
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Informacion del registro</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 text-muted small">
                                <span class="fw-semibold">Creado:</span> {{ $component->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li class="text-muted small">
                                <span class="fw-semibold">Actualizado:</span> {{ $component->updated_at->format('d/m/Y H:i') }}
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
