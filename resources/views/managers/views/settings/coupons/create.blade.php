@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formCoupons" enctype="multipart/form-data" role="form" onSubmit="return false">


                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="">
                    <input type="hidden" id="slack" name="slack" value="">
                    <textarea style="display: none"  id="description" name="description"></textarea>

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Crear cupon</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para permitirte  <mark><code>introducir</code></mark> nueva información de manera sencilla y estructurada. A continuación, se presentan varios campos que deberás completar con los datos requeridos.
                        </p>
                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Titulo</label>
                                        <input type="text" class="form-control" id="title"  name="title" value=""  placeholder="Ingresar titulo">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Coupon</label>
                                        <input type="text" class="form-control" id="code"  name="code" value=""  placeholder="Ingresar codigo">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Precio minimo</label>
                                        <input type="text" class="form-control" id="min_price"  name="min_price" value=""  placeholder="Ingresar precio minimo">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Monto</label>
                                        <input type="text" class="form-control" id="amount"  name="amount" value=""  placeholder="Ingresar precio minimo">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Uso maximo</label>
                                        <input type="text" class="form-control" id="limit"  name="limit" value=""  placeholder="Ingresar precio minimo">
                                </div>
                            </div>



                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tipo</label>
                                    <div class="input-group">
                                        {!! Form::select('type', $types, null , ['class' => 'select2 form-control','id' => 'type']) !!}
                                    </div>
                                    <label id="type-error" class="error d-none" for="type"></label>
                                </div>
                            </div>


                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('available', $availables, null , ['class' => 'select2 form-control','id' => 'available']) !!}
                                    </div>
                                    <label id="available-error" class="error d-none" for="available"></label>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Fecha</label>
                                    <div class="input-group">
                                        <input class="form-control date-range-picker date-range" type="text" placeholder="Fecha de inicio - Fecha de término" name="date_range" >
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Cursos</label>
                                    <div class="input-group">
                                        {!! Form::select('courses[]', $courses, null, ['class' => 'select2 form-control'  , 'multiple' => 'multiple' , 'id' => 'courses']) !!}
                                    </div>
                                    <label id="courses-error" class="error d-none" for="courses"></label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Paquetes</label>
                                    <div class="input-group">
                                        {!! Form::select('bundles[]', $bundles, null, ['class' => 'select2 form-control'  , 'multiple' => 'multiple' , 'id' => 'bundles']) !!}
                                    </div>
                                    <label id="bundles-error" class="error d-none" for="bundles"></label>
                                </div>
                            </div>
                       

                     <div class="col-12">
                                <div class="mb-3">
                        <label class="col-form-label">Descripción</label>
                        <div class="quill-wrapper">
                            <div  id="descriptions"></div>
                        </div>
                        <label id="description-error" class="error d-none" for="description"></label>
                    </div>
                    </div>

                     <div class="col-12"><div class="action-form border-top mt-4">
                        <div class="text-center">
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


            $(".date-picker").flatpickr({enableTime:!0});

            $(".date-range-picker").each(function(el) {
                var $this = $(this);
                var options = {
                    mode: "range",
                    showMonths: 2,
                    dateFormat: 'm/d/Y'
                };
                $this.flatpickr(options);
            });

            $("#formCoupons").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    title: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    code: {
                        required: true,
                        minlength: 6,
                        maxlength: 6,
                    },
                    limit: {
                        required: true,
                        number: true,
                        min: 1,
                        max: 999999,
                    },
                    price: {
                        required: true,
                        number: true,
                        min: 1,
                        max: 999999,
                    },
                    amount: {
                        required: true,
                        number: true,
                        min: 1,
                        max: 999999,
                    },
                    min_price: {
                        required: true,
                        number: true,
                        min: 1,
                        max: 999999,
                    },
                    description: {
                        required: false,
                        minlength: 0,
                        maxlength: 2000,
                    },
                    type: {
                        required: true,
                    },
                    available: {
                        required: true,
                    },
                    'bundles[]': {
                        required: false,
                    },
                    'courses[]': {
                        required: false,
                    },
                    date_var: {
                        required: true,
                    },
                },
                messages: {
                    title: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    code: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 6 caracter",
                        maxlength: "Debe contener al menos 6 caracter",
                    },
                    price: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 999999 caracter",
                    },
                    limit: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 999999 caracter",
                    },
                    amount: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 999999 caracter",
                    },
                    min_price: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 999999 caracter",
                    },
                    available: {
                        required: "Es necesario un opción.",
                    },
                    type: {
                        required: "Es necesario un opción.",
                    },
                    courses: {
                        required: "Es necesario un opción.",
                    },
                    bundles: {
                        required: "Es necesario un opción.",
                    },
                    date_var: {
                        required: "Es necesario un opción.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formCoupons');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var title = $("#title").val();
                    var description = $("#description").val();
                    var code = $("#code").val();
                    var type = $("#type").val();
                    var amount = $("#amount").val();
                    var bundles = $("#bundles").val();
                    var courses = $("#courses").val();
                    var min_price = $("#min_price").val();
                    var date_var = $("#date_var").val();
                    var available = $("#available").val();
                    var limit = $("#limit").val();

                    formData.append('slack', slack);
                    formData.append('title', title);
                    formData.append('description', description);
                    formData.append('code', code);
                    formData.append('type', type);
                    formData.append('amount', amount);
                    formData.append('date_var', date_var);
                    formData.append('min_price', min_price);
                    formData.append('available', available);
                    formData.append('limit', limit);
                    formData.append('courses', courses);
                    formData.append('bundles', bundles);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.coupons.store') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.status == true){

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location = "{{ route('manager.coupons') }}";
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





