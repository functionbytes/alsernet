@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formContacs" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="description" name="description" value="{!! $contact->description !!}">
                    <input type="hidden" id="id" name="id" value="{{ $contact->id }}">
                    <input type="hidden" id="slack" name="slack" value="{{ $contact->uid }}">
                    <input type="hidden" id="statuSignatures" name="statuSignatures" value="true">
                    <input type="hidden" id="statuThumbnails" name="statuThumbnails" value="true">
                    <input type="hidden" id="edit" name="edit" value="true">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar contactenos</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para permitirte  <mark><code>introducir</code></mark> nueva información de manera sencilla y estructurada. A continuación, se presentan varios campos que deberás completar con los datos requeridos.
                        </p>

                        <div class="row">

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Nombres</label>
                                        <input type="text" class="form-control" id="firstname"  name="firstname" disabled  value="{{ $contact->firstname }}" placeholder="Ingresar nombres">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Apellidos</label>
                                        <input type="text" class="form-control" id="lastname"  name="lastname" disabled value="{{ $contact->lastname }}" placeholder="Ingresar apellido">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Celular</label>
                                        <input type="text" class="form-control" id="identification"  name="identification" disabled value="{{ $contact->cellphone }}" placeholder="Ingresar identificación">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('reviewed', $revieweds, $contact->reviewed , ['class' => 'select2 form-control','id' => 'reviewed']) !!}
                                    </div>
                                    <label id="reviewed-error" class="error d-none" for="reviewed"></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Correo electronico</label>
                                        <input type="text" class="form-control" id="email"  name="email" disabled value="{{ $contact->email }}" placeholder="Ingresar profección">
                                    </div>
                                </div>
                            </div>



                        <div class="col-12">
                            <div class="mb-3">
                                <label class="col-form-label">Mensaje</label>
                                <div class="quill-wrapper">
                                    <div id="messages">{!! $contact->message !!}</div>
                                </div>
                                <label id="message-error" class="error d-none" for="message"></label>
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

                </form>
            </div>

        </div>

    </div>

@endsection



@push('scripts')

    <script type="text/javascript">
        Dropzone.autoDiscover = false;

        $(document).ready(function() {

            $("#formContacs").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    available: {
                        required: true,
                    },

                },
                messages: {
                    reviewed: {
                        required: "Es necesario un estado.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formContacs');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var reviewed = $("#reviewed").val();

                    formData.append('slack', slack);
                    formData.append('reviewed', reviewed);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);



                    $.ajax({
                        url: "{{route('callcenter.contacts.update')}}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                toastr.success("Se ha editado correctamente la solicitud de contacto.", "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location.href = "{{ route('callcenter.contacts') }}";
                                }, 3000);


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



    <script type="text/javascript">


        var toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'script': 'sub' }, { 'script': 'super' }],
            [{ 'indent': '-1' }, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [ 'link', 'image', 'video' ],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],

            ['clean']
        ];


        var toolbarOption = [
            ['clean']
        ];

        var description = new Quill('#messages', {

            modules: {
                toolbar: toolbarOption,
                clipboard: {
                    matchVisual: false
                }
            },
            placeholder: 'Escriba aquí...',
            theme: 'snow'
        });

        $(".ql-editor").addClass('disabled');
        $(".ql-editor").attr('contenteditable', false);

    </script>

@endpush



