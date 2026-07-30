@extends('layouts.theme')

@section('title', 'Editar categoría')

@section('page_header')
    @include('core::components.card', ['title' => 'Proveedores'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

    <div class="card w-100">

        <form id="formCategory" enctype="multipart/form-data" role="form" onSubmit="return false">

            {{ csrf_field() }}

            <input type="hidden" id="categoryId" name="id" value="{{ $category->id }}">

            <div class="card-body">
                <div class="d-flex no-block align-items-center mb-1">
                    <h5 class="mb-0">Editar categoría: <span class="text-primary">{{ $category->name }}</span></h5>
                </div>
                <p class="card-subtitle mb-3 mt-1 text-muted">
                    ERP ID: <code>{{ $category->erp_id ?? '—' }}</code>
                    @if($category->sport)
                        · Deporte: <code>{{ $category->sport->name }}</code>
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
                                   value="{{ $category->name }}"
                                   required
                                   placeholder="Nombre de la categoría">
                            <small class="form-text text-muted">Nombre descriptivo de la categoría</small>
                        </div>
                    </div>

                    <!-- Nombre corto -->
                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Nombre corto</label>
                            <input type="text"
                                   class="form-control"
                                   id="short_name"
                                   name="short_name"
                                   value="{{ $category->short_name }}"
                                   placeholder="Nombre abreviado">
                            <small class="form-text text-muted">Versión corta del nombre (opcional)</small>
                        </div>
                    </div>

                    <!-- Deporte -->
                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Deporte</label>
                            <select class="form-control select2"
                                    id="sport_id"
                                    name="sport_id"
                                    data-placeholder="Sin deporte...">
                                <option value=""></option>
                                @foreach($sports as $sport)
                                    <option value="{{ $sport->id }}" {{ $category->sport_id == $sport->id ? 'selected' : '' }}>
                                        {{ $sport->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Deporte asociado a esta categoría</small>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Estado</label>
                            <select class="form-control select2"
                                    id="available"
                                    name="available"
                                    data-placeholder="Seleccionar estado...">
                                <option value="1" {{ $category->available ? 'selected' : '' }}>Activa</option>
                                <option value="0" {{ !$category->available ? 'selected' : '' }}>Inactiva</option>
                            </select>
                            <small class="form-text text-muted">Solo las categorías activas están disponibles</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    Guardar
                </button>
                <a href="{{ route('settings.suppliers.categories.index') }}" class="btn btn-secondary w-100">
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

    $('#sport_id').select2({
        allowClear: true,
        placeholder: 'Sin deporte...',
    });

    $('#available').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity,
    });

    $('#formCategory').validate({
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
        submitHandler: function (form) {
            var id = $('#categoryId').val();
            var $btn = $('button[type="submit"]');
            $btn.prop('disabled', true);

            $.ajax({
                url: '/setting/suppliers/categories/edit/' + id,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                type: 'PUT',
                data: {
                    name:       $('#name').val(),
                    short_name: $('#short_name').val(),
                    sport_id:   $('#sport_id').val(),
                    available:  $('#available').val(),
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, 'Operación exitosa', {
                            closeButton: true, progressBar: true, positionClass: 'toast-bottom-right'
                        });
                        setTimeout(function () {
                            window.location = '{{ route('settings.suppliers.categories.index') }}';
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
