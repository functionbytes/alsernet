@extends('layouts.managers')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formCategories" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <input type="hidden" id="id" name="id" value="{{ $categorie->id }}">
          <input type="hidden" id="slack" name="slack" value="{{ $categorie->uid }}">

          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Editar categoria</h5>

            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
            </p>

            <div class="row">

              <div class="col-6">
                <div class="mb-3">
                  <div class="mb-3">
                    <label  class="control-label col-form-label">Titulo</label>
                    <input type="text" class="form-control" id="title"  name="title"  placeholder="Ingresa titulo" value=" {{ $categorie->title  }}" >
                  </div>
                </div>
              </div>


              <div class="col-6">
                <div class="mb-3">
                  <label class="control-label col-form-label">Estado</label>
                  <div class="input-group">
                    {!! Form::select('available', $availables, $categorie->available , ['class' => 'select2 form-control' ,'name' => 'available', 'id' => 'available' ]) !!}
                  </div>
                  <label id="available-error" class="error d-none" for="available"></label>
                </div>
              </div>

            </div>

          </div>


          <div class="action-form border-top mt-4">
            <div class="text-center">
              <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                Guardar
              </button>
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
          var id = $("#id").val();
          var title = $("#title").val();
          var available = $("#available").val();

          formData.append('id', id);
          formData.append('title', title);
          formData.append('available', available);

          $.ajax({
            url: "{{ route('manager.categories.blogs.update') }}",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            contentType: false,
            processData: false,
            data: formData,
            success: function(data) {
              window.location = "{{ route('manager.categories.blogs') }}";
            }
          });

        }

      });

    });

  </script>


@endpush



