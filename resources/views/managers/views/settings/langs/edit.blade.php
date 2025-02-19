@extends('layouts.managers')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formLangs" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <input type="hidden" id="id" name="id" value="{{ $lang->id }}">
          <input type="hidden" id="uid" name="uid" value="{{ $lang->uid }}">

          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Editar idioma</h5>

            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
            </p>

            <div class="row">

                <div class="col-6">
                  <div class="mb-3">
                    <label  class="control-label col-form-label">Titulo</label>
                    <input type="text" class="form-control" id="title"  name="title"  placeholder="Ingresa titulo" value=" {{ $lang->title  }}" >
                  </div>
              </div>

                <div class="col-6">
                    <div class="mb-3">
                        <label  class="control-label col-form-label">Iso code</label>
                        <input type="text" class="form-control" id="iso_code"  name="iso_code"  placeholder="Ingresa iso code" value=" {{ $lang->iso_code  }}" >
                    </div>
                </div>

                <div class="col-6">
                    <div class="mb-3">
                        <label  class="control-label col-form-label">Lenguage code</label>
                        <input type="text" class="form-control" id="lenguage_code"  name="lenguage_code"  placeholder="Ingresa lenguage codigo" value="{{ $lang->lenguage_code  }}" >
                    </div>
                </div>

                <div class="col-6">
                    <div class="mb-3">
                        <label  class="control-label col-form-label">Locacion</label>
                        <input type="text" class="form-control" id="locate"  name="locate"  placeholder="Ingresa locacion" value=" {{ $lang->locate  }}" >
                    </div>
                </div>
                <div class="col-6">
                    <div class="mb-3">
                        <label  class="control-label col-form-label">Formato fecha</label>
                        <input type="text" class="form-control" id="date_format_full"  name="date_format_full"  placeholder="Ingresa formato fecha" value=" {{ $lang->date_format_full  }}" >
                    </div>
                </div>
                <div class="col-6">
                    <div class="mb-3">
                        <label  class="control-label col-form-label">Formato fecha lite</label>
                        <input type="text" class="form-control" id="date_format_lite"  name="date_format_lite"  placeholder="Ingresa formato fecha lite" value=" {{ $lang->date_format_lite  }}" >
                    </div>
                </div>

                <div class="col-6">
                    <div class="mb-3">
                        <label class="control-label col-form-label">Estado</label>
                        <select class="form-control select2" id="available" name="available">
                            <option value="1" {{ $lang->available == 1 ? 'selected' : '' }}>Público</option>
                            <option value="0" {{ $lang->available == 0 ? 'selected' : '' }}>Oculto</option>
                        </select>
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-3">
                        <label for="users" class="control-label col-form-label">Categorias</label>
                        <select class="form-control select2" id="categories" name="categories[]" multiple="multiple">
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" {{ in_array($id, $lang->categories->pluck('id')->toArray()) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <label id="categories-error" class="error d-none" for="users"></label>
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


      $("#formLangs").validate({
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
          prioritie: {
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
          prioritie: {
            required: "Es necesario un estado.",
          },
        },
        submitHandler: function(form) {

          var $form = $('#formLangs');
          var formData = new FormData($form[0]);
          var id = $("#id").val();
          var title = $("#title").val();
          var available = $("#available").val();
          var iso_code = $("#iso_code").val();
          var lenguage_code = $("#lenguage_code").val();
          var locate = $("#locate").val();
          var date_format_full = $("#date_format_full").val();
          var date_format_lite = $("#date_format_lite").val();
          var categories = $("#categories").val();

          formData.append('id', id);
          formData.append('title', title);
          formData.append('categories', categories);
          formData.append('iso_code', iso_code);
          formData.append('lenguage_code', lenguage_code);
          formData.append('locate', locate);
          formData.append('date_format_full', date_format_full);
          formData.append('date_format_lite', date_format_lite);
          formData.append('available', available);

            var $submitButton = $('button[type="submit"]');
            $submitButton.prop('disabled', true);

          $.ajax({
            url: "{{ route('manager.langs.update') }}",
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
                          window.location = "{{ route('manager.langs') }}";
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



