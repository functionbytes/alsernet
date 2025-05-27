@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formEvents" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="{{ $event->id }}">
                    <input type="hidden" id="slack" name="slack" value="{{ $event->uid }}">
                    <input type="hidden" id="edit" name="edit" value="true">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Editar evento
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Titulo</label>
                                    <input type="text" class="form-control" id="title"  name="title" value="{{ $event->title }}" placeholder="Ingresar nombre">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Fecha de inicio</label>
                                    <input type="text" class="form-control" id="start_at"  name="start_at" value="{{ $event->start_at }}" placeholder="Ingresar nombre">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Fecha de final</label>
                                    <input type="text" class="form-control" id="end_at"  name="end_at" value="{{ $event->end_at }}" placeholder="Ingresar nombre">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Referencia</label>
                                    <input type="text" class="form-control" id="reference"  name="reference" value="{{ $event->reference }}" placeholder="Ingresar nombre">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('available', $availables, $event->available , ['class' => 'select2 form-control','id' => 'available']) !!}
                                    </div>
                                    <label id="available-error" class="error d-none" for="available"></label>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Modalidad iva</label>
                                    <div class="input-group">
                                        {!! Form::select('iva', $options, $event->iva , ['class' => 'select2 form-control','id' => 'iva']) !!}
                                    </div>
                                    <label id="iva-error" class="error d-none" for="iva"></label>
                                </div>
                            </div>

                            <hr>


                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Banner uncio</label>
                                    <div class="input-group">
                                        {!! Form::select('banners_unique', $options, $event->banners_unique , ['class' => 'select2 form-control','id' => 'banners_unique']) !!}
                                    </div>
                                    <label id="banners_unique-error" class="error d-none" for="iva"></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Banners</label>
                                    <div class="input-group">
                                        {!! Form::select('iva', $options, $event->iva , ['class' => 'select2 form-control','id' => 'iva' ,'multiple' ]) !!}
                                    </div>
                                    <label id="iva-error" class="error d-none" for="iva"></label>
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

                        <hr>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Pagina id </label>
                                    <input type="text" class="form-control" id="cms"  name="cms" value="{{ $event->cms }}" placeholder="Ingresar normal">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Etiqueta evento</label>
                                    <input type="text" class="form-control" id="hover_buttom"  name="hover_buttom" value="{{ $event->hover_buttom }}" placeholder="Ingresar normal">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Numero filtro</label>
                                    <input type="text" class="form-control" id="amazing"  name="amazing" value="{{ $event->amazing }}" placeholder="Ingresar normal">
                                </div>
                            </div>



                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Color normal</label>
                                    <input type="text" class="form-control" id="color_buttom"  name="color_buttom" value="{{ $event->color_buttom }}" placeholder="Ingresar normal">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Color hover</label>
                                    <input type="text" class="form-control" id="hover_buttom"  name="hover_buttom" value="{{ $event->hover_buttom }}" placeholder="Ingresar normal">
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


            $("#formEvents").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    title: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    reference: {
                        required: false,
                        minlength: 1,
                        maxlength: 100,
                    },
                    barcode: {
                        required: false,
                        minlength: 1,
                        maxlength: 100,
                    },
                    available: {
                        required: true,
                    },

                },
                messages: {
                    title: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    reference: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    barcode: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    available: {
                        required: "El parametro es necesario.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formEvents');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var title = $("#title").val();
                    var barcode = $("#barcode").val();
                    var reference = $("#reference").val();
                    var available = $("#available").val();

                    formData.append('slack', slack);
                    formData.append('title', title);
                    formData.append('available', available);
                    formData.append('reference', reference);
                    formData.append('barcode', barcode);
                    formData.append('available', available);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.products.update') }}",
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
                                    window.location.href = "{{ route('manager.products') }}";
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



