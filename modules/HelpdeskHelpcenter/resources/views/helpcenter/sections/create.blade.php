@extends('layouts.theme')

@section('title', 'Nueva sección')

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Nueva sección'])
@endsection

@section('content')

  @include('core::components.alerts')

  <div class="card">

    <div class="card-header p-4 border-bottom border-light">
      <h5 class="mb-1 fw-bold">Nueva sección</h5>
      <p class="small mb-0 text-muted">Las secciones agrupan artículos dentro de una categoría raíz</p>
    </div>

    <form id="formSection" enctype="multipart/form-data" role="form" onSubmit="return false">

      {{ csrf_field() }}

      <div class="card-body">

            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Categoría Padre *</label>
                  <select class="form-select" id="parent_id" name="parent_id">
                    <option value="">Selecciona una categoría</option>
                    @foreach($categories as $category)
                      <option value="{{ $category->id }}" @if(isset($parentId) && $parentId == $category->id) selected @endif>
                        {{ $category->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Nombre *</label>
                  <input type="text" class="form-control" id="name" name="name"
                         placeholder="Ingresa el nombre de la sección">
                </div>
              </div>

              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Descripción</label>
                  <textarea class="form-control" id="description" name="description" rows="3"
                            placeholder="Ingresa una descripción"></textarea>
                </div>
              </div>

            </div>
          </div>

          <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('manager.helpcenter.categories') }}" class="btn btn-outline-secondary">
              Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i> Guardar
            </button>
          </div>
        </form>
      </div>

@endsection

@push('scripts')

  <script type="text/javascript">

    $(document).ready(function () {

      $("#formSection").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
          parent_id: {
            required: true,
          },
          name: {
            required: true,
            minlength: 3,
            maxlength: 255,
          },
          description: {
            maxlength: 1000,
          },
        },
        messages: {
          parent_id: {
            required: "Debes seleccionar una categoría padre.",
          },
          name: {
            required: "El nombre es necesario.",
            minlength: "El nombre debe tener al menos 3 caracteres.",
            maxlength: "El nombre no puede exceder 255 caracteres.",
          },
          description: {
            maxlength: "La descripción no puede exceder 1000 caracteres.",
          },
        },
        errorElement: "label",
        errorClass: "error",
        errorPlacement: function (error, element) {
          error.insertAfter(element);
        },
        submitHandler: function (form) {
          const submitBtn = $(form).find('button[type="submit"]');

          const originalBtnText = submitBtn.html();

          submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');


          $.ajax({
            type: 'POST',
            url: "{{ route('manager.helpcenter.sections.store') }}",
            data: $(form).serialize(),
            dataType: 'json',
            success: function (data) {
              submitBtn.prop('disabled', false).html(originalBtnText);
              if (data.success) {
                setTimeout(function () {
                  window.location.href = data.redirect;
                }, 1000);
              }
            },
            error: function (xhr) {
              submitBtn.prop('disabled', false).html(originalBtnText);
              if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                $.each(errors, function (key, value) {
                  toastr.error(value[0]);
                });
              } else {
                toastr.error('Ocurrió un error al guardar la sección');
              }
            }
          });
        }
      });

    });

  </script>

@endpush
