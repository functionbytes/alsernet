
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-arrange @if($shop->generate_location) generatelocation location @endif">
            <div class="card ">
                <div class="card-body text-center">
                    @if(!$shop->generate_location)
                        <input type="text" id="location" name="location"  autofocus>
                    @endif
                    <input type="hidden" id="shop" name="shop"  value="{{$shop->uid}}">
                    <input type="hidden" id="inventarie" name="inventarie"  value="{{$inventarie->uid}}" >
                    <p>OPCION</p>
                    <i class="fa-duotone fa-solid fa-rectangle-barcode"></i>
                    <h5 class="fw-semibold fs-5 mb-2">Leer codigo de barras de la ubiacion</h5>
                    <p class="mb-3 px-xl-5">Acercalo al lector</p>
                </div>
            </div>
    </div>
@endsection


@push('scripts')

    <script type="text/javascript">

        $(document).ready(function() {

            $("#location").on('input', function() {

                var location = $(this).val();

                if (location !== '') {
                    $.ajax({
                        url: "{{ route('inventarie.inventarie.location.validate.location') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        data: {
                            location: location
                        },
                        success: function(response) {

                            if (response.success) {

                                let slack = response.slack;

                                let inventarie = $("#inventarie").val();  // Segundo parámetro

                                let url = "{{ route('inventarie.inventarie.location.validate.modalitie', [':slack', ':inventarie']) }}"
                                    .replace(':slack', slack)
                                    .replace(':inventarie', inventarie);

                                window.location.href = url;


                            } else {

                                $("#location").val('');
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
            });


            $(".generatelocation.location").on('click', function() {
                    var location = $("#location").val();  // Make sure to get the correct value
                    var shop = $("#shop").val();  // Make sure to get the correct value

                    $.ajax({
                        url: "{{ route('inventarie.inventarie.location.validate.validate') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        data: {
                            shop: shop,
                            location: location
                        },
                        success: function(response) {
                            if (response.success) {
                                let slack = response.slack;
                                let inventarie = $("#inventarie").val();  // Get the second parameter
                                let url = "{{ route('inventarie.inventarie.location.validate.modalitie', [':slack', ':inventarie']) }}"
                                    .replace(':slack', slack)
                                    .replace(':inventarie', inventarie);

                                window.location.href = url;
                            } else {
                                $("#location").val('');
                                let errorSound = new Audio("/inventaries/sound/error.mp3");
                                errorSound.play();

                                setTimeout(function() {
                                    errorSound.pause();
                                    errorSound.currentTime = 0;
                                }, 400);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX error:", status, error);  // Handle errors if any
                        }
                    });
                });

        });
    </script>




@endpush
