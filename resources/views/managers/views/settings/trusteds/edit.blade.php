@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            
            <div class="card w-100">

                <form id="formTrusted" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="{{ $trusted->id }}">
                    <input type="hidden" id="slack" name="slack" value="{{ $trusted->slack }}">
                    <input type="hidden" id="status" name="status" value="{{ $thumbnail }}">
                    <input type="hidden" id="edit" name="edit" value="true">
                    <input type="hidden" id="thumbnail" name="thumbnail">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Imagen</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para permitirte  <mark><code>introducir</code></mark> nueva información de manera sencilla y estructurada. A continuación, se presentan varios campos que deberás completar con los datos requeridos.
                        </p>
                        <div class="dropzone dz-clickable dz-started" id="thumbnail">
                            <div class="fallback">
                                <input type="file" hidden name="file">
                            </div>
                        </div>
                        <label id="thumbnail-error" class="error d-none" for="thumbnail"></label>
                    </div>

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar aliado</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-6">
                                <div class="mb-3">
                                    <div class="mb-3">
                                        <label for="cono1" class="control-label col-form-label">Titulo</label>
                                        <input type="text" class="form-control" id="title"  name="title" value="{{ $trusted->title }}" placeholder="Ingresar titulo">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('available', $availables, $trusted->available, ['class' => 'select2 form-control','id' => 'available']) !!}
                                    </div>
                                    <label id="available-error" class="error" for="available"></label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="mb-3">
                                        <label for="cono1" class="control-label col-form-label">Link</label>
                                        <input type="text" class="form-control" id="url"  name="url" value="{{ $trusted->url }}" placeholder="Ingresar la url">
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

            $("#formTrusted").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    title: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    url: {
                        required: false,
                        url : true,
                        minlength: 0,
                        maxlength: 1000,
                    },
                    available: {
                        required: true,
                    },
                    thumbnail: {
                        required: function() {
                            return $("#status").val() == "false" ? true : false;
                        }
                    }
                },
                messages: {
                    title: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    url: {
                        required: "El parametro es necesario.",
                        url: "Debe ingresar una url valida.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos c caracter",
                    },
                    available: {
                        required: "Es necesario un estado.",
                    },
                    thumbnail: {
                        required: "Es necesario una imagen.",
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("id") == "thumbnail") {
                        error.insertAfter("#thumbnail");
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {

                    var $form = $('#formTrusted');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var title = $("#title").val();
                    var url = $("#url").val();
                    var available = $("#available").val();

                    formData.append('slack', slack);
                    formData.append('title', title);
                    formData.append('url', url);
                    formData.append('available', available);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.trusteds.update') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.status == true){

                                slack = response.slack;
                                $("#slack").val(slack);
                                myThumbnail.processQueue();

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                myThumbnail.on("queuecomplete", function() {
                                    setTimeout(function() {
                                        window.location = "{{ route('manager.trusteds') }}";
                                    }, 2000);
                                });

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


            $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
            });

            var myThumbnail = new Dropzone("div#thumbnail", {
                paramName: "file",
                url: "{{ route('manager.trusteds.thumbnails') }}",
                addRemoveLinks: true,
                autoProcessQueue: false,
                uploadMultiple: false,
                acceptedFiles: "image/*", 
                parallelUploads: 1,
                maxFiles: 1,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                init: function() {

                    var myThumbnail = this;

                    item = $("#slack").val();

                    $.getJSON("{{ route('manager.trusteds.thumbnails.get', ':item') }}".replace(':item', item), function(data) {

                        $.each(data, function(key, value) {

                            var mockFile = {
                                id: value.id,
                                uuid: value.uuid,
                                name: value.file,
                                size: value.size,
                                path: value.path,
                                file: value.file
                            };

                            myThumbnail.options.addedfile.call(myThumbnail, mockFile);
                            myThumbnail.options.thumbnail.call(myThumbnail, mockFile,  value.path);
                            myThumbnail.options.complete.call(myThumbnail, mockFile);
                            myThumbnail.options.success.call(myThumbnail, mockFile);

                        });

                    });

                    myThumbnail.on("maxfilesexceeded", function(file) {
                        this.removeFile(file);
                    });

                    myThumbnail.on('sending', function(file, xhr, formData) {
                        let trusted = document.getElementById('slack').value;
                        formData.append('trusted', trusted);

                    });

                    myThumbnail.on("addedfile", function(file) {
                        $("#thumbnail").val(file.name);
                        $("#formTrusted").validate().element("#thumbnail");
                    });

                    myThumbnail.on("removedfile", function(file) {

                        $("#thumbnail").val('');
                        $("#formTrusted").validate().element("#thumbnail");
                        if (file.id) {
                            $.ajax({
                                type: 'GET',
                                url: "{{ route('manager.trusteds.thumbnails.delete', ':id') }}".replace(':id', file.id),
                                success: function(result) {
                                    $("#status").val('false');
                                }
                            });
                        }

                    });

                    myThumbnail.on('resetFiles', function() {
                        $("#status").val('false');
                        myThumbnail.removeAllFiles();
                    });


                    myThumbnail.on("success", function(file, response) {
                        $("#status").val('true');
                    });

                    myThumbnail.on("queuecomplete", function() {
                        $("#status").val('true');
                    });

                    myThumbnail.on("complete", function() {
                        $("#status").val('true');
                        uploadThumbnail();
                    });
                }
            });


        });

    </script>




@endpush



