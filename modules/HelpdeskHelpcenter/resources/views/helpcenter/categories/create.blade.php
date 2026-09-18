@extends('layouts.theme')

@section('title', 'Nueva categoría')

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Nueva categoría'])
@endsection

@section('content')

  @include('core::components.alerts')

  <div class="row g-3">

    {{-- Formulario --}}
    <div class="col-12 col-lg-8">
    <div class="card">

    <div class="card-header p-4 border-bottom border-light">
      <h5 class="mb-1 fw-bold">Nueva categoría</h5>
      <p class="small mb-0 text-muted">Crea un contenedor raíz para organizar secciones y artículos</p>
    </div>

    <form id="formCategory" enctype="multipart/form-data" role="form" onSubmit="return false">

      {{ csrf_field() }}

      <div class="card-body">

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
                      <i id="icon-preview" class="fas fa-icons"></i>
                    </span>
                    <input type="text" class="form-control" id="icon" name="icon"
                           placeholder="Ej: far fa-circle-question">
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

            </div>
          </div>

          <div class="card-footer">
            <button type="submit" class="btn btn-primary w-100 mb-1">Guardar</button>
            <a href="{{ route('manager.helpcenter.categories') }}" class="btn btn-light w-100">Cancelar</a>
          </div>
        </form>
      </div>

        </div>

        {{-- Panel de instrucciones --}}
        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las categorias</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted mb-0">
                        Una categoria es el primer nivel del centro de ayuda. Dentro de ella se
                        crean secciones, y dentro de las secciones van los articulos.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Como se organiza</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><span class="fw-semibold">Categoria:</span> el area general, p. ej. "Pedidos y envios"</li>
                        <li class="mb-2"><span class="fw-semibold">Seccion:</span> el subtema, p. ej. "Seguimiento del pedido"</li>
                        <li class="mb-0"><span class="fw-semibold">Articulo:</span> la respuesta concreta a una duda</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Pocas categorias y bien diferenciadas entre si</li>
                        <li class="mb-2">Nombres que use el cliente, no los internos del equipo</li>
                        <li class="mb-0">La posicion decide el orden en el centro de ayuda</li>
                    </ul>
                </div>
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
          $('#icon-preview').attr('class', 'fas fa-icons');
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
        submitHandler: function (form) {
          const submitBtn = $(form).find('button[type="submit"]');

          const originalBtnText = submitBtn.html();

          submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');


          $.ajax({
            type: 'POST',
            url: "{{ route('manager.helpcenter.categories.store') }}",
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
                toastr.error('Ocurrió un error al guardar la categoría');
              }
            }
          });
        }
      });

    });

  </script>

@endpush
