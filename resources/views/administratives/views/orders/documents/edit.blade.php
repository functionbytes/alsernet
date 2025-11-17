@extends('layouts.administratives')

@section('content')

    <div class="documents-status">
        <div class="row">
            @if($document->upload_at!=null && $document->media->count()>0)
                <div class="col-md-12 mb-3">
                    <a href="{{ route('administrative.documents.summary', $document->uid) }}" target="_blank" class="card item-status h-100">
                        <div class="card-body text-center">
                            <div class="my-4">
                                <i class="fa-solid fa-wallet font-navegation fs-3x"></i>
                            </div>
                            <h4 class="fw-bolder text-uppercase mb-3">VER DOCUMENTO</h4>
                        </div>
                    </a>
                </div>
            @endif

        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">



                <form id="formDocuments" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="uid" name="uid" value="{{ $document->uid }}">

                    <div class="card-body border-top">


                        @if($products->count())
                            <div class="d-flex no-block align-items-center">
                                <h5 class="mb-0">Listado producto </h5>

                            </div>
                            <p class="card-subtitle mb-3 mt-3">
                                Listado de productos relacionados con la orden.
                            </p>

                        <div class="list-products-container">

                                @foreach($products as $item)

                                    <div class="bundle-block mb-3" >
                                        <ul class="list-group list-group-flush">

                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    {{ $item->product->name }}
                                                    <span class="badge bg-success">{{ $item->quantity}} ud</span>
                                                </li>
                                        </ul>
                                    </div>
                                @endforeach
                        </div>

                        @endif


                            <hr class="mt-2 mb-4">

                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Detalle orden </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Listado de productos relacionados con la orden.
                        </p>


                        <div class="row">
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Orden</label>
                                <input type="text" class="form-control"  name="firstname" value="{{$order->id_order}}" placeholder="Ingresar nombres"  autocomplete="new-password" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Referencia</label>
                                <input type="text" class="form-control"  name="firstname" value="{{$order->reference}}" placeholder="Ingresar nombres"  autocomplete="new-password" disabled>
                            </div>
                        </div>

                        <hr class="mt-2 mb-4">

                            <div class="d-flex no-block align-items-center">
                                <h5 class="mb-0">Informacion cliente </h5>

                            </div>
                            <p class="card-subtitle mb-3 mt-3">
                                Listado de productos relacionados con la orden.
                            </p>
                            <div class="row">
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Nombres</label>
                                <input type="text" class="form-control"  name="firstname" value="{{$customer->firstname}}" placeholder="Ingresar nombres"  autocomplete="new-password" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Apellidos</label>
                                <input type="text" class="form-control" name="lastname" value="{{$customer->lastname}}" placeholder="Ingresar apellidos" autocomplete="new-password" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">DNI/NIE/CIF</label>
                                <input type="text" class="form-control" name="identification" value="{{$invoice?->dni}}" placeholder="Ingresar la identificacion" autocomplete="new-password" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Correo electronico</label>
                                <input type="text" class="form-control" name="email" value="{{$customer->email}}" placeholder="Ingresar el correo electronico" autocomplete="new-password" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-12 mb-3">
                                <label class="control-label col-form-label">Gestionado</label>
                                <select class="form-control select2" id="proccess" name="proccess" >
                                    <option value="0" {{ $document->proccess == 0 ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ $document->proccess == 1 ? 'selected' : '' }}>Si</option>
                                </select>
                                <label id="proccess-error" class="error" for="proccess" style="display: none"></label>
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

        $(document).ready(function() {


            $("#formDocuments").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    proccess: {
                        required: true,
                    },
                },
                messages: {
                    proccess: {
                        required: "Es necesario un estado.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formDocuments');
                    var formData = new FormData($form[0]);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('administrative.documents.update') }}",
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
                                    positionClass: "toast-bottom-right",
                                    timeOut: 1000,
                                    onHidden: function () {
                                        window.location = "{{ route('administrative.documents') }}";
                                    }
                                });


                            }else{

                                $submitButton.prop('disabled', false);
                                error = response.message;

                                toastr.warning(error, "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right",
                                    timeOut: 1000,
                                    onHidden: function () {
                                        $('.errors').text(error);
                                        $('.errors').removeClass('d-none');
                                    }
                                });


                            }

                        }
                    });

                }

            });

        });

    </script>


@endpush



