
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-content">

        <input type="hidden" id="inventarie" name="inventarie"  value="{{$inventarie->uid}}">
        <input type="hidden" id="item" name="item"  value="{{$item->uid}}">
        <input type="hidden" id="location" name="location"  value="{{$location->uid}}">

        <div class="card w-100">
            <div class="card-body">
                <h4 class="card-title fw-semibold">Ubicacion : {{$location->title}}</h4>
                <p class="card-subtitle mb-3">Validacion de inventario en ubicacion</p>

                <div class="position-relative border-top pb-3 pt-3 row ">

                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label">Codigo de barras <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="barcode" name="barcode" autofocus>
                            <p class="fs-2">Ingresar el codigo de barras</p>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label">Cantidad <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="count" name="count" >
                            <p class="fs-2">Ingresar la cantidad</p>
                        </div>
                    </div>


                </div>

                <button class="btn btn-secondary me-1 w-100" id="sendLocations">ENVIAR</button>
                <button class="btn btn-primary me-1 w-100 mt-2" id="deleteProducts">BORRAR</button>
            </div>
        </div>

    </div>

@endsection


@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {

            const cookie = {
                get: function(name) {
                    const cookieArr = document.cookie.split(';');
                    for (let i = 0; i < cookieArr.length; i++) {
                        let cookie = cookieArr[i].trim();
                        if (cookie.startsWith(name + "=")) {
                            return decodeURIComponent(cookie.substring(name.length + 1));
                        }
                    }
                    return null;
                },
                set: function(name, value, days) {
                    let expires = "";
                    if (days) {
                        let date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        expires = "; expires=" + date.toUTCString();
                    }
                    document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
                },
                delete: function(name) {
                    document.cookie = name + '=; Max-Age=0; path=/';
                }
            };

            function saveArrayToCookie(name, array, days) {
                cookie.set(name, JSON.stringify(array), days);
            }

            function getArrayFromCookie(name) {
                const cookieValue = cookie.get(name);
                return cookieValue ? JSON.parse(cookieValue) : [];
            }

            let location = $("#location").val();
            let storedValue = cookie.get(location);

            if (storedValue) {
                renderProduct(JSON.parse(storedValue));
            }

            function renderProduct(product) {
                const { reference, barcode, id, slack, count } = product;
                $("#barcode").val(barcode);
                $("#count").val(count);
            }

            // Handle product input
            $("#barcode").on('input', function() {

                let product = $(this).val();
                let location = $("#location").val();

                if (product !== '') {

                    if (product.startsWith('100')) {

                        product = product.slice(0, -1);

                        $.ajax({
                            url: "{{ route('inventarie.inventarie.location.validate.product') }}",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            type: "POST",
                            data: {
                                location: location,
                                product: product
                            },
                            success: function(response) {

                                if (response.success) {

                                    let productList = getArrayFromCookie(location);
                                    let productValue = response.product;

                                    if (typeof productValue === 'string') {
                                        productValue = JSON.parse(productValue);
                                    }

                                    productList.push({
                                        reference: productValue.reference,
                                        barcode: productValue.barcode,
                                        id: productValue.id,
                                        slack: productValue.slack,
                                        count: 1
                                    });

                                    saveArrayToCookie(location, productList, 1);
                                    $('#count').focus();

                                    let errorSound = new Audio("/inventaries/sound/checks.mp3");
                                    errorSound.play();

                                    setTimeout(function() {
                                        errorSound.pause();
                                        errorSound.currentTime = 0; // Reiniciar el sonido a su inicio
                                    }, 400);

                                } else {
                                    $("#barcode").val('');
                                    $("#count").val('');
                                    $('#barcode').focus();
                                    let errorSound = new Audio("/inventaries/sound/error.mp3");
                                    errorSound.play();

                                    setTimeout(function() {
                                        errorSound.pause();
                                        errorSound.currentTime = 0; // Reiniciar el sonido a su inicio
                                    }, 400);

                                }
                            }
                        });
                    }
                }
            });

            $('#count').on('input', function() {
                let count = $(this).val();
                let location = $("#location").val();
                let productBarcode = $("#barcode").val();

                if (productBarcode !== '' && count !== '') {
                    let productList = getArrayFromCookie(location);
                    let productIndex = productList.findIndex(product => product.barcode === productBarcode);
                    if (productIndex !== -1) {
                        productList[productIndex].count = count;
                        saveArrayToCookie(location, productList, 1);
                    }
                }
            });

            $('#deleteProducts').on('click', function() {
                $("#barcode").val('');
                $("#count").val('');
            });

            function deleteCookie(name) {
                document.cookie = name + '=; Max-Age=0; path=/';
            }

            // Handle sending locations
            $('#sendLocations').on('click', function() {
                let location = $("#location").val();
                let item = $("#item").val();
                let product = $("#barcode").val();
                let count = $("#count").val();

                $.ajax({
                    url: "{{ route('inventarie.inventarie.location.close') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "POST",
                    data: {
                        modalitie: "manual",
                        count: count,
                        product: product.slice(0, -1),
                        item: item,
                        location: location
                    },
                    success: function(response) {
                        if (response.success) {
                            cookie.set(location, JSON.stringify([]), 1); // Clear the cookie after sending
                            deleteCookie(location);
                            $("#barcode").val('');
                            $("#count").val('');
                            $('#barcode').focus();

                            let inventarie = $("#inventarie").val();
                            let url = "{{ route('inventarie.inventarie.arrange', [':inventarie']) }}".replace(':inventarie', inventarie);
                            window.location.href = url;
                        }
                    }
                });
            });

        });
    </script>
@endpush
