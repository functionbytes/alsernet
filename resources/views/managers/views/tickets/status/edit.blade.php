@extends('layouts.managers')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formStatus" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <input type="hidden" id="id" name="id" value="{{ $status->id }}">
          <input type="hidden" id="uid" name="uid" value="{{ $status->uid }}">

          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Editar estado</h5>

            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
            </p>

            <div class="row">

              <div class="col-12">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Titulo</label>
                    <input type="text" class="form-control" id="title"  name="title"  placeholder="Ingresa titulo" value=" {{ $status->title  }}" >
                  </div>
              </div>
              <div class="col-6">
                <div class="mb-3">
                  <label  class="control-label col-form-label">Color</label>
                  <input type="color" class="form-control" id="color"  name="color"  placeholder="Ingresa titulo" value="{{ $status->color  }}">
                </div>
              </div>


                <div class="col-6">
                    <div class="mb-3">
                        <label class="control-label col-form-label">Estado</label>
                        <select class="form-control select2" id="available" name="available">
                            <option value="1" {{ $status->available == 1 ? 'selected' : '' }}>Público</option>
                            <option value="0" {{ $status->available == 0 ? 'selected' : '' }}>Oculto</option>
                        </select>
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


      $("#formStatus").validate({
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
          color: {
            required: true,
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
          color: {
            required: "Es necesario un estado.",
          },
        },
        submitHandler: function(form) {

          var $form = $('#formStatus');
          var formData = new FormData($form[0]);
          var id = $("#id").val();
          var title = $("#title").val();
          var available = $("#available").val();
          var color = $("#color").val();
          var uid = $("#uid").val();

          formData.append('id', id);
          formData.append('title', title);
          formData.append('available', available);
          formData.append('color', color);
          formData.append('uid', uid);


            var $submitButton = $('button[type="submit"]');
            $submitButton.prop('disabled', true);

          $.ajax({
            url: "{{ route('manager.tickets.status.update') }}",
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
                          window.location = "{{ route('manager.tickets.status') }}";
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



