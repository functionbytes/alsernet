





@extends('layouts.shops')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formNotifications" enctype="multipart/form-data" role="form" onSubmit="return false">

          <input type="hidden" name="slack"  id="slack" value="{{ $user->uid }}">
          {{ csrf_field() }}

          <div class="card-body border-top mb-20">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Configuración de notificaciones</h5>
            </div>

            <div class="row mt-20">

              <div class="col-12 ">
                <div class="mb-4 mt-3">
                  <div class="row align-items-center">
                    <div class=" col-sm-11 ">
                      <label  class="control-label col-form-label ">Noticiaciones de email general</label>
                      <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración no te llegarn notificaciones sobre inscripciones que se le hagan a empresas.</p>
                    </div>
                    <div class="col-sm-1 justify-content-end d-flex align-items">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="mail_notification" id="mail_notification"   @if($user->mail_notification==1 ) checked @endif/>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 border-top">
                <div class="mb-4 mt-3">
                  <div class="row align-items-center">
                    <div class=" col-sm-11 ">
                      <label  class="control-label col-form-label ">Noticiaciones de facturación</label>
                      <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración no te llegarn notificaciones sobre inscripciones que se le hagan a empresas.</p>
                    </div>
                    <div class="col-sm-1 justify-content-end d-flex align-items">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="invoice_notification" id="invoice_notification"   @if($user->invoice_notification==1 ) checked @endif/>
                      </div>
                    </div>
                  </div>
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

      $("#formNotifications").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
          mail_notification: {
            required: false,
          },
          inscription_notification: {
            required: false,
          },
          invoice_notification: {
            required: false,
          },
        },
        message: {
          mail_notification: {
            required: "El parametro es necesario.",
          },
          inscription_notification: {
            required: "El parametro es necesario.",
          },
          invoice_notification: {
            required: "El parametro es necesario.",
          },
        },
        submitHandler: function(form) {

          var $form = $('#formNotifications');
          var formData = new FormData($form[0]);
          var slack = $("#slack").val();
          var mail_notification = $("#mail_notification").is(':checked');
          var inscription_notification = $("#inscription_notification").is(':checked');
          var invoice_notification = $("#invoice_notification").is(':checked');

          formData.append('slack', slack);
          formData.append('mail_notification', mail_notification);
          formData.append('inscription_notification', inscription_notification);
          formData.append('invoice_notification', invoice_notification);

            var $submitButton = $('button[type="submit"]');
            $submitButton.prop('disabled', true);

            $.ajax({
            url: "{{ route('callcenter.settings.notifications.update') }}",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            contentType: false,
            processData: false,
            data: formData,
            success: function(response) {

              if(response.success == true){
                toastr.success("Se ha editado correctamente el perfil.", "Operación exitosa", {
                  closeButton: true,
                  progressBar: true,
                  positionClass: "toast-bottom-right"
                });

                setTimeout(function() {
                  window.location.href = "{{ route('callcenter.dashboard') }}";
                }, 2000);

              }else{

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







