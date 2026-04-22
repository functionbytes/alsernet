@extends('layouts.theme')

@section('title', 'Editar categoria: ' . $category->name)

@section('content')

    @include('core::components.card', ['title' => 'Editar categoria'])

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="categoryForm" action="{{ route('manager.helpdesk.settings.ticket-categories.update', $category) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $category->name }}</h5>
                        <small class="text-muted">Modifica las propiedades de la categoria</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre, slug y descripcion visible de la categoria</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $category->name) }}"
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
                                           value="{{ old('slug', $category->slug) }}">
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
                                              rows="3">{{ old('description', $category->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Apariencia</h6>
                        <p class="text-muted small mb-3">Icono y color que identifican visualmente la categoria en listados</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Icono</label>
                                    @php
                                        $currentIcon = $category->icon;
                                        if ($currentIcon && str_starts_with($currentIcon, 'ti ')) {
                                            $currentIcon = 'fas fa-tag';
                                        }
                                        $currentIcon = $currentIcon ?: 'fas fa-tag';
                                    @endphp
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i id="iconPreview" class="{{ old('icon', $currentIcon) }}"></i>
                                        </span>
                                        <input type="text" name="icon" id="iconInput"
                                               class="form-control @error('icon') is-invalid @enderror"
                                               value="{{ old('icon', $currentIcon) }}"
                                               placeholder="fas fa-tag">
                                    </div>
                                    <small class="form-text text-muted">Clase Font Awesome 6. Ej: fas fa-headset, fas fa-bug</small>
                                    @error('icon')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Color</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" name="color" id="colorPicker"
                                               class="form-control form-control-color @error('color') is-invalid @enderror"
                                               value="{{ old('color', $category->color ?? '#90bb13') }}">
                                        <input type="text" id="colorHex" class="form-control"
                                               value="{{ old('color', $category->color ?? '#90bb13') }}" readonly>
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
                        <p class="text-muted small mb-3">Politica SLA por defecto y disponibilidad de la categoria</p>
                        <div class="row g-3">

                            @if(isset($slaPolicies) && $slaPolicies->count())
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Politica SLA por defecto</label>
                                        <select name="default_sla_policy_id"
                                                class="form-select select2 @error('default_sla_policy_id') is-invalid @enderror">
                                            <option value="">Sin politica SLA</option>
                                            @foreach($slaPolicies as $sla)
                                                <option value="{{ $sla->id }}"
                                                        {{ old('default_sla_policy_id', $category->default_sla_policy_id) == $sla->id ? 'selected' : '' }}>
                                                    {{ $sla->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('default_sla_policy_id')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="active" class="form-label">Estado</label>
                                    <select class="form-select @error('active') is-invalid @enderror" id="active" name="active" required>
                                        <option value="1" {{ old('active', $category->active ? 1 : 0) == 1 ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('active', $category->active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                    <small class="form-text text-muted">Las inactivas no estan disponibles para nuevos tickets</small>
                                    @error('active')
                                        <div class="field-validation-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las categorias</h6>
                    <p class="card-text text-muted">
                        Las categorias permiten clasificar los tickets para facilitar su gestion y enrutamiento hacia el equipo correcto.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion del registro</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small">
                            <span class="fw-semibold">Creada:</span> {{ $category->created_at->format('d/m/Y H:i') }}
                        </li>
                        <li class="text-muted small">
                            <span class="fw-semibold">Actualizada:</span> {{ $category->updated_at->format('d/m/Y H:i') }}
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
    // Icon preview
    $('#iconInput').on('input', function () {
        $('#iconPreview').attr('class', $(this).val() || 'fas fa-tag');
    });

    // Color picker sync
    $('#colorPicker').on('input', function () {
        $('#colorHex').val($(this).val());
    });

    // Color preset buttons
    $(document).on('click', '.color-preset', function () {
        const color = $(this).data('color');
        $('#colorPicker').val(color);
        $('#colorHex').val(color);
    });

    // Select2
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush
