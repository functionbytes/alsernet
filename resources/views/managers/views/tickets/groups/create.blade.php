@extends('layouts.managers')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formGroup" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}



          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Crear grupo</h5>
            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para permitirte  <mark><code>introducir</code></mark> nueva información de manera sencilla y estructurada. A continuación, se presentan varios campos que deberás completar con los datos requeridos.
            </p>
            <div class="row">

              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Titulo</label>
                    <input type="text" class="form-control" id="title"  name="title"  placeholder="Ingresa titulo">
                  </div>
              </div>

              <div class="col-6">
                <div class="mb-3">
                  <label class="control-label col-form-label">Estado</label>
                  <div class="input-group">
                    {!! Form::select('available', $availables, null , ['class' => 'select2 form-control' ,'name' => 'available', 'id' => 'available' ]) !!}
                  </div>
                 <label id="available-error" class="error d-none" for="available"></label>
                </div>
              </div>

              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Usuarios</label>
                  <div class="input-group">
                    {!! Form::select('users[]', $users, null, ['class' => 'select2 form-control'  , 'multiple' => 'multiple' , 'id' => 'users']) !!}
                  </div>
                  <label id="users-error" class="error d-none" for="users"></label>
                </div>
              </div>
              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Categorias</label>
                  <div class="input-group">
                    {!! Form::select('categories[]', $categories, null , ['class' => 'select2 form-control'  , 'multiple' => 'multiple' , 'id' => 'categories']) !!}
                  </div>
                  <label id="categories-error" class="error d-none" for="categories"></label>
                </div>
              </div>
<div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
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

    $(document).ready(function() {


      $("#formGroup").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
          title: {
            required: true,
            minlength: 3,
            maxlength: 100,
          },
          available: {
            required: true,
          },
          'users[]': {
            required: false,
          },
          'categories[]': {
            required: false,
          },

        },
        messages: {
          title: {
            required: "El parametro es necesario.",
            minlength: "Debe contener al menos 3 caracter",
            maxlength: "Debe contener al menos 100 caracter",
          },
          available: {
            required: "Es necesario un estado.",
          },
          'users[]': {
            required: "Es necesario un tags.",
          },
          'categories[]': {
            required: "Es necesario un tags.",
          },
        },
        submitHandler: function(form) {

          var $form = $('#formGroup');
          var formData = new FormData($form[0]);
          var title = $("#title").val();
          var available = $("#available").val();
          var users = $("#users").val();
          var categories = $("#categories").val();

          formData.append('title', title);
          formData.append('available', available);
          formData.append('users', users);
          formData.append('categories', categories);

            var $submitButton = $('button[type="submit"]');
            $submitButton.prop('disabled', true);

          $.ajax({
            url: "{{ route('manager.tickets.groups.store') }}",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
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
                          window.location = "{{ route('manager.tickets.groups') }}";
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

