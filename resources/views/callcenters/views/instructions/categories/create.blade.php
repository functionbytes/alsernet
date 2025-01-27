@extends('layouts.callcenters')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formCategories" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}



          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Crear categoria</h5>
            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para permitirte  <mark><code>introducir</code></mark> nueva información de manera sencilla y estructurada. A continuación, se presentan varios campos que deberás completar con los datos requeridos.
            </p>
            <div class="row">

              <div class="col-12">
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

              <div class="col-6">
                <div class="mb-3">
                  <label  class="control-label col-form-label">Icono</label>
                  <input type="text" class="form-control" id="icon"  name="icon"  placeholder="Ingresa el icono">
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


      $("#formCategories").validate({
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
        },
        submitHandler: function(form) {

          var $form = $('#formCategories');
          var formData = new FormData($form[0]);
          var title = $("#title").val();
          var available = $("#available").val();

          formData.append('title', title);
          formData.append('available', available);

            var $submitButton = $('button[type="submit"]');
            $submitButton.prop('disabled', true);



            $.ajax({
            url: "{{ route('callcenter.instructions.categories.store') }}",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            contentType: false,
            processData: false,
            data: formData,

              success: function(response) {

                  if(response.success == true){

                      toastr.success("Se ha creado correctamente.", "Operación exitosa", {
                          closeButton: true,
                          progressBar: true,
                          positionClass: "toast-bottom-right"
                      });

                      setTimeout(function() {
                          window.location = "{{ route('callcenter.instructions.categories') }}";
                      }, 2000);


                  }else{

                      $submitButton.prop('disabled', false);

                      toastr.warning("Se ha generado un error.", "Operación fallida", {
                          closeButton: true,
                          progressBar: true,
                          positionClass: "toast-bottom-right"
                      });

                      error = response.message;
                      $('.errors').removeClass('d-none');
                      $('.errors').html(error);

                  }
              }

          });

        }

      });

    });

  </script>


@endpush

