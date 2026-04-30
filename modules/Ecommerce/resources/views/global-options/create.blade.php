@extends('layouts.theme')

@section('title', 'Nueva opcion global')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva opcion global'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.global-options.store') }}" method="POST" id="option-form">
        @csrf
        <input type="hidden" name="save_action" id="save-action" value="save_exit">

        <div class="row g-4 align-items-start">

            {{-- Columna principal --}}
            <div class="col-lg-8">

                {{-- Nombre --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Informacion de la opcion</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-0">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="option-name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required
                                placeholder="Ej: Color, Talla, Material">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Valor de la opcion --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Valor de la opcion</h6>
                    </div>
                    <div class="card-body">

                        {{-- Placeholder cuando tipo = text --}}
                        <div id="option-values-placeholder" class="text-center py-4 text-muted">
                            <i class="fas fa-list fa-2x opacity-50 mb-2 d-block"></i>
                            <small>¡Elija el tipo de opcion!</small>
                        </div>

                        {{-- Tabla de valores --}}
                        <div id="option-values-section" style="display:none">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" id="values-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:32px"></th>
                                            <th>ETIQUETA</th>
                                            <th style="width:130px">PRECIO</th>
                                            <th style="width:140px">TIPO DE PRECIO</th>
                                            <th style="width:40px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="values-tbody">
                                        {{-- Rows added dynamically --}}
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-value-btn">
                                    <i class="fas fa-plus me-1"></i> Agregar nueva fila
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>{{-- /col-lg-8 --}}

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    {{-- Publicar --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Publicar</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2"
                                onclick="document.getElementById('save-action').value='save'">
                                Guardar opcion
                            </button>
                            <button type="submit" class="btn btn-outline-secondary w-100 mb-2"
                                onclick="document.getElementById('save-action').value='save_exit'">
                                Guardar y salir
                            </button>
                            <a href="{{ route('ecommerce.global-options.index') }}" class="btn btn-light w-100">
                                Cancelar
                            </a>
                        </div>
                    </div>

                    {{-- Tipo --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Tipo</h6>
                        </div>
                        <div class="card-body">
                            <select name="option_type" id="option_type"
                                class="form-select @error('option_type') is-invalid @enderror" required>
                                <optgroup label="Texto">
                                    <option value="text" {{ old('option_type', 'text') === 'text' ? 'selected' : '' }}>Field</option>
                                </optgroup>
                                <optgroup label="Seleccion">
                                    <option value="dropdown" {{ old('option_type') === 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                                    <option value="checkbox" {{ old('option_type') === 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                                    <option value="radio"    {{ old('option_type') === 'radio'    ? 'selected' : '' }}>RadioButton</option>
                                </optgroup>
                            </select>
                            @error('option_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Requerido --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">¿Se requiere?</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="required" value="1"
                                    id="option-required" {{ old('required') ? 'checked' : '' }}>
                                <label class="form-check-label" for="option-required">Obligatorio</label>
                            </div>
                        </div>
                    </div>

                    {{-- Orden --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Orden</h6>
                        </div>
                        <div class="card-body">
                            <input type="number" name="order" min="0"
                                class="form-control @error('order') is-invalid @enderror"
                                value="{{ old('order', 0) }}">
                            @error('order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>{{-- /col-lg-4 --}}

        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    var valueIndex = 0;

    function buildRow(index, value) {
        value = value || {};
        var selectedFixed = (!value.affect_type || value.affect_type === 'fixed') ? ' selected' : '';
        var selectedPct   = (value.affect_type === 'percentage') ? ' selected' : '';
        return '<tr class="value-row">' +
            '<td><i class="fas fa-grip-vertical text-muted" style="cursor:grab"></i></td>' +
            '<td><input type="text" name="values[' + index + '][option_value]" class="form-control form-control-sm" placeholder="Etiqueta" value="' + (value.option_value || '') + '" required></td>' +
            '<td><input type="number" name="values[' + index + '][affect_price]" class="form-control form-control-sm" placeholder="0.00" step="0.01" min="0" value="' + (value.affect_price || '') + '"></td>' +
            '<td>' +
                '<select name="values[' + index + '][affect_type]" class="form-select form-select-sm">' +
                    '<option value="fixed"' + selectedFixed + '>Fijado</option>' +
                    '<option value="percentage"' + selectedPct + '>Porcentaje</option>' +
                '</select>' +
            '</td>' +
            '<td><button type="button" class="btn btn-sm btn-light remove-value-btn"><i class="fas fa-times"></i></button></td>' +
        '</tr>';
    }

    function toggleValuesSection() {
        var type = document.getElementById('option_type').value;
        var section = document.getElementById('option-values-section');
        var placeholder = document.getElementById('option-values-placeholder');

        if (type === 'text') {
            section.style.display = 'none';
            placeholder.style.display = 'block';
        } else {
            placeholder.style.display = 'none';
            section.style.display = 'block';
            if (document.getElementById('values-tbody').children.length === 0) {
                addRow();
            }
        }
    }

    function addRow(value) {
        var tbody = document.getElementById('values-tbody');
        tbody.insertAdjacentHTML('beforeend', buildRow(valueIndex, value));
        valueIndex++;
    }

    document.getElementById('option_type').addEventListener('change', toggleValuesSection);

    document.getElementById('add-value-btn').addEventListener('click', function () {
        addRow();
    });

    document.getElementById('values-tbody').addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-value-btn');
        if (btn) { btn.closest('tr').remove(); }
    });

    @if(old('values'))
        @foreach(old('values', []) as $i => $val)
            addRow({ option_value: '{{ addslashes($val['option_value'] ?? '') }}', affect_price: '{{ $val['affect_price'] ?? '' }}', affect_type: '{{ $val['affect_type'] ?? 'fixed' }}' });
        @endforeach
    @endif

    toggleValuesSection();
}());
</script>
@endpush
