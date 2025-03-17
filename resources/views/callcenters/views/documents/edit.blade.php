@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formDocuments" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="description" name="description" value="{!! $document->description  !!}">
                    <input type="hidden" id="id" name="id" value="{{ $document->id }}">
                    <input type="hidden" id="slack" name="slack" value="{{ $document->uid }}">
                    <input type="hidden" id="status" name="status" value="{{ $file }}">
                    <input type="hidden" id="edit" name="edit" value="true">
                    <input type="hidden" id="files" name="files">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Documento</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para permitirte  <mark><code>introducir</code></mark> nueva información de manera sencilla y estructurada. A continuación, se presentan varios campos que deberás completar con los datos requeridos.
                        </p>
                        <div class="dropzone dz-clickable dz-started" id="files">
                            <div class="fallback">
                                <input type="file" hidden name="file">
                            </div>
                        </div>
                        <label id="files-error" class="error d-none" for="files"></label>
                    </div>

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar documento</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Titulo</label>
                                        <input type="text" class="form-control" id="title"  name="title" value="{{ $document->title }}" placeholder="Ingresar titulo">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('available', $availables, $document->available, ['class' => 'select2 form-control','id' => 'available']) !!}
                                    </div>
                                    <label id="available-error" class="error d-none" for="available"></label>
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

            $("#formDocuments").validate({
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
                    files: {
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
                    available: {
                        required: "Es necesario un estado.",
                    },
                    files: {
                        required: "Es necesario un documento.",
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("id") == "files") {
                        error.insertAfter("#files");
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {

                    var $form = $('#formDocuments');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var title = $("#title").val();
                    var available = $("#available").val();

                    formData.append('slack', slack);
                    formData.append('title', title);
                    formData.append('available', available);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('callcenter.documents.update') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                slack = response.slack;
                                $("#slack").val(slack);
                                myFiles.processQueue();

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                myFiles.on("queuecomplete", function() {

                                });

                                setTimeout(function() {
                                        window.location = "{{ route('callcenter.documents') }}";
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


            $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
            });

            var myFiles = new Dropzone("div#files", {
                paramName: "file",
                url: "{{ route('callcenter.documents.files') }}",
                method: 'POST',
                addRemoveLinks: true,
                autoProcessQueue: false,
                uploadMultiple: false,
                acceptedFiles: ".pdf",
                parallelUploads: 1,
                maxFiles: 1,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                init: function() {

                    statufiles = false;
                    var myFiles = this;

                    item = $("#slack").val();

                    $.getJSON("{{ route('callcenter.documents.files.get', ':item') }}".replace(':item', item), function(data) {
                        if (data.length > 0) {
                            $("#status").val('true');
                            $("#files").val(data[0].file);

                            $.each(data, function(key, value) {

                                var mockFile = {
                                    id: value.id,
                                    uuid: value.uuid,
                                    name: value.file,
                                    size: value.size,
                                    path: value.path,
                                    file: value.file
                                };

                                myFiles.options.addedfile.call(myFiles, mockFile);
                                myFiles.options.files.call(myFiles, mockFile,  value.path);
                                myFiles.options.complete.call(myFiles, mockFile);
                                myFiles.options.success.call(myFiles, mockFile);

                                });

                            }

                    });

                    myFiles.on("maxfilesexceeded", function(file) {
                        this.removeFile(file);
                    });

                    myFiles.on('sending', function(file, xhr, formData) {
                        let documents = document.getElementById('slack').value;
                        formData.append('documents', documents);
                    });

                    myFiles.on("addedfile", function(file) {
                        $("#files").val(file.name);
                        $("#status").val('true');
                        $("#formDocuments").validate().element("#files");
                    });

                    myFiles.on("removedfile", function(file) {
                        $("#files").val('');
                        $("#status").val('false');
                        $("#formDocuments").validate().element("#files");
                        if (file.id) {
                            $.ajax({
                                type: 'GET',
                                url: "{{ route('callcenter.documents.files.delete', ':id') }}".replace(':id', file.id),
                                success: function(result) {
                                    $("#status").val('false');
                                }
                            });
                        }

                    });

                    myFiles.on('resetFiles', function() {
                        $("#status").val('false');
                        myFiles.removeAllFiles();
                    });

                    myFiles.on("success", function(file, response) {
                        $("#status").val('true');
                    });

                    myFiles.on("queuecomplete", function() {
                    });

                    myFiles.on("complete", function() {
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
            $('#description').val(text);
        });

    </script>

@endpush



