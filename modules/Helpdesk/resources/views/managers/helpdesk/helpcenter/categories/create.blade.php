@extends('layouts.theme')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formCategory" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <div class="card-body">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Crear Categoría</h5>
            </div>
            <p class="card-subtitle mb-3 mt-3">
              Completa los campos necesarios para crear una nueva categoría del centro de ayuda.
            </p>

            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Nombre *</label>
                  <input type="text" class="form-control" id="name" name="name"
                         placeholder="Ingresa el nombre de la categoría">
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
                <div class="mb-3">
                  <label class="control-label col-form-label">Icono (Font Awesome)</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i id="icon-preview" class="fa-duotone fa-icons"></i>
                    </span>
                    <input type="text" class="form-control" id="icon" name="icon"
                           placeholder="Ej: fa-duotone fa-question-circle">
                  </div>
                  <small class="form-text text-muted">
                    Ingresa las clases de Font Awesome. <a href="https://fontawesome.com/search?o=r&m=free" target="_blank">Ver iconos disponibles</a>
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <label class="control-label col-form-label">Visible para</label>
                  <select class="form-select" id="visible_to_role" name="visible_to_role">
                    <option value="">Todos los usuarios</option>
                    @foreach($roles as $role)
                      <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                    @endforeach
                  </select>
                  <small class="form-text text-muted">
                    Selecciona qué rol puede ver esta categoría en el centro de ayuda público
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <label class="control-label col-form-label">Gestionado por</label>
                  <select class="form-select" id="managed_by_role" name="managed_by_role">
                    <option value="">Sin restricción</option>
                    @foreach($roles as $role)
                      <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                    @endforeach
                  </select>
                  <small class="form-text text-muted">
                    Selecciona qué rol puede gestionar esta categoría
                  </small>
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

      // Icon preview
      $('#icon').on('input', function() {
        var iconClasses = $(this).val();
        if (iconClasses) {
          $('#icon-preview').attr('class', iconClasses);
        } else {
          $('#icon-preview').attr('class', 'fa-duotone fa-icons');
        }
      });

      $("#formCategory").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
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
            url: "{{ route('manager.helpdesk.helpcenter.categories.store') }}",
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
                toastr.error('Ocurrió un error al guardar la categoría');
              }
            }
          });
        }
      });

    });

  </script>

@endpush
