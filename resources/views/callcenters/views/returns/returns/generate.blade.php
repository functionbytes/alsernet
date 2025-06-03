@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formReturns" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" name="return_id" id="return_id" value="{{$return->id_return_request}}">
                    <input type="hidden" name="order_id" id="order_id" value="{{$order->id}}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0 uppercase">Crear devolución - Orden #{{$order->order_number}}</h5>
                        </div>

                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para gestionar las devoluciones de productos. A continuación, encontrarás diversos <mark><code>campos</code></mark> que te permitirán seleccionar los productos a devolver y especificar las razones correspondientes.
                        </p>

                        <!-- Validaciones y advertencias -->
                        @if(!$validation['can_proceed'])
                            <div class="alert alert-danger mb-3">
                                <h6>No se puede proceder con la devolución:</h6>
                                <ul class="mb-0">
                                    @foreach($validation['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(!empty($validation['warnings']))
                            <div class="alert alert-warning mb-3">
                                <h6>Advertencias:</h6>
                                <ul class="mb-0">
                                    @foreach($validation['warnings'] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <!-- Información del cliente -->
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">Nombres</label>
                                <input type="text" class="form-control" value="{{$customer->firstname}}" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">Apellidos</label>
                                <input type="text" class="form-control" value="{{$customer->lastname}}" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">DNI/NIE/CIF</label>
                                <input type="text" class="form-control" value="{{$customer->identification}}" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">Correo electronico</label>
                                <input type="text" class="form-control" value="{{$customer->email}}" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">Telefono</label>
                                <input type="text" class="form-control" value="{{$customer->cellphone}}" disabled>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">Fecha del pedido</label>
                                <input type="text" class="form-control" value="{{ $order->created_at->format('d/m/Y') }}" disabled>
                            </div>

                            <hr class="mb-4 mt-3">

                            <div class="d-flex flex-column mb-3">
                                <h5 class="mb-0 text-uppercase fw-semibold">Productos a devolver</h5>
                                <p class="card-subtitle text-muted mt-2">
                                    Selecciona los productos que deseas devolver, especificando la cantidad, razón y condición de cada uno.
                                </p>

                                <div class="col-12 mb-3">
                                    <button type="button" class="btn btn-primary" id="addProductBtn" data-bs-toggle="modal" data-bs-target="#selectProductModal">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="list-products-container">
                                <div id="selectedProductsList">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No se han seleccionado productos para devolver.
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen total -->
                            <div id="return-summary" class="mt-3 d-none">
                                <div class="container-installment p-2 small alert alert-danger">
                                    Total a devolver: <strong id="totalRefundAmount">0.00€</strong>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="control-label col-form-label">Observaciones adicionales para esta devolución</label>
                                <textarea type="text" class="form-control" name="notes" autocomplete="new-password"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="errors mb-3 d-none">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border-top pt-1 mt-2">
                                    <button type="button" id="validateBtn" class="btn  btn-info   px-4 waves-effect waves-light mt-2 w-100" disabled>
                                         Validar devolución
                                    </button>
                                    <button type="submit" id="submitBtn" class="btn btn-primary px-4 waves-effect waves-light mt-2 w-100" disabled>
                                         Crear devolución
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Modal para seleccionar productos -->
    <div id="selectProductModal" class="modal fade select-product">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seleccionar productos para devolver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">

                    <div class="table-responsive border rounded">
                        <table class="table align-middle text-nowrap mb-0">
                            <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Producto</th>
                                <th width="120">Precio Unit.</th>
                                <th width="100">Cantidad</th>
                                <th width="100">Ya devueltas</th>
                                <th width="120">A devolver</th>
                                <th width="200">Razón</th>
                                <th width="180">Condición</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($returnableProducts as $product)

                                <tr data-product-id="{{ $product['product_id'] }}">

                                    <td>
                                        <input type="checkbox" class="product-checkbox"
                                               value="{{ $product['product_id'] }}"
                                               data-name="{{ $product['name'] }}"
                                               data-max="{{ $product['available_to_return'] }}"
                                               data-price="{{ $product['unit_price'] }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-0 ">{{ $product['name'] }}</p>
                                                <span class="mt-0 mb-0">{{ $product['description'] }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <h6 class="mb-0">${{ number_format($product['unit_price'], 2) }}€</h6>
                                    </td>
                                    <td>
                                        <p class="mb-0">{{ $product['ordered_quantity'] }}</p>
                                    </td>

                                    <td>
                                        <p class="mb-0">{{ $product['already_returned'] }}</p>
                                    </td>



                                    <td>
                                        <input type="number"
                                               class="form-control form-control-sm quantity-input"
                                               min="1"
                                               max="{{ $product['available_to_return'] }}"
                                               value="1"
                                               disabled>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm reason-select select2" disabled>
                                            <option value="">Seleccionar...</option>
                                            @foreach($returnReasons as $key => $reason)
                                                <option value="{{ $key }}">{{ $reason }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm condition-select select2" disabled>
                                            <option value="">Seleccionar...</option>
                                            @foreach($returnConditions as $key => $condition)
                                                <option value="{{ $key }}">{{ $condition }}</option>
                                            @endforeach
                                        </select>
                                    </td>

                                </tr>

                            @endforeach



                            </tbody>
                        </table>

                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmProductSelection" disabled>
                        <i class="fas fa-check"></i> Confirmar selección
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')

    <script type="text/javascript">
        $(document).ready(function() {
            let selectedProducts = {};
            let tempSelectedProducts = {};

            // Checkbox principal en el modal
            $('#selectAll').on('change', function() {
                $('.product-checkbox').prop('checked', this.checked).trigger('change');
            });

            // Checkbox de producto individual
            $('.product-checkbox').on('change', function() {
                const $row = $(this).closest('tr');
                const isChecked = this.checked;
                const productId = $(this).val();

                // Habilitar/deshabilitar campos
                $row.find('.quantity-input, .reason-select, .condition-select').prop('disabled', !isChecked);

                if (isChecked) {
                    // Agregar a productos temporales
                    tempSelectedProducts[productId] = {
                        id: productId,
                        name: $(this).data('name'),
                        price: parseFloat($(this).data('price')),
                        max: parseInt($(this).data('max')),
                        quantity: parseInt($row.find('.quantity-input').val()),
                        reason: $row.find('.reason-select').val(),
                        condition: $row.find('.condition-select').val()
                    };
                } else {
                    // Eliminar de productos temporales
                    delete tempSelectedProducts[productId];
                    $row.find('.quantity-input').val(1);
                    $row.find('.reason-select, .condition-select').val('');
                }

                updateConfirmButton();
            });

            // Cambios en los inputs del modal
            $('.quantity-input, .reason-select, .condition-select').on('change input', function() {
                const $row = $(this).closest('tr');
                const $checkbox = $row.find('.product-checkbox');

                if ($checkbox.is(':checked')) {
                    const productId = $checkbox.val();

                    // Validar cantidad máxima
                    if ($(this).hasClass('quantity-input')) {
                        const max = parseInt($(this).attr('max'));
                        const val = parseInt($(this).val()) || 0;

                        if (val > max) {
                            $(this).val(max);
                            toastr.warning(`La cantidad máxima disponible es ${max}`);
                        } else if (val < 1) {
                            $(this).val(1);
                        }
                    }

                    // Actualizar producto temporal
                    if (tempSelectedProducts[productId]) {
                        tempSelectedProducts[productId].quantity = parseInt($row.find('.quantity-input').val());
                        tempSelectedProducts[productId].reason = $row.find('.reason-select').val();
                        tempSelectedProducts[productId].condition = $row.find('.condition-select').val();
                    }
                }

                updateConfirmButton();
            });

            // Actualizar botón de confirmar
            function updateConfirmButton() {
                let canConfirm = Object.keys(tempSelectedProducts).length > 0;

                // Verificar que todos tengan razón y condición
                Object.values(tempSelectedProducts).forEach(product => {
                    if (!product.reason || !product.condition) {
                        canConfirm = false;
                    }
                });

                $('#confirmProductSelection').prop('disabled', !canConfirm);
            }

            // Al abrir el modal
            $('#selectProductModal').on('show.bs.modal', function() {
                tempSelectedProducts = {...selectedProducts};

                // Restaurar selecciones previas
                $('.product-checkbox').each(function() {
                    const productId = $(this).val();
                    const $row = $(this).closest('tr');

                    if (selectedProducts[productId]) {
                        $(this).prop('checked', true);
                        $row.find('.quantity-input').val(selectedProducts[productId].quantity).prop('disabled', false);
                        $row.find('.reason-select').val(selectedProducts[productId].reason).prop('disabled', false);
                        $row.find('.condition-select').val(selectedProducts[productId].condition).prop('disabled', false);
                    }
                });
            });

            // Confirmar selección de productos
            $('#confirmProductSelection').on('click', function() {
                selectedProducts = {...tempSelectedProducts};
                updateSelectedProductsList();
                $('#selectProductModal').modal('hide');
            });

            // Actualizar lista de productos seleccionados
            function updateSelectedProductsList() {
                const $container = $('#selectedProductsList');

                if (Object.keys(selectedProducts).length === 0) {
                    $container.html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No se han seleccionado productos para devolver.
                        </div>
                    `);
                    $('#return-summary').addClass('d-none');
                    $('#validateBtn, #submitBtn').prop('disabled', true);
                    return;
                }

                let html = '';
                let total = 0;

                Object.values(selectedProducts).forEach((product, index) => {
                    const subtotal = product.quantity * product.price;
                    total += subtotal;

                    html += `
                        <div class="bundle-block mb-3" data-product="${product.id}">
                            <div class="d-flex justify-content-between align-items-center bundle-head">
                                <strong>${product.name}</strong>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-product" data-product="${product.id}">
                                    <i class="fas fa-trash-alt me-1"></i>
                                </button>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-3">Cantidad: <strong>${product.quantity}</strong></div>
                                        <div class="col-md-3">Precio unit.: <strong>${product.price.toFixed(2)}€</strong></div>
                                        <div class="col-md-3">Subtotal: <strong>${subtotal.toFixed(2)}€</strong></div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">Razón: <strong>${$('.reason-select option[value="'+product.reason+'"]').text()}</strong></div>
                                        <div class="col-md-6">Condición: <strong>${$('.condition-select option[value="'+product.condition+'"]').text()}</strong></div>
                                    </div>
                                </li>
                            </ul>
                            <input type="hidden" name="products[${index}][product_id]" value="${product.id}">
                            <input type="hidden" name="products[${index}][quantity]" value="${product.quantity}">
                            <input type="hidden" name="products[${index}][reason]" value="${product.reason}">
                            <input type="hidden" name="products[${index}][condition]" value="${product.condition}">
                        </div>
                    `;
                });

                $container.html(html);
                $('#totalRefundAmount').text(total.toFixed(2) + '€');
                $('#return-summary').removeClass('d-none');
                $('#validateBtn').prop('disabled', false);
            }

            // Eliminar producto de la lista
            $(document).on('click', '.btn-remove-product', function() {
                const productId = $(this).data('product');
                delete selectedProducts[productId];
                updateSelectedProductsList();
            });

            // Validar devolución
            $('#validateBtn').on('click', function() {
                if (Object.keys(selectedProducts).length === 0) {
                    toastr.warning('Debe seleccionar al menos un producto');
                    return;
                }

                const products = Object.values(selectedProducts).map(p => ({
                    product_id: p.id,
                    quantity: p.quantity,
                    reason: p.reason,
                    condition: p.condition
                }));

                $.ajax({
                    url: '{{ route("callcenter.returns.validate-products") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        return_id: $('#return_id').val(),
                        products: products
                    },
                    beforeSend: function() {
                        $('#validateBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Validando...');
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Productos validados correctamente', "Operación exitosa", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });

                            $('#submitBtn').prop('disabled', false);
                        } else {
                            let errorMsg = 'Errores de validación:\n';
                            response.errors.forEach(error => {
                                errorMsg += `- ${error.message}\n`;
                            });

                            toastr.error(errorMsg, "Validación fallida", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right",
                                timeOut: 5000
                            });
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error al validar los productos', "Operación fallida", {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        $('#validateBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Validar devolución');
                    }
                });
            });

            // Enviar formulario
            $('#formReturns').on('submit', function(e) {
                e.preventDefault();

                if (Object.keys(selectedProducts).length === 0) {
                    toastr.warning('Debe seleccionar al menos un producto');
                    return;
                }

                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route("callcenter.returns.store") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Operación exitosa", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right",
                                timeOut: 1000,
                                onHidden: function() {
                                    window.location.href = "{{ route('callcenter.returns.index') }}";
                                }
                            });
                        } else {
                            toastr.error(response.message || 'Error al crear la devolución', "Operación fallida", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error al procesar la solicitud', "Operación fallida", {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Crear devolución');
                    }
                });
            });
        });
    </script>

@endpush
