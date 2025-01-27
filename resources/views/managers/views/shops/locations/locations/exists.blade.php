
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-arrange">

    <div class="row justify-content-center align-items-center">
            <div class="col-md-6">
                <div class="card">
                        <div class="card-body text-center">
                            <input type="hidden" id="shop" name="shop"  value="{{ $shop->uid }}" >
                            <p>OPCION</p>
                            <i class="fa-duotone fa-solid fa-rectangle-barcode"></i>
                            <input type="text" class="form-control pl-4 pr-4 mt-2 mb-2" id="location" name="location"  autofocus>
                            <h5 class="fw-semibold fs-5 mb-2">Leer codigo de barras de la ubiacion</h5>
                            <p class="mb-3 px-xl-5">Acercalo al lector</p>
                        </div>
                    </div>
            </div>
        </div>
    </div>

@endsection


@push('scripts')

    <script type="text/javascript">

        $(document).ready(function() {

            $("#location").on('keypress', function(e) {
                if (e.which === 13) {  // 13 es el código de la tecla Enter
                    var location = $(this).val();
                var shop = $("#shop").val();

                if (location !== '' && location.includes('-')) {
                    $.ajax({
                        url: "{{ route('manager.shops.locations.exists.validate') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        data: {
                            location: location,
                            shop: shop
                        },
                        success: function(response) {

                            if (response.success) {

                                //let slack = $("#shop").val();
                               // let url = "{{ route('manager.shops.locations', [':slack', ':inventarie']) }}".replace(':slack', slack);

                               // window.location.href = url;

                                $("#location").val('');
                                $("#location").blur();

                                setTimeout(function() {
                                    $("#product").focus();
                                }, 200);

                            } else {

                                $("#location").val('');
                                $("#location").blur();

                                setTimeout(function() {
                                    $("#product").focus();
                                }, 200);

                                let errorSound = new Audio("/inventaries/sound/error.mp3");
                                errorSound.play();

                                setTimeout(function() {
                                    errorSound.pause();
                                    errorSound.currentTime = 0;
                                }, 400);

                            }
                        },
                    });
                }
                }
            });

        });
    </script>




@endpush
