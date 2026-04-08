@extends('layouts.theme')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formSection" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <div class="card-body">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Crear Sección</h5>
            </div>
            <p class="card-subtitle mb-3 mt-3">
              Completa los campos necesarios para crear una nueva sección del centro de ayuda.
            </p>

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

              <div class="col-12">
                <div class="border-top pt-1 mt-4">
                  <a href="{{ route('manager.helpdesk.helpcenter.categories') }}"
                     class="btn btn-secondary px-4 waves-effect waves-light mt-2 me-2">
                    Cancelar
                  </a>
                  <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2">
                    Guardar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>

    </div>
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
        submitHandler: function (form, event) {
          event.preventDefault();

          $.blockUI({
            message: '<i class="icon-spinner4 spinner"></i>',
            overlayCSS: {
              backgroundColor: '#1b2024',
              opacity: 0.8,
              zIndex: 1200,
              cursor: 'wait'
            },
            css: {
              border: 0,
              color: '#fff',
              padding: 0,
              zIndex: 1201,
              backgroundColor: 'transparent'
            }
          });

          $.ajax({
            type: 'POST',
            url: "{{ route('manager.helpdesk.helpcenter.sections.store') }}",
            data: $(form).serialize(),
            dataType: 'json',
            success: function (data) {
              $.unblockUI();
              if (data.success) {
                toastr.success(data.message);
                setTimeout(function () {
                  window.location.href = data.redirect;
                }, 1000);
              }
            },
            error: function (xhr) {
              $.unblockUI();
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
