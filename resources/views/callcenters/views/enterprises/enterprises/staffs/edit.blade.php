@extends('layouts.callcenters')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formUsers" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <input type="hidden" id="id" name="id" value="{{ $user->id }}">
          <input type="hidden" id="slack" name="slack" value="{{ $user->uid }}">
          <input type="hidden" id="distributor" name="enterprise" value="{{ $distributor->uid }}">
          <input type="hidden" id="edit" name="edit" value="true">

          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Editar teacher</h5>
            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
            </p>

            <div class="row">

              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Nombres</label>
                    <input type="text" class="form-control" id="firstname"  name="firstname" value="{{ $user->firstname }}" placeholder="Ingresar nombres" autocomplete="new-password" >
                </div>
              </div>
              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Apellidos</label>
                    <input type="text" class="form-control" id="lastname"  name="lastname" value="{{ $user->lastname }}" placeholder="Ingresar apellido" autocomplete="new-password" >
                </div>
              </div>

              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Identificación</label>
                    <input type="text" class="form-control" id="identification"  name="identification" value="{{ $user->identification }}" placeholder="Ingresar identificación" autocomplete="new-password" >
                </div>
              </div>

              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Correo electronico</label>
                    <input type="text" class="form-control" id="email"  name="email" value="{{ $user->email }}" placeholder="Ingresar correo electronico" autocomplete="new-password" >
                </div>
              </div>

              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Dirección</label>
                    <input type="text" class="form-control" id="address"  name="address" value="{{ $user->address }}" placeholder="Ingresar dirección" autocomplete="new-password" >
                </div>
              </div>

              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Celular</label>
                    <input type="text" class="form-control" id="cellphone"  name="cellphone" value="{{ $user->cellphone }}" placeholder="Ingresar celular" autocomplete="new-password" >
                </div>
              </div>

              <div class="col-6">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password"  name="password" value="" placeholder="Ingresar contraseña" autocomplete="new-password" >
                </div>
              </div>

              <div class="col-6">
                <div class="mb-3">
                  <label class="control-label col-form-label">Estado</label>
                  <div class="input-group">
                    {!! Form::select('available', $availables, $user->available , ['class' => 'select2 form-control','id' => 'available']) !!}
                  </div>
                  <label id="available-error" class="error d-none" for="available"></label>
                </div>
              </div>

              <div class="col-12">
                <div class="errors d-none">
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
    Dropzone.autoDiscover = false;

    $(document).ready(function() {


      jQuery.validator.addMethod(
              'emailExt',
              function (value, element, param) {
                return value.match(
                        /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i,
                )
              },
              'Porfavor ingrese email valido',
      );

      $("#formUsers").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
          firstname: {
            required: true,
            minlength: 3,
            maxlength: 100,
          },
          lastname: {
            required: true,
            minlength: 3,
            maxlength: 100,
          },
          identification: {
            required: false,
            minlength: 3,
            maxlength: 100,
          },
          cellphone: {
            required: false,
            number: true,
            minlength: 6,
            maxlength: 10,
          },
          email: {
            required: true,
            email: true,
            emailExt: true,
          },
          available: {
            required: true,
          },
          address: {
            required: false,
            minlength: 3,
            maxlength: 100,
          },
          password: {
            required: false,
            minlength: 3,
            maxlength: 100,
          },

        },
        messages: {
          firstname: {
            required: "El parametro es necesario.",
            minlength: "Debe contener al menos 3 caracter",
            maxlength: "Debe contener al menos 100 caracter",
          },
          lastname: {
            required: "El parametro es necesario.",
            minlength: "Debe contener al menos 3 caracter",
            maxlength: "Debe contener al menos 100 caracter",
          },
          identification: {
            required: "El parametro es necesario.",
            minlength: "Debe contener al menos 3 caracter",
            maxlength: "Debe contener al menos 100 caracter",
          },
          cellphone: {
            required: "El parametro es necesario.",
            number: 'Solo se puede ingresar números.',
            minlength: "Debe contener al menos 6 caracter",
            maxlength: "Debe contener al menos 10 caracter",
          },
          email: {
            required: 'Tu email ingresar correo electrónico es necesario.',
            email: 'Por favor, introduce una dirección de correo electrónico válida.',
          },
          available: {
            required: "Es necesario un estado.",
          },
          address: {
            required: "El parametro es necesario.",
            minlength: "Debe contener al menos 3 caracter",
            maxlength: "Debe contener al menos 100 caracter",
          },
          password: {
            required: "El parametro es necesario.",
            minlength: "Debe contener al menos 6 caracter",
            maxlength: "Debe contener al menos 10 caracter",
          },
        },
        submitHandler: function(form) {

          var $form = $('#formUsers');
          var formData = new FormData($form[0]);
          var slack = $("#slack").val();
          var firstname = $("#firstname").val();
          var lastname = $("#lastname").val();
          var identification = $("#identification").val();
          var cellphone = $("#cellphone").val();
          var email = $("#email").val();
          var address = $("#address").val();
          var password = $("#password").val();
          var available = $("#available").val();

          formData.append('slack', slack);
          formData.append('firstname', firstname);
          formData.append('lastname', lastname);
          formData.append('identification', identification);
          formData.append('cellphone', cellphone);
          formData.append('email', email);
          formData.append('address', address);
          formData.append('password', password);
          formData.append('available', available);

            var $submitButton = $('button[type="submit"]');
            $submitButton.prop('disabled', true);

          $.ajax({
            url: "{{ route('callcenter.enterprises.staffs.update') }}",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            contentType: false,
            processData: false,
            data: formData,
            success: function(response) {


                if(response.success == true){

                    toastr.success("Se ha editado un empleado.", "Operación exitosa", {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });

                    setTimeout(function() {
                        let slack = @json($distributor->uid);
                        window.location.href = "{{ route('callcenter.enterprises.staffs', ':slack') }}".replace(':slack', slack);
                    }, 2000);

                }else{


                    $submitButton.prop('disabled', false);

                    error = response.message;

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



