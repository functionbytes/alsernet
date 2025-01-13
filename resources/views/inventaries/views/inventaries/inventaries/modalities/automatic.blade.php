
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-content">

            <input type="hidden" id="inventarie" name="inventarie"  value="{{$inventarie->slack}}">
            <input type="hidden" id="item" name="item"  value="{{$item->slack}}">
            <input type="hidden" id="location" name="location"  value="{{$location->slack}}">
            <input type="text" id="product" name="product"  autofocus >


        <div class="card w-100">
            <div class="card-body">
                <h4 class="card-title fw-semibold">Ubicacion : {{$location->title}}</h4>
                <p class="card-subtitle mb-3">Validacion de inventario en ubicacion</p>

                <div class="position-relative border-top pb-3">
                    <div id="product-list"></div>
                </div>

                <button class="btn btn-secondary me-1 w-100 " id="sendLocations">ENVIAR</button>
                <button class="btn btn-primary me-1 w-100 mt-2" id="deleteProducts">BORRAR</button>
            </div>
        </div>

    </div>

@endsection



@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {

            $("#product").show().focus();

            function getCookie(name) {

                let cookieArr = document.cookie.split(';');
                for (let i = 0; i < cookieArr.length; i++) {
                    let cookie = cookieArr[i].trim();
                    // Verifica si la cookie comienza con el nombre buscado
                    if (cookie.startsWith(name + "=")) {
                        return decodeURIComponent(cookie.substring(name.length + 1));
                    }
                }
                return null; // Si la cookie no se encuentra
            }

            function deleteCookie(name) {
                document.cookie = name + '=; Max-Age=0; path=/';
            }

            function renderProductList(products) {

                let productList = $('#product-list');
                productList.empty();
                products.reverse();

                let tableHtml = `
                    <table class="table table-bordered ">
                        <thead>
                            <tr>
                                <th>Referencia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                products.forEach(function(product, index) {
                    tableHtml += `
                        <tr data-index="${index}">
                            <td>${product.reference}</td>
                            <td>
                                <button class="btn btn-danger btn-sm remove-product-btn">Eliminar</button>
                            </td>
                        </tr>
                    `;
                });

                tableHtml += '</tbody></table>';

                productList.append(tableHtml);

                $('.remove-product-btn').on('click', function() {
                    let row = $(this).closest('tr');
                    let index = row.data('index');
                    products.splice(index, 1);
                    setCookie(location, JSON.stringify(products), 1);
                    renderProductList(products);
                });
            }


            function setCookie(name, value, days) {
                var expires = "";
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
            }

            function saveArrayToCookie(name, array, days) {
                var jsonString = JSON.stringify(array);
                setCookie(name, jsonString, days);
            }

            function getArrayFromCookie(name) {
                var cookieValue = getCookie(name);
                if (cookieValue) {
                    return JSON.parse(cookieValue);
                } else {
                    return [];
                }
            }

            var productsList = [];

            let location = $("#location").val();

            var storedValue = getCookie(location);

            if (storedValue) {
                renderProductList(JSON.parse(storedValue));
            } else {
            }

            $("#product").on('input', function() {

                let product = $(this).val();
                let location = $("#location").val();


                if (product !== '') {


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

                                var products = getArrayFromCookie(location);

                                var productValue = response.product;

                                if (typeof productValue === 'string') {
                                    productValue = JSON.parse(productValue);
                                }

                                products.push({
                                    reference: productValue.reference,
                                    barcode: productValue.barcode,
                                    id: productValue.id,
                                    slack: productValue.slack
                                });

                                setCookie(location, JSON.stringify(products), 1);
                                renderProductList(products);
                                $('#product').val('');
                                $('#product').focus();
                            } else {
                                $("#product").val('');
                                $('#product').focus();
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
            });

            $('#deleteProducts').on('click', function() {
                let location = $("#location").val();
                setCookie(location, JSON.stringify([]), 1);
                renderProductList([]);
            });

            $('#sendLocations').on('click', function() {

                var location = $("#location").val();
                var item = $("#item").val();
                var products = getCookie(location);

                try {
                    products = JSON.parse(products);
                } catch (e) {
                    products = [];
                }

                if (products.length <= 1) {

                    if (/Mobi|Android/i.test(navigator.userAgent)) {

                        $("#product").blur();

                        setTimeout(function() {
                            $("#product").focus();
                        }, 200);
                    } else {

                        $("#product").val('');
                        $('#product').focus();
                    }

                    let errorSound = new Audio("/inventaries/sound/error.mp3");
                    errorSound.play();

                    //toastr.warning("Se ha generado un error.", "Operación fallida", {
                    //    closeButton: true,
                    //    progressBar: true,
                    //    positionClass: "toast-bottom-right"
                    //});

                    return;

                }

                $.ajax({
                    url: "{{ route('inventarie.inventarie.location.close') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "POST",
                    data: {
                        modalitie: "automatic",
                        products: products,
                        item: item,
                        location: location
                    },
                    success: function(response) {
                        if (response.success) {

                            $('#product-list').empty();
                            let location = $("#location").val();
                            deleteCookie(location);
                            let inventarie = $("#inventarie").val();  // Segundo parámetro
                            let url = "{{ route('inventarie.inventarie.arrange', [':inventarie']) }}".replace(':inventarie', inventarie);
                            window.location.href = url;

                        }
                    }
                });
            });

        });
    </script>
@endpush
