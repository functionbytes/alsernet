@extends('layouts.callcenters')

@section('content')

    @include('customer.includes.card', ['title' => 'Crear tikects'])


    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTikects" enctype="multipart/form-data" role="form" onSubmit="return false">

                    <textarea style="display: none"  id="description" name="description"></textarea>

                    {{ csrf_field() }}

                    <div class="card-body border-top">

                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Asunto</label>
                                        <input type="text" class="form-control" id="subject"  name="subject" value="" placeholder="Ingresar un asunto">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Categoria</label>
                                    <div class="input-group">
                                        {!! Form::select('categorie', $categories, null , ['class' => 'select2 form-control','id' => 'categorie']) !!}
                                    </div>
                                    <label id="categorie-error" class="error d-none" for="categorie"></label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Descripción</label>

                                    <div class="quill-wrapper">
                                        <div  id="descriptions"></div>
                                    </div>
                                    <label id="description-error" class="error d-none" for="description"></label>
                                </div>
                            </div>


                        </div>

                    </div>


                     <div class="col-12"><div class="action-form border-top">
                        <div class="text-center">
                            <button type="submit" class="btn btn-info rounded-pill px-4 waves-effect waves-light">
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


            $("#formTikects").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    subject: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    description: {
                        required: false,
                        minlength: 0,
                        maxlength: 2000,
                    },
                    categorie: {
                        required: true,
                    }
                },
                messages: {
                    subject: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    categorie: {
                        required: "Es necesario un opción.",
                    },
                    description: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 2000 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formTickets');
                    var formData = new FormData($form[0]);
                    var subject = $("#subject").val();
                    var categorie = $("#categorie").val();
                    var description = $("#description").val();

                    formData.append('subject', subject);
                    formData.append('categorie', categorie);
                    formData.append('description', description);

                    $.ajax({
                        url: "{{ route('customer.tickets.store') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function() {
                            location.href = "{{route('customer.tickets')}}";
                        }
                    });

                }

            });



        });

    </script>


    <script type="text/javascript">



        var toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'script': 'sub' }, { 'script': 'super' }],
            [ 'link', 'image' ],
            ['clean']
        ];


        var toolbarOption = [
            ['clean']
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
            $('#description').text(description.container.firstChild.innerHTML);
        });


    </script>

@endpush






