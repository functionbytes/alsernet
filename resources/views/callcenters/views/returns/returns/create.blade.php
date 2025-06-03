



@extends('layouts.callcenters')'

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formStatements" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" name="uid" id="uid" value="{{$return->uid}}">
                    <input type="hidden" name="id" id="id" value="{{$return->id}}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0 uppercase">Crear devolución # {{$order->order_number}}
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>
                        <div class="row">
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Nombres</label>
                                <input type="text" class="form-control"  name="firstname" value="{{$customer->firstname}}" placeholder="Ingresar nombres"  autocomplete="new-password" >
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Apellidos</label>
                                <input type="text" class="form-control" name="lastname" value="{{$customer->lastname}}" placeholder="Ingresar apellidos" autocomplete="new-password">
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">DNI/NIE/CIF</label>
                                <input type="text" class="form-control" name="identification" value="{{$customer->identification}}" placeholder="Ingresar la identificacion" autocomplete="new-password">
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">Fecha de nacimiento</label>
                                <input type="text" class="form-control picker-date" name="birth" value="{{$customer->birth_at }}" autocomplete="off">
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Iban</label>
                                <input type="text" class="form-control iban" name="iban" value="{{$customer->iban}}" placeholder="Ingresar el iban" autocomplete="new-password">
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Correo electronico</label>
                                <input type="text" class="form-control" name="email" value="{{$customer->email}}" placeholder="Ingresar el correo electronico" autocomplete="new-password">
                            </div>

                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Telefono</label>
                                <input type="text" class="form-control"  name="cellphone" value="{{$customer->cellphone}}" disabled placeholder="Ingresar el celular" autocomplete="new-password">
                            </div>

                            <div class="col-sm-12 col-md-6 mb-3">
                                <label  class="control-label col-form-label">Telefono (opcional)</label>
                                <input type="text" class="form-control"  name="phone" value="" placeholder="Ingresar el celular" autocomplete="new-password">
                            </div>

                            <div class="col-sm-12 col-md-6 mb-3">
                                <label class="control-label col-form-label">Fecha de entrega</label>
                                <input type="text" class="form-control picker" name="delivery" value="{{$customer->delivery_at }}" autocomplete="off">
                            </div>



                            <hr class="mb-4 mt-3">

                            <div class="d-flex flex-column mb-3">
                                <h5 class="mb-0 text-uppercase fw-semibold">Gestión comercial</h5>
                                <p class="card-subtitle text-muted mt-2">
                                    En esta sección encontrarás toda la información relacionada con la actividad comercial, incluyendo el estado de contacto con el cliente, reprogramaciones de llamadas, seguimiento y otras acciones realizadas por el equipo comercial.
                                </p>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-3">
                                <label for="status" class="control-label col-form-label">Oferta</label>
                                <select class="form-control select2" id="bundle" name="bundle">
                                    @foreach($products as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <label id="bundle-error" class="error" for="bundle" style="display: none"></label>
                            </div>

                            <div class="col-12 mb-3">
                                <label  class="control-label col-form-label">Observaciones adicionales para esta venta</label>
                                <textarea type="text" class="form-control"  name="notes"  autocomplete="new-password"></textarea>
                            </div>

                            <div class="list-bundles-container">
                                @if($items->count())
                                    @foreach($items->groupBy('bundle_id') as $bundleId => $bundleItems)

                                        @php
                                            $amount = $bundleItems->first()->bundle->amount ?? 0;
                                        @endphp

                                        <div class="bundle-block mb-3" data-bundle="{{ $bundleId }}">
                                            <div class="d-flex justify-content-between align-items-center bundle-head">
                                                <strong>{{ $bundles[$bundleId]  }} </strong><br>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-clear-bundle" data-bundle="{{ $bundleId }}">
                                                    <i class="fas fa-trash-alt me-1"></i>
                                                </button>
                                            </div>
                                            <ul class="list-group list-group-flush">
                                                @foreach($bundleItems as $index => $item)
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        {{ $item->product->title ?? 'Producto' }}
                                                    </li>
                                                    <input type="hidden" name="bundles[{{ $bundleId }}][products][{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="bundles[{{ $bundleId }}][products][{{ $index }}][category_id]" value="{{ $item->category_id }}">
                                                    <input type="hidden" name="bundles[{{ $bundleId }}][products][{{ $index }}][bundle_id]" value="{{ $item->bundle_id }}">
                                                @endforeach
                                            </ul>
                                            <input type="hidden" name="bundles[{{ $bundleId }}][bundle_id]" value="{{ $bundleId }}">
                                            <input type="hidden" name="bundles[{{ $bundleId }}][amount]" value="{{ $amount }}">
                                        </div>
                                    @endforeach
                                    <input type="hidden" id="bundles_installment" name="installment" value="{{ $statement->installment }}">
                                    <input type="hidden" id="bundles_installment_amount" name="installment_amount" value="{{ $statement->installment_amount }}">

                                @endif
                            </div>

                            <div id="global-installment-summary" class="mt-3"></div>


                            <div class="col-12">
                                <div class="errors mb-3 d-none">
                                </div>
                            </div>

                            <div class="col-12 ">
                                <div class="border-top pt-1 mt-2">
                                    <button type="submit" class="btn btn-primary  px-4 waves-effect waves-light mt-2 w-100">
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


    <div id="confirmSaleModal" class="modal fade modal-bundle">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecciona los artículos de la oferta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body" id="modal-offer-content">
                    <div class="text-center text-muted">Cargando...</div>
                </div>
            </div>
        </div>
    </div>


@endsection



@push('scripts')

    <script type="text/javascript">
        Dropzone.autoDiscover = false;
        const hasPreloadedItems = @json($items->count() > 0);
        const hasNoIncident = @json($statement->incidents->count()>0);
        console.log(hasNoIncident);
        var dropzones = {};

        $(document).ready(function() {

            if ($.fn.select2 && $('#bundle').hasClass("select2-hidden-accessible")) {
                $('#bundle').select2('destroy');
            }

            $('#bundle').select2({
                width: '100%'
            });

            $('#type').on('change', function () {
                const selectedType = $(this).val();

                if (selectedType === '1') {

                    $('.typePayment').removeClass('d-none');
                    $('.installmentPayment').addClass('d-none');

                    $('.iban').val('');

                    $('#installment').val('1').trigger('change');
                }else if (selectedType === '2') {

                    $('.installmentPayment').removeClass('d-none');
                    $('.typePayment').addClass('d-none');

                    const currentIban = $('.iban').val().trim();

                    if (currentIban === '' || currentIban === 'ES00 0000 0000 0000 0000 0000') {
                        $('.iban').val('');
                    }

                }else if (selectedType === '3') {

                    $('.typePayment').addClass('d-none');
                    $('.installmentPayment').addClass('d-none');

                    $('.iban').val('ES00 0000 0000 0000 0000 0000');

                    $('#installment').val('1').trigger('change');

                }

            });


            function initializeProductSelector(bundleId, bundleTitle, bundleAmount, maxPoints) {
                let selectedProducts = [];
                const $selector = $('#productSelector');
                const $container = $('#bundleListContainer');
                const $used = $('#usedPoints');
                const $remaining = $('#remainingPointsDynamic');
                const $confirmBtn = $('#confirm-bundle');

                $selector.select2({ width: '100%', dropdownParent: $('#confirmSaleModal') });

                function groupProducts() {
                    const grouped = {};
                    selectedProducts.forEach((p) => {
                        const key = `${p.id}`;
                        if (!grouped[key]) grouped[key] = { ...p, quantity: 1 };
                        else grouped[key].quantity += 1;
                    });
                    return Object.values(grouped);
                }

                function updateUI() {
                    const grouped = groupProducts();
                    window.bundleSelections = window.bundleSelections || {};
                    window.bundleSelections[bundleId] = grouped;

                    const used = grouped.reduce((sum, p) => sum + (p.points * p.quantity), 0);
                    const remaining = maxPoints - used;

                    $used.text(used.toFixed(2));
                    $remaining.text(remaining.toFixed(2));
                    $confirmBtn.prop('disabled', used.toFixed(2) != maxPoints.toFixed(2));

                    if (grouped.length === 0) return $container.empty();

                    let html = `
                <div class="bundle-block mb-3" data-bundle="${bundleId}">
                    <div class="d-flex justify-content-between align-items-center bundle-head w-100">
                        <div><strong>Listado productos</strong></div>
                    </div>
                    <ul class="list-group list-group-flush">`;

                    grouped.forEach((p, i) => {
                        html += `
                    <li class="list-group-item">
                        <div class="row w-100 align-items-center text-center">
                            <div class="col-12 col-md-6 d-flex flex-column flex-md-row justify-content-start align-items-center text-md-start mb-2 mb-md-0">
                                <span class="me-2">${p.title}</span>
                                <span class="text-muted">(${p.points} pts)</span>
                            </div>
                            <div class="col-6 col-md-3 d-flex justify-content-center align-items-center">
                                <span class="badge bg-success">${p.quantity} ud</span>
                            </div>
                            <div class="col-6 col-md-3 d-flex justify-content-center align-items-center">
                                <button class="btn btn-sm btn-danger btn-remove-item" data-id="${p.id}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </li>
                    <input type="hidden" name="bundles[${bundleId}][products][${i}][product_id]" value="${p.id}">
                    <input type="hidden" name="bundles[${bundleId}][products][${i}][bundle_id]" value="${bundleId}">
                    <input type="hidden" name="bundles[${bundleId}][products][${i}][quantity]" value="${p.quantity}">`;
                    });

                    html += `</ul></div>`;
                    $container.html(html);

                    $selector.find('option').each(function () {
                        const pts = parseFloat($(this).data('points')) || 0;
                        $(this).prop('disabled', pts > remaining);
                    });

                    $selector.val(null).trigger('change.select2');
                }

                $selector.off('change').on('change', function () {
                    const option = $(this).find('option:selected');
                    const id = option.val();
                    const title = option.data('title');
                    const points = parseFloat(option.data('points'));

                    if (id && !isNaN(points)) {
                        selectedProducts.push({ id, title, points });
                        updateUI();
                    }
                });

                $container.off('click').on('click', '.btn-remove-item', function () {
                    const idToRemove = $(this).data('id');
                    const index = selectedProducts.findIndex(p => p.id == idToRemove);
                    if (index !== -1) {
                        selectedProducts.splice(index, 1);
                        updateUI();
                    }
                });

                updateUI();
            }

            $(document).on('click', '#confirm-bundle', function () {
                const $productselect = $('#bundle');
                const bundleId = $productselect.val();
                if (!bundleId) return;

                const bundleTitle = $productselect.find('option:selected').text();
                const bundleAmount = parseFloat($('#productSelector').data('bundle-amount')) || 0;
                const grouped = window.bundleSelections?.[bundleId] || [];

                if (!grouped.length) {
                    console.warn('No se seleccionaron productos para el bundle:', bundleId);
                    return;
                }

                const installment = 1;
                const installmentAmount = bundleAmount.toFixed(2);
                let itemsHtml = '';
                let inputProducts = '';

                grouped.forEach((item, index) => {
                    itemsHtml += `
                <li class="list-group-item">
                    <div class="row w-100 align-items-center text-center">
                        <div class="col-12 col-md-6 d-flex flex-column flex-md-row justify-content-start align-items-center text-md-start mb-2 mb-md-0">
                            <span class="me-2">${item.title}</span>
                            <span class="text-muted">(${item.points} pts)</span>
                        </div>
                        <div class="col-6 col-md-6 d-flex justify-content-center align-items-center">
                            <span class="badge bg-success">${item.quantity} ud</span>
                        </div>
                    </div>
                </li>`;

                    inputProducts += `
                <input type="hidden" name="bundles[${bundleId}][products][${index}][product_id]" value="${item.id}">
                <input type="hidden" name="bundles[${bundleId}][products][${index}][bundle_id]" value="${bundleId}">
                <input type="hidden" name="bundles[${bundleId}][products][${index}][quantity]" value="${item.quantity}">`;
                });

                $(`.bundle-block[data-bundle="${bundleId}"]`).remove();

                const block = `
            <div class="bundle-block mb-3" data-bundle="${bundleId}">
               <div class="d-flex justify-content-between align-items-center bundle-head w-100">
                                <div>
                                    <strong>${bundleTitle}</strong><br>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-clear-bundle" data-bundle="${bundleId}">
                                    <i class="fas fa-trash-alt me-1"></i>
                                </button>
                            </div>
                <ul class="list-group list-group-flush">${itemsHtml}</ul>
                <input type="hidden" name="bundles[${bundleId}][bundle_id]" value="${bundleId}">
                <input type="hidden" name="bundles[${bundleId}][amount]" value="${installmentAmount}">
                <input type="hidden" name="bundles[${bundleId}][installment]" value="${installment}">
                <input type="hidden" name="bundles[${bundleId}][installment_amount]" value="${installmentAmount}">
                ${inputProducts}
            </div>`;

                $('.list-bundles-container').append(block);
                $('#confirmSaleModal').modal('hide');
                if (typeof updateGlobalInstallmentSummary === 'function') {
                    updateGlobalInstallmentSummary();
                    $('#bundle').val(null).trigger('change');

                }
            });


            $('#bundle').on('change', function () {
                const bundleId = $(this).val();
                const modal = $('#confirmSaleModal');
                const modalContent = $('#modal-offer-content');


                if (!bundleId) return;

                modalContent.html('<div class="text-center text-muted">Cargando...</div>');
                modal.modal('show');

                $.get("{{ route('commercial.statements.bundle.content', ':id') }}".replace(':id', bundleId), function (data) {
                    modalContent.html(data);

                    // Inicializar select2 dentro del modal para productSelector
                    const $productSelector = $('#productSelector');
                    if ($productSelector.length) {
                        $productSelector.select2({
                            width: '100%',
                            dropdownParent: modal
                        }).val(null).trigger('change');

                        const bundleTitle = $('#bundle option:selected').text();
                        const bundleAmount = parseFloat($('#productSelector').data('bundle-amount')) || 0;
                        const installmentAmount = bundleAmount.toFixed(2);
                        const maxPoints = parseFloat($('#remainingPoints').text()) || 0;

                        initializeProductSelector(bundleId, bundleTitle, bundleAmount, maxPoints);
                    }

                    // Modo clásico: si se usa select múltiple tipo .product-select-bundle
                    const $multiSelectors = $('.product-select-bundle');
                    if ($multiSelectors.length) {
                        $multiSelectors.select2({
                            width: '100%',
                            placeholder: 'Selecciona productos',
                            dropdownParent: modal
                        }).val(null).trigger('change');

                        function updatePoints() {
                            const maxPoints = parseFloat($('#remainingPoints').text()) || 0;
                            const $used = $('#usedPoints');
                            const $remaining = $('#remainingPointsDynamic');
                            const $confirmBtn = $('#confirm-bundle');

                            let used = 0;

                            $multiSelectors.each(function () {
                                $(this).find('option:selected').each(function () {
                                    used += parseFloat($(this).data('points')) || 0;
                                });
                            });

                            const remaining = maxPoints - used;
                            $used.text(used.toFixed(2));
                            $remaining.text(remaining.toFixed(2));

                            $multiSelectors.each(function () {
                                const $select = $(this);
                                $select.find('option').each(function () {
                                    const $opt = $(this);
                                    const pts = parseFloat($opt.data('points')) || 0;
                                    const isSelected = $opt.is(':selected');

                                    $opt.prop('disabled', !isSelected && (used + pts) > maxPoints);
                                });
                            });

                            $multiSelectors.trigger('change.select2');
                            $confirmBtn.prop('disabled', used > maxPoints || used === 0);
                        }

                        updatePoints();
                        $(document).off('change.bundle').on('change.bundle', '.product-select-bundle', updatePoints);
                    }

                }).fail(function () {
                    modalContent.html('<div class="text-danger text-center">Error al cargar los productos de la oferta.</div>');
                });
            });


            // Eliminar bundle
            $(document).on('click', '.btn-clear-bundle', function () {
                const bundleId = $(this).data('bundle');
                $(`.bundle-block[data-bundle="${bundleId}"]`).remove();
                $(`.select2[data-bundle-id="${bundleId}"]`).val('').trigger('change');
                updateGlobalInstallmentSummary();
            });

            $(document).on('change', '#installment', function () {
                updateGlobalInstallmentSummary();
            });

            function updateGlobalInstallmentSummary() {
                const uniqueBundleIds = new Set();
                let totalAmount = 0;
                let totalPoints = 0;
                const globalInstallment = parseInt($('#installment').val()) || 1;

                $('.bundle-block').each(function () {
                    const bundleId = $(this).data('bundle');
                    if (uniqueBundleIds.has(bundleId)) return; // evita duplicados
                    uniqueBundleIds.add(bundleId);

                    const amount = parseFloat($(this).find(`input[name="bundles[${bundleId}][amount]"]`).val()) || 0;
                    totalAmount += amount;

                    const grouped = window.bundleSelections?.[bundleId] || [];
                    grouped.forEach(p => {
                        totalPoints += (parseFloat(p.points) || 0) * (parseInt(p.quantity) || 0);
                    });
                });

                const globalInstallmentAmount = (totalAmount / globalInstallment).toFixed(2);
                const html = `
        <div class="container-installment p-2 small alert alert-danger">
            Importe total: <strong>${totalAmount.toFixed(2)}€</strong>,
            Puntos: <strong>${totalPoints}</strong>,
            Cuotas: <strong>${globalInstallment}</strong>,
            Cuota mensual: <strong>${globalInstallmentAmount}€</strong>
        </div>`;

                $('#global-installment-summary').html(html);
            }




            $('#status').select2({
                placeholder: 'Seleccionar un estado',
            });


            $('.picker-date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                endDate: new Date(),
                todayHighlight: true
            });

            $('.picker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                startDate: new Date(),
                todayHighlight: true
            });



            function toggleVisitFields() {
                const status = $('#status').val();

                if (status == '1') {
                    $('.container-schedule').removeClass('d-none');
                    $('.container-visit').removeClass('d-none');
                } else {
                    $('.container-schedule')
                        .addClass('d-none')
                        .find('input, textarea').val('');
                    $('.container-schedule').find('select').val(null).trigger('change'); // Select2

                    $('.container-visit')
                        .addClass('d-none')
                        .find('input, textarea').val('');
                    $('.container-visit').find('select').val(null).trigger('change');
                }

                if (status == '2') {
                    $('.container-nextcall').removeClass('d-none');
                } else {
                    $('.container-nextcall')
                        .addClass('d-none')
                        .find('input, textarea').val('');
                    $('.container-nextcall').find('select').val(null).trigger('change');
                }
            }


            toggleVisitFields();

            $('#status').on('change', function () {
                toggleVisitFields();
            });

            $(".picker").datepicker({
                onSelect: function(dateText, inst) {
                    $(this).datepicker("hide");
                }
            });

            jQuery.validator.addMethod(
                'cellphone',
                function (value, element) {
                    // Limpiar el valor: quitar espacios, guiones o puntos
                    const cleanValue = value.replace(/[\s.-]/g, '');
                    // Validar que tenga exactamente 9 dígitos y empiece por 6, 7 o 9
                    return this.optional(element) || /^(6|7|8|9)[0-9]{8}$/.test(cleanValue);
                },
                'Por favor, ingrese un número de teléfono válido'
            );

            jQuery.validator.addMethod(
                'iban',
                function (value, element) {
                    if (value === '') return true;

                    value = value.replace(/\s+/g, '').toUpperCase();

                    const regex = /^ES\d{22}$/;

                    return regex.test(value);
                },
                'Por favor, ingrese un IBAN válido.'
            );

            jQuery.validator.addMethod(
                'emailExt',
                function (value, element) {
                    if (value === '') return true;
                    return /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\\.,;:\s@\"]+\.)+[^<>()[\]\\.,;:\s@\"]{2,})$/i.test(value);
                },
                'Por favor, ingrese un correo electrónico válido.'
            );



            $("#formStatements").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    firstname: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    lastname: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    identification: {
                        required: true,
                        minlength: 1,
                        maxlength: 9,
                    },
                    cellphone: {
                        required: true,
                        cellphone: true,
                    },
                    phone: {
                        required: false,
                        cellphone: true,
                    },
                    iban: {
                        required: function (element) {
                            const status = $('#type').val();

                            if (status === '1') {
                                return false;
                            } else if (status === '2') {
                                return true;
                            } else if (status === '3') {
                                return true;
                            }

                        },
                    },
                    email: {
                        required: true,
                        emailExt: true,
                    },
                    marital: {
                        required: true,
                    },
                    housing: {
                        required: true,
                    },
                    payment: {
                        required: true,
                    },
                    relationship: {
                        required: true,
                    },
                    employment: {
                        required: true,
                    },
                    birth: {
                        required: true,
                    },
                    delivery: {
                        required: true,
                    },
                    schedule: {
                        required: true,
                    },
                    cream: {
                        required: true,
                    },
                    type: {
                        required: true,
                    },
                    accessorie: {
                        required: true,
                    },
                    status: {
                        required: true,
                    },
                    bundle: {
                        required: true,
                    },
                    installment: {
                        required: function (element) {
                            const status = $('#type').val();
                            return status === '2';
                        },
                    },
                    method: {
                        required: function (element) {
                            const status = $('#type').val();
                            return status === '1';
                        },
                    },
                    notes: {

                        required: false,
                        minlength: 1
                    }
                },
                messages: {
                    firstname: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    lastname: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    identification: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 9  caracter",
                    },
                    cellphone: {
                        required: "El parametro es necesario.",
                        email: 'Por favor, ingrese un número de teléfono.',
                    },
                    phone: {
                        required: "El parametro es necesario.",
                        email: 'Por favor, ingrese un número de teléfono.',
                    },
                    birth: {
                        required: "El parametro es necesario.",
                    },
                    iban: {
                        required: "El parametro es necesario.",
                        iban: "Por favor, ingrese un IBAN válido.",
                    },
                    email: {
                        required: "El parametro es necesario.",
                        emailExt: true,
                    },
                    marital: {
                        required: "El parametro es necesario.",
                    },
                    housing: {
                        required: "El parametro es necesario.",
                    },
                    payment: {
                        required: "El parametro es necesario.",
                    },

                    relationship: {
                        required: "El parametro es necesario.",
                    },
                    delivery: {
                        required: "El parametro es necesario.",
                    },
                    schedule: {
                        required: "El parametro es necesario.",
                    },
                    cream: {
                        required: "El parametro es necesario.",
                    },
                    accessorie: {
                        required: "El parametro es necesario.",
                    },
                    employment: {
                        required: "El parametro es necesario.",
                    },
                    type: {
                        required: "El parametro es necesario.",
                    },
                    method: {
                        required: "El parametro es necesario.",
                    },
                    bundle: {
                        required: "El parametro es necesario.",
                    },
                    status: {
                        required: "El parametro es necesario.",
                    },
                    installment: {
                        required: "El parametro es necesario.",
                    },
                    notes: {
                        required: "El campo de observaciones es obligatorio.",
                        minlength: "Las observaciones deben tener al menos 5 caracteres."
                    },

                },
                submitHandler: function(form) {

                    var $form = $('#formStatements');
                    var formData = new FormData($form[0]);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);


                    $.ajax({
                        url: "{{ route('callcenter.returns.store') }}",
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

                                $.each(dropzones, function(fieldName, dz) {
                                    if (dz.getQueuedFiles().length > 0) {
                                        dz.options.autoProcessQueue = true;
                                        dz.processQueue();
                                    }
                                });

                                if($('#status').val() == 2){
                                    $('#confirmIncidentModal').modal('show');
                                }else{

                                    toastr.success(message, "Operación exitosa", {
                                        closeButton: true,
                                        progressBar: true,
                                        positionClass: "toast-bottom-right",
                                        timeOut: 1000,
                                        onHidden: function () {
                                            window.location.href = "{{ route('callcenter.returns.store') }}";
                                        }
                                    });

                                }


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


















