
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-content">

            <input type="hidden" id="inventarie" name="inventarie"  value="{{$inventarie->uid}}">
            <input type="hidden" id="item" name="item"  value="{{$item->uid}}">
            <input type="hidden" id="location" name="location"  value="{{$location->uid}}">
            <input type="text" id="product" name="product"  autofocus >


        <div class="card w-100">
            <div class="card-body">
                <h4 class="card-title fw-semibold">Ubicacion : {{$location->title}}</h4>
                <p class="card-subtitle mb-3">Validacion de inventario en ubicacion</p>

                <div class="position-relative border-top pt-3 pb-3">
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

                        // Función para guardar productos en localStorage
            function saveArrayToLocalStorage(name, array) {
                localStorage.setItem(name, JSON.stringify(array));
            }

            // Función para obtener productos del localStorage
            function getArrayFromLocalStorage(name) {
                let storedValue = localStorage.getItem(name);
                return storedValue ? JSON.parse(storedValue) : [];
            }

            // Función para generar un ID único para cada producto
            function generateUniqueId() {
                return 'product_' + Math.random().toString(36).substr(2, 9); // Genera un ID aleatorio
            }

            // Función para manejar el input del producto
            $("#product").on('input', function() {

                let product = $(this).val();
                let location = $("#location").val();

                if (product != '') {

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

                                    var products = getArrayFromLocalStorage(location); // Obtener productos del localStorage

                                    var productValue = response.product;

                                    if (typeof productValue === 'string') {
                                        productValue = JSON.parse(productValue);
                                    }

                                    // Asignar un ID único al producto
                                    productValue.uniqueId = generateUniqueId();

                                    // Agregar el producto sin validar duplicados
                                    products.push({
                                        reference: productValue.reference,
                                        barcode: productValue.barcode.slice(0, -1), // Eliminar el último carácter del barcode
                                        id: productValue.id,
                                        slack: productValue.slack,
                                        uniqueId: productValue.uniqueId // Asignar el ID único
                                    });

                                    // Guardar el listado actualizado en localStorage
                                    saveArrayToLocalStorage(location, products);

                                    // Renderizar la lista de productos actualizada
                                    renderProductList(products);
                                    $('#product').val('');
                                    $('#product').focus();

                                    let errorSound = new Audio("/inventaries/sound/check.mp3");
                                    errorSound.play();

                                    setTimeout(function() {
                                        errorSound.pause();
                                        errorSound.currentTime = 0; // Reiniciar el sonido a su inicio
                                    }, 400);

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
                    }else{
                        let errorSound = new Audio("/inventaries/sound/error.mp3");
                                    errorSound.play();

                                    setTimeout(function() {
                                        errorSound.pause();
                                        errorSound.currentTime = 0; // Reiniciar el sonido a su inicio
                                    }, 400);
                    }
                }
            });

            // Renderizar la lista de productos desde localStorage
            // Renderizar la lista de productos desde localStorage
            function renderProductList(products, option = null) {
    let productList = $('#product-list');
    productList.empty(); // Limpiar la lista de productos antes de renderizar

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

    // Si option es null, renderizar los productos tal cual
    if (option == null) {
        products.forEach(function(product) {
            tableHtml += `
                <tr data-id="${product.uniqueId}"> <!-- Usar uniqueId para identificar el producto -->
                    <td>${product.reference}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-product-btn">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    } else {
        // Si option no es null, invertir el orden de los productos
        products.reverse().forEach(function(product) {
            tableHtml += `
                <tr data-id="${product.uniqueId}"> <!-- Usar uniqueId para identificar el producto -->
                    <td>${product.reference}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-product-btn">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    }

    tableHtml += '</tbody></table>';

    productList.append(tableHtml);

    // Asociar el evento de eliminación a cada botón de eliminación
    $('.remove-product-btn').on('click', function() {
        // Encontrar el ID del producto correspondiente al botón de eliminación
        let row = $(this).closest('tr');
        let uniqueId = row.data('id'); // Obtener el uniqueId del producto seleccionado

        // Mostrar un popup de confirmación
        let confirmDelete = confirm("¿Estás seguro de que quieres eliminar este producto?");

        if (confirmDelete) {
            // Eliminar el producto correspondiente utilizando el ID único
            products = products.filter(function(product) {
                return product.uniqueId !== uniqueId;
            });

            // Guardar la lista actualizada en localStorage
            saveArrayToLocalStorage(location, products);

            // Renderizar la lista actualizada
            renderProductList(products, 'id');
        }
    });
}


            // Cargar productos desde localStorage al iniciar
            let location = $("#location").val();
            let storedProducts = getArrayFromLocalStorage(location);
            renderProductList(storedProducts);


            $('#deleteProducts').on('click', function() {
                let location = $("#location").val(); // Obtener la ubicación
                localStorage.removeItem(location); // Eliminar los productos de localStorage para esa ubicación

                // Limpiar la lista visualmente
                renderProductList([]);
            });

            $('#sendLocations').on('click', function() {
                var location = $("#location").val();
                var item = $("#item").val();

                // Retrieve and parse products from localStorage
                var products = getArrayFromLocalStorage(location);

                try {
                    products = JSON.stringify(products);
                } catch (e) {
                    products = [];
                }

                // Check if products array is empty
                if (products.length === 0) {

                    if (/Mobi|Android/i.test(navigator.userAgent)) {
                        $("#product").blur();
                        setTimeout(function() {
                            $("#product").focus();
                        }, 200);
                    } else {
                        $("#product").val('');
                        $('#product').focus();
                    }

                    let errorSound = new Audio("/inventaries/sound/checks.mp3");
                                    errorSound.play();

                                    setTimeout(function() {
                                        errorSound.pause();
                                        errorSound.currentTime = 0; // Reiniciar el sonido a su inicio
                                    }, 400);

                    // Optional: Show a toastr warning for better UX
                    // toastr.warning("Se ha generado un error.", "Operación fallida", {
                    //     closeButton: true,
                    //     progressBar: true,
                    //     positionClass: "toast-bottom-right"
                    // });

                    return;
                }

                // Perform AJAX request to close the location
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
                            // Clear product list and remove the location from localStorage
                            $('#product-list').empty();
                            localStorage.removeItem(location);

                            // Redirect to the arranged inventory page
                            let inventarie = $("#inventarie").val();
                            let url = "{{ route('inventarie.inventarie.arrange', [':inventarie']) }}".replace(':inventarie', inventarie);
                            window.location.href = url;
                        } else {
                            // Handle any error on the server side
                            alert("Error: " + response.message);
                        }
                    }
                });
            });



        });
    </script>
@endpush
