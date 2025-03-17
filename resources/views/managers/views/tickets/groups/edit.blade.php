@extends('layouts.managers')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formGroup" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <input type="hidden" id="id" name="id" value="{{ $group->id }}">
          <input type="hidden" id="slack" name="slack" value="{{ $group->uid }}">

          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Editar grupo</h5>

            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
            </p>

            <div class="row">

              <div class="col-12">
                <div class="mb-3">
                  <div class="mb-3">
                    <label  class="control-label col-form-label">Titulo</label>
                    <input type="text" class="form-control" id="title"  name="title"  placeholder="Ingresa titulo" value=" {{ $group->title  }}" >
                  </div>
                </div>
              </div>


                <div class="col-6">
                    <div class="mb-3">
                        <label class="control-label col-form-label">Estado</label>
                        <select class="form-control select2" id="available" name="available">
                            <option value="1" {{ $group->available == 1 ? 'selected' : '' }}>Público</option>
                            <option value="0" {{ $group->available == 0 ? 'selected' : '' }}>Oculto</option>
                        </select>
                    </div>
                </div>

                <div class="col-6">
                    <div class="mb-3">
                        <label for="prioritie" class="control-label col-form-label">Categorias</label>
                        <select class="form-control select2" id="prioritie" name="prioritie">
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" {{ $group->categorie_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>


                <div class="col-12">
                    <div class="mb-3">
                        <label for="users" class="control-label col-form-label">Usuarios</label>
                        <select class="form-control select2" id="users" name="users[]" multiple="multiple">
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}" {{ in_array($id, $group->users->pluck('id')->toArray()) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <label id="users-error" class="error d-none" for="users"></label>
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
          var slack = $("#slack").val();
          var title = $("#title").val();
          var available = $("#available").val();
          var users = $("#users").val();
          var categories = $("#categories").val();

          formData.append('slack', slack);
          formData.append('title', title);
          formData.append('available', available);
          formData.append('users', users);
          formData.append('categories', categories);

            var $submitButton = $('button[type="submit"]');
            $submitButton.prop('disabled', true);

          $.ajax({
            url: "{{ route('manager.tickets.groups.update') }}",
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



