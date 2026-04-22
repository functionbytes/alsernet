@extends('layouts.theme')

@section('title', 'Editar etiqueta: ' . $tag->name)

@section('content')

    @include('core::components.card', ['title' => 'Editar etiqueta'])

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="tagForm" action="{{ route('manager.helpdesk.settings.tickets.tags.update', $tag) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $tag->name }}</h5>
                        <small class="text-muted">Modifica las propiedades de la etiqueta</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre, slug y descripcion visible de la etiqueta</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $tag->name) }}"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           value="{{ old('slug', $tag->slug) }}">
                                    <small class="form-text text-muted">Se genera automaticamente desde el nombre</small>
                                    @error('slug')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea name="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="3">{{ old('description', $tag->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Apariencia</h6>
                        <p class="text-muted small mb-3">Color identificador de la etiqueta en conversaciones y listados</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Color</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" name="color" id="colorPicker"
                                               class="form-control form-control-color @error('color') is-invalid @enderror"
                                               value="{{ old('color', $tag->color ?? '#90bb13') }}">
                                        <input type="text" id="colorHex" class="form-control"
                                               value="{{ old('color', $tag->color ?? '#90bb13') }}" readonly>
                                    </div>
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Colores sugeridos</label>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @foreach(['#90bb13','#13C672','#FA896B','#FEC90F','#539BFF','#8E44AD','#E74C3C','#95A5A6'] as $c)
                                            <button type="button" class="btn btn-sm color-preset rounded-circle border-0"
                                                    data-color="{{ $c }}"
                                                    title="{{ $c }}"
                                                    style="background-color:{{ $c }};width:32px;height:32px;"></button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                        <p class="text-muted small mb-3">Disponibilidad de la etiqueta para asignacion en tickets</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Estado</label>
                                    <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
                                        <option value="1" {{ old('is_active', $tag->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Activa — disponible para asignar</option>
                                        <option value="0" {{ old('is_active', $tag->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactiva — no disponible</option>
                                    </select>
                                    @error('is_active')
                                        <div class="field-validation-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.tickets.tags.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las etiquetas</h6>
                    <p class="card-text text-muted">
                        Las etiquetas permiten clasificar los tickets para facilitar su busqueda, filtrado y organizacion por el equipo de soporte.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check text-success me-2"></i> Usa nombres cortos y descriptivos</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check text-success me-2"></i> Asigna un color distinto por etiqueta</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check text-success me-2"></i> Evita duplicados revisando las existentes</li>
                        <li class="text-muted small"><i class="fas fa-check text-success me-2"></i> El slug se genera automaticamente desde el nombre</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion del registro</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small">
                            <span class="fw-semibold">Creada:</span> {{ $tag->created_at->format('d/m/Y H:i') }}
                        </li>
                        <li class="text-muted small">
                            <span class="fw-semibold">Actualizada:</span> {{ $tag->updated_at->format('d/m/Y H:i') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#colorPicker').on('input', function () {
        $('#colorHex').val($(this).val());
    });

    $(document).on('click', '.color-preset', function () {
        const color = $(this).data('color');
        $('#colorPicker').val(color);
        $('#colorHex').val(color);
    });
});
</script>
@endpush
