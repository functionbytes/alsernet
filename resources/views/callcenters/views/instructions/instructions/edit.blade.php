@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formInstructions" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="slack" name="slack" value="{{ $instruction->uid }}">
                    <input type="hidden" id="description" name="description" value="{!! $instruction->description !!}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar instrucciones</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Titulo</label>
                                        <input type="text" class="form-control" id="title"  name="title"  placeholder="Ingresa titulo" value=" {{ $instruction->title  }}" >
                                </div>
                            </div>


                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('available', $availables, $instruction->available , ['class' => 'select2 form-control' ,'name' => 'available', 'id' => 'available' ]) !!}
                                    </div>
                                    <label id="available-error" class="error d-none" for="available"></label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Categorias</label>
                                    <div class="input-group">
                                        {!! Form::select('categorie', $categories, $instruction->category_id  , ['class' => 'select2 form-control' ,'name' => 'categorie', 'id' => 'categorie' ]) !!}
                                    </div>
                                    <label id="available-error" class="error d-none" for="available"></label>
                                </div>
                            </div>


                            <div class="col-12">
                                <label class="control-label col-form-label">Descripción</label>
                                <div class="">
                                    <div id="descriptions">{!! $instruction->description !!}</div>
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
                toolbar: toolbarOptions,
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



        $(document).ready(function() {


            $("#formInstructions").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    title: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    description: {
                        required: true,
                        minlength: 3,
                        maxlength: 1000,
                    },
                    available: {
                        required: true,
                    },
                    categorie: {
                        required: true,
                    },
                },
                messages: {
                    title: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    description: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 1000 caracter",
                    },
                    available: {
                        required: "Es necesario un estado.",
                    },
                    categorie: {
                        required: "Es necesario un estado.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formInstructions');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var description = $("#description").val();
                    var title = $("#title").val();
                    var available = $("#available").val();
                    var categorie = $("#categorie").val();

                    formData.append('slack', slack);
                    formData.append('title', title);
                    formData.append('description', description);
                    formData.append('available', available);
                    formData.append('categorie', categorie);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);


                    $.ajax({
                        url: "{{ route('callcenter.instructions.update') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                toastr.success("Se ha editado correctamente.", "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location = "{{ route('callcenter.instructions') }}";
                                }, 3000);


                            }else{

                                $submitButton.prop('disabled', false);

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



