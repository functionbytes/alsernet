@extends('layouts.managers')


@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formMetadata" enctype="multipart/form-data" role="form" onSubmit="return false">
                    {{ csrf_field() }}

                    <input type="hidden" id="meta_description" name="meta_description" value="{!! setting('meta_description') !!}">
                    <input type="hidden" id="slack" name="slack" value="{!! setting('meta_image') !!}">
                    <input type="hidden" id="statuMetas" name="statuMetas" value="{{ $metadata }}">
                    <input type="hidden" id="statuEdit" name="statuEdit" value="true">
                    <input type="hidden" id="metadata" name="metadata">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Imagen</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la foto de tu perfil es necesario actualizar para mantener tus datos al día.
                        </p>
                        <div class="dropzone dz-clickable" id="metadata">
                            <div class="fallback">
                                <input type="file" hidden name="metadata">
                            </div>
                        </div>
                        <label id="metadata-error" class="error d-none" for="metadata"></label>
                    </div>


                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar metadata</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Título</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ setting('meta_title') }}" placeholder="Ingresar título">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Palabras clave</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="{{ setting('meta_keywords') }}" >
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="control-label col-form-label">Descripción</label>
                                <div class="">
                                    <div id="descriptions">{!! setting('meta_description') !!}</div>
                                </div>
                                <label id="description-error" class="error d-none" for="description"></label>
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

            $('#meta_keywords').tagsinput({
                maxTags: 15
            });

            $("#formMetadata").validate({
                ignore: ".ignore",
                rules: {
                    meta_title: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    meta_description: {
                        required: true,
                        minlength: 3,
                        maxlength: 500,
                    },
                    'meta_keywords[]': {
                        required: true,
                    },
                    metadata: {
                        required: function() {
                            return $("#statuMetas").val() === '';
                        }
                    }
                },
                messages: {
                    meta_title: {
                        required: "El parámetro es necesario.",
                        minlength: "Debe contener al menos 3 caracteres",
                        maxlength: "Debe contener como máximo 100 caracteres",
                    },
                    'meta_keywords[]': {
                        required: "El parámetro es necesario.",
                    },
                    meta_description: {
                        required: "El parámetro es necesario.",
                        minlength: "Debe contener al menos 3 caracteres",
                        maxlength: "Debe contener como máximo 500 caracteres",
                    },
                    metadata: {
                        required: "Es necesario una imagen.",
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("id") == "metadata") {
                        error.insertAfter("#metadata");
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    
                    var $form = $('#formMetadata');
                    var formData = new FormData($form[0]);
                    var meta_title = $("#meta_title").val();
                    var meta_keywords = $("#meta_keywords").val();
                    var meta_description = $("#meta_description").val();

                    formData.append('meta_title', meta_title);
                    formData.append('meta_keywords', meta_keywords);
                    formData.append('meta_description', meta_description);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);


                    $.ajax({
                        url: "{{ route('manager.settings.metadata.update') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {
                            if(response.success == true){

                                myMetadata.processQueue();

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                myMetadata.on("queuecomplete", function() {
                                    
                                });

                                setTimeout(function() {
                                        window.location = "{{ route('manager.dashboard') }}";
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

                            
                        },
                        error: function(xhr, status, error) {
                            toastr.error("Ha ocurrido un error. Por favor, inténtelo de nuevo.", "Error", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    });
                }
            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var myMetadata = new Dropzone("div#metadata", {
                paramName: "file",
                url: "{{ route('manager.settings.metadata') }}",
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
                    var myMetadata = this;

                    item = $("#slack").val();

                    $.getJSON("{{ route('manager.settings.metadata.metadata.get', ':item') }}".replace(':item', item), function(data) {

                        $.each(data, function(key, value) {

                            var mockFile = {
                                id: value.id,
                                uuid: value.uuid,
                                name: value.file,
                                size: value.size,
                                path: value.path,
                                file: value.file
                            };

                            myMetadata.options.addedfile.call(myMetadata, mockFile);
                            myMetadata.options.thumbnail.call(myMetadata, mockFile,  value.path);
                            myMetadata.options.complete.call(myMetadata, mockFile);
                            myMetadata.options.success.call(myMetadata, mockFile);

                        });
                    });

                    myMetadata.on("maxfilesexceeded", function(file) {
                        this.removeFile(file);
                    });

                    myMetadata.on('sending', function(file, xhr, formData) {
                        let setting = document.getElementById('slack').value;
                        formData.append('setting', setting);
                    });

                    myMetadata.on("addedfile", function(file) {
                        $("#metadata").val(file.name);
                        $("#formMetadata").validate().element("#metadata");
                    });

                    myMetadata.on("removedfile", function(file) {
                        $("#metadata").val('');
                        $("#formMetadata").validate().element("#metadata");

                        if (file.id) {
                            $.ajax({
                                type: 'GET',
                                url: "{{ route('manager.certifiers.thumbnails.delete', ':id') }}".replace(':id', file.id),
                                success: function(result) {
                                    $("#status").val('false');
                                }
                            });
                        }
                    });

                    myMetadata.on('resetFiles', function() {
                        $("#status").val('false');
                        myMetadata.removeAllFiles();
                    });

                    myMetadata.on("success", function(file, response) {
                    });

                    myMetadata.on("queuecomplete", function() {
                    });

                    myMetadata.on("complete", function() {
                    
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

        var description = new Quill('#descriptions', {
            modules: {
                toolbar: toolbarOption,
                clipboard: {
                    matchVisual: false
                }
            },
            placeholder: 'Escriba aquí...',
            theme: 'snow'
        });

        description.on('selection-change', function (range, oldRange, source) {
            if (range === null && oldRange !== null) {
                $('body').removeClass('overlay-disabled');
            } else if (range !== null && oldRange === null) {
                $('body').addClass('overlay-disabled');
            }
        });

        description.on('text-change', function(delta, oldDelta, source) {
            var text = description.container.firstChild.innerHTML.replaceAll("<p><br></p>", "");
            $('#meta_description').val(text);
        });
    </script>

@endpush