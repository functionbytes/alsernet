@extends('layouts.theme')

@section('title', 'Editar atributo de especificacion')

@section('content')
    @include('core::components.card', ['title' => 'Editar atributo de especificacion'])

    <form action="{{ route('ecommerce.specification-attributes.update', $specificationAttribute) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion del atributo</h5>
                        <small class="text-muted">Modifique los campos necesarios.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $specificationAttribute->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Valor por defecto</label>
                            <input type="text" name="default_value" class="form-control @error('default_value') is-invalid @enderror" value="{{ old('default_value', $specificationAttribute->default_value) }}">
                            @error('default_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div id="options-section" class="mb-3" style="display: none;">
                            <label class="form-label">Opciones</label>
                            <div id="options-list">
                                @foreach(old('options', $specificationAttribute->options ?? []) as $option)
                                    <div class="input-group mb-2 option-row">
                                        <input type="text" name="options[]" class="form-control" value="{{ $option }}" placeholder="Opcion">
                                        <button type="button" class="btn btn-outline-secondary btn-remove-option">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" id="btn-add-option" class="btn btn-outline-secondary btn-sm">
                                Agregar opcion
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card" style="top: 80px; position: sticky;">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Publicar</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar atributo</button>
                        <a href="{{ route('ecommerce.specification-attributes.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Grupo</h6>
                    </div>
                    <div class="card-body">
                        <select name="group_id" class="form-select @error('group_id') is-invalid @enderror">
                            <option value="">Seleccionar grupo</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group_id', $specificationAttribute->group_id) == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Tipo</h6>
                    </div>
                    <div class="card-body">
                        <select name="type" id="attr_type" class="form-select @error('type') is-invalid @enderror">
                            <option value="text" {{ old('type', $specificationAttribute->type) === 'text' ? 'selected' : '' }}>Texto</option>
                            <option value="textarea" {{ old('type', $specificationAttribute->type) === 'textarea' ? 'selected' : '' }}>Area de texto</option>
                            <option value="select" {{ old('type', $specificationAttribute->type) === 'select' ? 'selected' : '' }}>Lista de opciones</option>
                            <option value="checkbox" {{ old('type', $specificationAttribute->type) === 'checkbox' ? 'selected' : '' }}>Casilla</option>
                            <option value="radio" {{ old('type', $specificationAttribute->type) === 'radio' ? 'selected' : '' }}>Radio</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Detalles</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted d-block">Creado</small>
                            <span class="small">{{ $specificationAttribute->created_at->translatedFormat('j M, Y') }}</span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Actualizado</small>
                            <span class="small">{{ $specificationAttribute->updated_at->translatedFormat('j M, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    var typeSelect = document.getElementById('attr_type');
    var optionsSection = document.getElementById('options-section');
    var optionsList = document.getElementById('options-list');

    function toggleOptions() {
        optionsSection.style.display = ['select', 'radio'].includes(typeSelect.value) ? '' : 'none';
    }

    typeSelect.addEventListener('change', toggleOptions);
    toggleOptions();

    document.getElementById('btn-add-option').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'input-group mb-2 option-row';
        row.innerHTML = '<input type="text" name="options[]" class="form-control" placeholder="Opcion">'
            + '<button type="button" class="btn btn-outline-secondary btn-remove-option"><i class="fas fa-times"></i></button>';
        optionsList.appendChild(row);
    });

    optionsList.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-option');
        if (btn) {
            btn.closest('.option-row').remove();
        }
    });
}());
</script>
@endpush
