@extends('layouts.theme')

@section('title', 'Editar proveedor')

@section('content')

    @include('core::components.card', ['title' => 'Proveedores'])

    <div class="widget-content searchable-container list">

    <div class="card w-100">

        <form id="formSupplier" enctype="multipart/form-data" role="form" onSubmit="return false">

            {{ csrf_field() }}

            <input type="hidden" id="slack" name="slack" value="{{ $supplier->uid }}">

            <div class="card-body">
                <div class="d-flex no-block align-items-center mb-1">
                    <h5 class="mb-0">Editar proveedor: <span class="text-primary">{{ $supplier->label }}</span></h5>
                </div>
                <p class="card-subtitle mb-3 mt-1 text-muted">
                    Editando el proveedor <code>{{ $supplier->code }}</code>
                    @if($supplier->erp_id)
                        · ERP ID: <code>{{ $supplier->erp_id }}</code>
                    @endif
                </p>

                <div class="row">

                    <!-- ID Gestión -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">ID Gestión</label>
                            <input type="number"
                                   class="form-control"
                                   id="erp_id"
                                   name="erp_id"
                                   value="{{ $supplier->erp_id }}"
                                   placeholder="Ej: 3001"
                                   min="1">
                            <small class="form-text text-muted">ID del proveedor en el ERP (opcional)</small>
                        </div>
                    </div>

                    <!-- Código -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Código
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="code"
                                   name="code"
                                   value="{{ $supplier->code }}"
                                   required
                                   placeholder="Código único del proveedor"
                                   pattern="[A-Z0-9_-]+"
                                   title="Solo letras mayúsculas, números, guiones y guiones bajos">
                            <small class="form-text text-muted">Identificador único (mayúsculas, ej: NIKE)</small>
                        </div>
                    </div>

                    <!-- Nombre -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="label"
                                   name="label"
                                   value="{{ $supplier->label }}"
                                   required
                                   placeholder="Nombre del proveedor">
                            <small class="form-text text-muted">Nombre descriptivo que se mostrará en la interfaz</small>
                        </div>
                    </div>

                    <!-- Prioridad -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Prioridad
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control"
                                   id="priority"
                                   name="priority"
                                   value="{{ $supplier->priority }}"
                                   required
                                   min="1"
                                   max="100"
                                   placeholder="1">
                            <small class="form-text text-muted">Orden de prioridad (1-100, menor primero)</small>
                        </div>
                    </div>

                    <!-- Email de Contacto -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Email de contacto</label>
                            <input type="email"
                                   class="form-control"
                                   id="email"
                                   name="email"
                                   value="{{ $supplier->email }}"
                                   placeholder="contacto@proveedor.com">
                            <small class="form-text text-muted">Email de contacto del proveedor</small>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Estado</label>
                            <select class="form-control select2 select2"
                                    id="available"
                                    name="available"
                                    data-placeholder="Seleccionar estado...">
                                <option value="1" {{ $supplier->available == 1 ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ $supplier->available == 0 ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            <label id="available-error" class="error d-none" for="available"></label>
                            <small class="form-text text-muted">Solo los proveedores activos se sincronizarán</small>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Descripción del proveedor...">{{ $supplier->description }}</textarea>
                            <small class="form-text text-muted">Información adicional sobre el proveedor (opcional)</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    Guardar
                </button>
                <a href="{{ route('settings.suppliers.index') }}" class="btn btn-secondary w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

    </div>{{-- widget-content --}}

@endsection



@push('scripts')

  <script type="text/javascript">

    $(document).ready(function() {

      // Initialize Select2
      $('.select2').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity
      });

      $("#formSupplier").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
          code: {
            required: true,
            minlength: 2,
            maxlength: 20,
          },
          label: {
            required: true,
            minlength: 3,
            maxlength: 255,
          },
          priority: {
            required: true,
            number: true,
            min: 1,
            max: 100,
          },
          email: {
            email: true,
          },
          available: {
            required: true,
          },

        },
        messages: {
          code: {
            required: "El código es necesario.",
            minlength: "Debe contener al menos 2 caracteres",
            maxlength: "No debe exceder 20 caracteres",
          },
          label: {
            required: "El nombre es necesario.",
            minlength: "Debe contener al menos 3 caracteres",
            maxlength: "No debe exceder 255 caracteres",
          },
          priority: {
            required: "La prioridad es necesaria.",
            number: "Debe ser un número.",
            min: "La prioridad mínima es 1",
            max: "La prioridad máxima es 100",
          },
          email: {
            email: "Debe ser un email válido.",
          },
          available: {
            required: "Es necesario un estado.",
          },
        },
        submitHandler: function(form) {

          var $form = $('#formSupplier');
          var formData = new FormData($form[0]);
          var slack = $("#slack").val();
          var erp_id = $("#erp_id").val();
          var code = $("#code").val();
          var label = $("#label").val();
          var priority = $("#priority").val();
          var email = $("#email").val();
          var description = $("#description").val();
          var available = $("#available").val();

          formData.append('slack', slack);
          if (erp_id) formData.append('erp_id', erp_id);
          formData.append('code', code);
          formData.append('label', label);
          formData.append('priority', priority);
          formData.append('email', email);
          formData.append('description', description);
          formData.append('available', available);

          var $submitButton = $('button[type="submit"]');
          $submitButton.prop('disabled', true);

          $.ajax({
            url: "{{ route('settings.suppliers.update', $supplier->uid) }}",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "PUT",
            contentType: false,
            processData: false,
            data: formData,
            success: function(response) {

              if(response.success == true){

                message = response.message;

                toastr.success(message, "Operación exitosa", {
                  closeButton: true,
                  progressBar: true,
                  positionClass: "toast-bottom-right"
                });

                setTimeout(function() {
                  window.location = "{{ route('settings.suppliers.index') }}";
                }, 2000);

              }else{

                $submitButton.prop('disabled', false);
                error = response.message;

                toastr.warning(error, "Operación fallida", {
                  closeButton: true,
                  progressBar: true,
                  positionClass: "toast-bottom-right"
                });

                $('.errors').text(error);
                $('.errors').removeClass('d-none');

              }

            }
          });

        }

      });

    });

  </script>


@endpush
