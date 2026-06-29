@extends('layouts.theme')

@section('title', isset($option) ? 'Editar opcion de producto' : 'Nueva opcion de producto')

@section('page_header')
    @include('core::components.card', ['title' => isset($option) ? 'Editar opcion de producto' : 'Nueva opcion de producto'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        <form
            action="{{ isset($option) ? route('ecommerce.product-options.update', $option) : route('ecommerce.product-options.store') }}"
            method="POST"
            id="option-form"
        >
            @csrf
            @isset($option)
                @method('PUT')
            @endisset

            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h5 class="mb-0 fw-bold">{{ isset($option) ? 'Editar opcion' : 'Nueva opcion' }}</h5>
                            <small class="text-muted">Define el nombre y los valores posibles de esta opcion</small>
                        </div>
                        <div class="card-body">
                            @include('core::components.alerts')

                            <div class="mb-4">
                                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $option->name ?? '') }}"
                                    placeholder="Ej: Color, Talla, Material"
                                    required
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <h6 class="fw-bold mb-1 border-bottom pb-2">Valores</h6>
                                <p class="text-muted small mb-3">Agrega cada valor posible para esta opcion (ej: Rojo, Azul, Verde).</p>

                                <div id="values-list" class="mb-3">
                                    @php
                                        $existingValues = old('values', isset($option) ? ($option->values->pluck('value')->toArray() ?: []) : []);
                                        if (empty($existingValues)) {
                                            $existingValues = [''];
                                        }
                                    @endphp

                                    @foreach($existingValues as $val)
                                        <div class="input-group mb-2 value-row">
                                            <input
                                                type="text"
                                                name="values[]"
                                                class="form-control"
                                                placeholder="Ej: Rojo"
                                                value="{{ $val }}"
                                            >
                                            <button type="button" class="btn btn-outline-secondary remove-value-btn" title="Eliminar valor">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-value-btn">
                                    <i class="fas fa-plus me-1"></i> Agregar valor
                                </button>

                                @error('values')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                                @error('values.*')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar opcion</button>
                            <a href="{{ route('ecommerce.product-options.index') }}" class="btn btn-light w-100">Cancelar</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Sobre las opciones</h6>
                            <p class="text-muted small mb-2">Las opciones de producto permiten definir variantes como talla, color o material.</p>
                            <p class="text-muted small mb-0">Cada valor que agregues estara disponible al crear o editar un producto.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    var $list = $('#values-list');

    function buildRow() {
        return $('<div class="input-group mb-2 value-row">' +
            '<input type="text" name="values[]" class="form-control" placeholder="Ej: Rojo">' +
            '<button type="button" class="btn btn-outline-secondary remove-value-btn" title="Eliminar valor">' +
                '<i class="fas fa-times"></i>' +
            '</button>' +
        '</div>');
    }

    $('#add-value-btn').on('click', function () {
        $list.append(buildRow());
        $list.find('.value-row:last input').focus();
    });

    $list.on('click', '.remove-value-btn', function () {
        if ($list.find('.value-row').length > 1) {
            $(this).closest('.value-row').remove();
        }
    });
});
</script>
@endpush
