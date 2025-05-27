@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formRates" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input  id="slack" name="slack" type="hidden" value="{{ $distributor->uid }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0"> Tarifas de cursos</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="mb-4 row align-items-center">
                            <div class="table-responsive table-bussiness-hours">
                                <table class="table card-table table-vcenter text-nowrap mb-0">
                                    <thead>
                                    <tr class="">
                                        <th class="w-20 border-bottom-0">Cursos</th>
                                        <th class="w-20 border-bottom-0">Valor</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    @foreach($rates as $rate)
                                        <tr class="border-bottom-transparent">
                                            <td class="">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="fw-semibold mb-1">{{ $rate->course->title }}</h6>
                                                        <p class="fs-2 mb-0 text-muted">{{ $rate->course->categorie->title }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="">
                                                <input type="text" class="form-control" id="courses"  name="courses[{{ $rate->id }}]"   value="{{ $rate->price }}" placeholder="Ingresar precio">
                                            </td>
                                        </tr>
                                    @endforeach

                                    </tbody>
                                </table>
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

            $("#formRates").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    'courses[]': {
                        required: true,
                        number: true,
                        min: 0
                    },
                },
                messages: {
                    'courses[]': {
                        required: "Es necesario ingresar un precio.",
                        number: "Debe ser un número.",
                        min: "El precio no puede ser negativo."
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formRates');
                    var formData = new FormData($form[0]);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);


                    $.ajax({
                        url: "{{ route('callcenter.distributors.rates.update') }}",
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
                                    let slack = @json($distributor->uid);
                                    window.location.href = "{{ route('callcenter.distributors.navegation', ':slack') }}".replace(':slack', slack);
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

