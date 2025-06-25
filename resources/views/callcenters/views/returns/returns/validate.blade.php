@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formValidate" enctype="multipart/form-data" role="form" onSubmit="return false">
                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Validar orden</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para permitirte <mark><code>introducir</code></mark> nueva información de manera sencilla y estructurada. A continuación, se presentan varios campos que deberás completar con los datos requeridos.
                        </p>

                        <div class="row">
                            <div class="col-12 mb-3">
                                    <input type="text" class="form-control"  id="order_number"  name="order_number"  placeholder="Ej: ORD-12345 o 12345" autocomplete="new-password">
                            </div>

                            <div class="col-12">
                                <div class="errors d-none alert alert-danger">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border-top pt-1 mt-4">
                                    <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100" id="searchBtn">
                                        <span class="btn-text">Buscar</span>
                                        <span class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
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
            $("#formValidate").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    order_number: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                },
                messages: {
                    order_number: {
                        required: "El número de orden es necesario.",
                        minlength: "Debe contener al menos 3 caracteres",
                        maxlength: "Debe contener máximo 100 caracteres",
                    },
                },
                submitHandler: function(form) {
                    var $form = $('#formValidate');
                    var formData = new FormData($form[0]);

                    var $submitButton = $('#searchBtn');
                    var $btnText = $submitButton.find('.btn-text');
                    var $spinner = $submitButton.find('.spinner-border');

                    // Mostrar loading
                    $submitButton.prop('disabled', true);
                    $btnText.text('Buscando...');
                    $spinner.removeClass('d-none');

                    // Limpiar errores previos
                    $('.errors').addClass('d-none').html('');

                    $.ajax({
                        url: "{{ route('callcenter.returns.validate.order') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if (response.success == true) {
                                // Mostrar mensaje de éxito
                                toastr.success(response.message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                // Redireccionar después de un breve delay
                                setTimeout(function() {
                                    if (response.redirect_url) {
                                        window.location.href = response.redirect_url;
                                    } else {
                                        // Fallback si no hay URL específica
                                        window.location.href = "{{ route('callcenter.returns.generate', ':orderNumber') }}".replace(':orderNumber', $('#order_number').val());
                                    }
                                }, 1500);

                            } else {
                                // Mostrar error
                                let error = response.message;
                                $('.errors').removeClass('d-none').html('<i class="fas fa-exclamation-triangle me-2"></i>' + error);

                                toastr.error(error, "Error", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                // Ocultar error después de 5 segundos
                                setTimeout(function() {
                                    //$('.errors').addClass('d-none').html('');
                                }, 5000);
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Error de conexión. Por favor intente nuevamente.';

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            $('.errors').removeClass('d-none').html('<i class="fas fa-exclamation-circle me-2"></i>' + errorMessage);

                            toastr.error(errorMessage, "Error de conexión", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });

                            setTimeout(function() {
                                $('.errors').addClass('d-none').html('');
                            }, 5000);
                        },
                        complete: function() {
                            // Restaurar botón
                            $submitButton.prop('disabled', false);
                            $btnText.text('Buscar');
                            $spinner.addClass('d-none');
                        }
                    });
                }
            });

            // Focus automático en el input
            $('#order_number').focus();

            // Permitir envío con Enter
            $('#order_number').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#formValidate').submit();
                }
            });
        });
    </script>
@endpush
