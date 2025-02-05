@extends('layouts.managers')

@section('content')

<div class="content-body">
    <div class="container-fluid">

        <div class="row page-titles mx-0">
            <div class="col-lg-12">

                <div class="col-sm-12 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active"><a href="{{ route('manager.dashboard') }}">Inicio</a></li>
                        <li class="breadcrumb-item active"><a href="{{ route('manager.teams') }}">Equipo</a></li>
                        <li class="breadcrumb-item "><a href="javascript:void(0)">Editar</a></li>
                    </ol>
                </div>
            </div>
        </div>


        <!-- row -->
        <div class="row">
            <div class="col-xl-12 col-xxl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"></h4>
                    </div>
                    <div class="card-body">

                      <form id="formTeams" enctype="multipart/form-data" role="form" onSubmit="return false">
                        {{ csrf_field() }}


                        <input type="hidden" id="id" name="id" value="{{ $team->id }}">
                        <input type="hidden" id="slack" name="slack" value="{{ $team->uid }}">
                        <input type="hidden" id="status" name="status" value="true">
                        <input type="hidden" id="edit" name="edit" value="true">
                        <div>

                            <section>

                                <div class="form-group-attached">
                                    <div class="row clearfix">
                                        <div class="col-sm-12 clearfix">

                                            <div class="dropzone-container col-md-12 pt-1 pb-2 mb-md-0">


                                                @if ($thumbnail != null)
                                                <div class="dropzone upload-file text-center py-5 dz-started" id="dropzoneThumbnail">
                                                    @else
                                                    <div class="dropzone upload-file text-center py-5" id="dropzoneThumbnail">
                                                        @endif

                                                        <div class="dz-default dz-message">
                                                            <button class="btn btn-indigo px-7 mb-2" type="button">
                                                                Buscar Archivo
                                                            </button>

                                                            <p class="text-heading fs-22 lh-15">Arrastra y suelta la imagen o
                                                            </p>

                                                            <input type="file" hidden name="file">
                                                            <p>Las fotos deben estar en formato JPEG o PNG y al menos 1024 x 768</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row clearfix">


                                     <div class="col-lg-6 mb-2">
                                            <div class="form-group">
                                                <label class="text-label">Nombres <span class="required">*</span></label>
                                                <input type="text" name="firstname" id="firstname" class="form-control" placeholder="Parsley" value="{{ $team->firstname }}" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-2">
                                            <div class="form-group">
                                                <label class="text-label">Apellidos <span class="required">*</span></label>
                                                <input type="text" name="lastname" id="lastname"  class="form-control" placeholder="Parsley" value="{{ $team->lastname }}"  required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-2">
                                            <div class="form-group">
                                                <label class="text-label">Cargo <span class="required">*</span></label>
                                                <input type="text" name="charge" id="charge" class="form-control" placeholder="Parsley" value="{{ $team->charge }}" required>
                                            </div>
                                        </div>

                                        <div class="col-lg-6 mb-2">
                                            <div class="form-group">
                                                <label class="text-label">Estado <span class="required">*</span></label>
                                                <div class="align-items-center">
                                                    {!! Form::select('available', $availables, $team->available , ['class' => 'full-width select','type' => 'text' ,'name' => 'available' ,'id' => 'available' , 'data-init-plugin' => 'select2']) !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-12 mb-2">
                                            <div class="form-group">
                                                <label class="text-label">Descripción <span class="required">*</span></label>
                                                <div class="quill-wrapper">
                                                    <div  id="descriptions"> {!! $team->description !!}</div>
                                                </div>
                                                <textarea style="display: none"  id="description" name="description"> {!! $team->description !!}</textarea>
                                            </div>
                                        </div>



                                    </div>
                            </section>
                            <button class="btn btn-primary btn-lg btn-block" type="submit" id="submit">Editar</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection





@push('scripts')

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    $(document).ready(function() {

        $("#formTeams").validate({
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
                charge: {
                    required: true,
                    minlength: 3,
                    maxlength: 100,
                },
                available: {
                    required: true,
                },
                description: {
                    required: true,
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
                charge: {
                    required: "El parametro es necesario.",
                    minlength: "Debe contener al menos 3 caracter",
                    maxlength: "Debe contener al menos 100 caracter",
                },
                description: {
                    required: "La descripción es necesario.",
                    minlength: "Debe contener almenos 3 caracter",
                    maxlength: "Debe contener almenos 100 caracter",
                },
                available: {
                    required: "Es necesario un estado.",
                },
            },
            submitHandler: function(form) {

                var $form = $('#formTeams');
                var formData = new FormData($form[0]);
                var slack = $("#slack").val();
                var firstname = $("#firstname").val();
                var lastname = $("#lastname").val();
                var charge = $("#charge").val();
                var available = $("#available").val();
                var description = $("#description").val();

                formData.append('slack', slack);
                formData.append('firstname', firstname);
                formData.append('lastname', lastname);
                formData.append('charge', charge);
                formData.append('available', available);
                formData.append('description', description);

                $.ajax({
                    url: "/manager/teams/update",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "POST",
                    contentType: false,
                    processData: false,
                    data: formData,
                    success: function(data) {

                        $("#slack").val(data);
                        myThumbnail.processQueue();
                        uploadThumbnail();

                    }
                });

            }

        });


        // Dropzone.options.rentals = false;
        let token = $('meta[name="csrf-token"]').attr('content');

        var myThumbnail = new Dropzone("div#dropzoneThumbnail", {
            paramName: "file"
            , url: "{{ url('/manager/teams/thumbnail') }}"
            , addRemoveLinks: true
            , autoProcessQueue: false
            , uploadMultiple: false
            , acceptedFiles: ".jpg,.jpeg"
            , parallelUploads: 1
            , maxFiles: 1
            , params: {
                _token: token
            },
            // The setting up ´f the dropzone
            init: function() {


                statuThumbnail = false;

                var myThumbnail = this;

                item = $("#slack").val();

                var path = "{{ asset('pages/images') }}";

                $.getJSON('/manager/teams/get/thumbnail/' + item, function(data) {
                    $.each(data, function(key, value) {
                        var mockFile = {
                            name: value.file
                            , size: value.size
                            , file: value.file
                        };
                        myThumbnail.options.addedfile.call(myThumbnail, mockFile);
                        myThumbnail.options.thumbnail.call(myThumbnail, mockFile
                            , path + "/teams/" + value.file);
                        myThumbnail.options.complete.call(myThumbnail, mockFile);
                        myThumbnail.options.success.call(myThumbnail, mockFile);
                    });
                });

                myThumbnail.on("maxfilesexceeded", function(file) {
                    this.removeFile(file);
                });

                myThumbnail.on('sending', function(file, xhr, formData) {
                    let team = document.getElementById('slack').value;
                    formData.append('team', team);
                });

                myThumbnail.on("addedfile", function(file) {
                    $("#status").val('false');
                    $('#dropzoneThumbnail').addClass('dz-started');
                });

                myThumbnail.on("removedfile", function(file) {
                    $("#status").val('false');
                    item = file.name;

                    if (item.length > 20) {
                        $.ajax({
                            type: 'GET'
                            , url: '/manager/teams/delete/thumbnail/' + item
                            , success: function(result) {
                                //$('#dropzoneThumbnail').addClass('dz-started');
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



        function uploadThumbnail() {

            var statuThumbnail = $("#status").val();
            var statuEdit = $("#edit").val();

            if (statuThumbnail == 'true' && statuEdit == 'true') {
                location.href = "/manager/teams";
            }

        }

        $('.select').select2({
            placeholder: "Selección"
            , minimumResultsForSearch: -1
        });


		var toolbarOptions = [
			['bold', 'italic', 'underline', 'strike'],        // toggled buttons
			['blockquote', 'code-block'],

			[{ 'header': 1 }, { 'header': 2 }],               // custom button values
			[{ 'list': 'ordered' }, { 'list': 'bullet' }],
			[{ 'script': 'sub' }, { 'script': 'super' }],      // superscript/subscript
			[{ 'indent': '-1' }, { 'indent': '+1' }],          // outdent/indent
			[{ 'direction': 'rtl' }],                         // text direction

			[{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
			[{ 'header': [1, 2, 3, 4, 5, 6, false] }],

			[{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
			[{ 'font': [] }],
			[{ 'align': [] }],

			['clean']                                         // remove formatting button
		];

		var description = new Quill('#descriptions', {
			modules: {
				toolbar: toolbarOptions
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
            $('#description').val(description.container.firstChild.innerHTML);
        });


    });

</script>



@endpush
