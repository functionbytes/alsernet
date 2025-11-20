
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-arrange">
            <div class="card">
                <div class="card-body text-center">

                    <input type="text" id="section" name="section" autofocus>
                    <input type="hidden" id="warehouse" name="warehouse" value="{{$warehouse->uid}}" >
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

            $("#section").on('input', function() {

                var section = $(this).val();
                console.log(section);

                if (section !== '') {
                    $.ajax({
                        url: "{{ route('warehouse.warehouses.location.validate.section') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        data: {
                            section: section
                        },
                        success: function(response) {

                            if (response.success) {
                                let location = response.location;
                                let section  = response.section;
                                let warehouse = response.warehouse;

                                let url = "{{ route('warehouse.warehouses.location.location.section', [':warehouse', ':location', ':section']) }}"
                                    .replace(':warehouse', encodeURIComponent(warehouse))
                                    .replace(':location', encodeURIComponent(location))
                                    .replace(':section', encodeURIComponent(section));

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

        });
    </script>




@endpush
