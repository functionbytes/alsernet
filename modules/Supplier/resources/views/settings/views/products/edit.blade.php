@extends('layouts.theme')

@section('title', 'Editar producto')

@section('page_header')
    @include('core::components.card', ['title' => 'Productos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

    <div class="card w-100">

        <form id="formProduct" role="form" onSubmit="return false">

            {{ csrf_field() }}
            <input type="hidden" id="productId" name="id" value="{{ $product->id }}">

            <div class="card-body">
                <div class="d-flex no-block align-items-center mb-1">
                    <h5 class="mb-0">Editar producto: <span class="text-primary">{{ $product->name }}</span></h5>
                </div>
                <p class="card-subtitle mb-3 mt-1 text-muted">
                    Código: <code>{{ $product->code }}</code>
                    @if($product->supplier)
                        · Proveedor: <code>{{ $product->supplier->label }}</code>
                    @endif
                    @if($product->category)
                        · Categoría: <code>{{ $product->category->name }}</code>
                    @endif
                </p>

                <div class="row">

                    <!-- Nombre -->
                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="name"
                                   name="name"
                                   value="{{ $product->name }}"
                                   required
                                   placeholder="Nombre del producto">
                            <small class="form-text text-muted">Nombre descriptivo del producto</small>
                        </div>
                    </div>

                    <!-- Disponible -->
                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Disponible</label>
                            <select class="form-control select2"
                                    id="available"
                                    name="available">
                                <option value="1" {{ $product->available ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ !$product->available ? 'selected' : '' }}>No</option>
                            </select>
                            <small class="form-text text-muted">Disponibilidad del producto</small>
                        </div>
                    </div>

                    <!-- Publicado en web -->
                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Publicado en tienda</label>
                            <select class="form-control select2"
                                    id="web_published"
                                    name="web_published">
                                <option value="1" {{ $product->web_published ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ !$product->web_published ? 'selected' : '' }}>No</option>
                            </select>
                            <small class="form-text text-muted">Visibilidad en la tienda online</small>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Descripción del producto">{{ $product->description }}</textarea>
                            <small class="form-text text-muted">Descripción opcional del producto</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    Guardar
                </button>
                <a href="{{ route('settings.suppliers.products.index') }}" class="btn btn-secondary w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

    </div>{{-- widget-content --}}

@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function () {

    $('#available, #web_published').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity,
    });

    $('#formProduct').validate({
        ignore: '.ignore',
        rules: {
            name: { required: true, minlength: 2, maxlength: 255 },
        },
        messages: {
            name: {
                required: 'El nombre es necesario.',
                minlength: 'Debe contener al menos 2 caracteres',
                maxlength: 'No debe exceder 255 caracteres',
            },
        },
        submitHandler: function () {
            var id = $('#productId').val();
            var $btn = $('button[type="submit"]');
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("settings.suppliers.products.update", $product->id) }}',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                type: 'POST',
                data: {
                    _method:       'PUT',
                    name:          $('#name').val(),
                    description:   $('#description').val(),
                    available:     $('#available').val(),
                    web_published: $('#web_published').val(),
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, 'Operación exitosa', {
                            closeButton: true, progressBar: true, positionClass: 'toast-bottom-right'
                        });
                        setTimeout(function () {
                            window.location = '{{ route('settings.suppliers.products.index') }}';
                        }, 1500);
                    } else {
                        $btn.prop('disabled', false);
                        toastr.warning(response.message, 'Operación fallida', {
                            closeButton: true, progressBar: true, positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false);
                    toastr.error(xhr.responseJSON?.message || 'Error al guardar', 'Error', {
                        closeButton: true, progressBar: true, positionClass: 'toast-bottom-right'
                    });
                }
            });
        }
    });

});
</script>
@endpush
