@extends('layouts.theme')

@section('title', 'Configuración de envíos')

@section('content')

    {{-- =========================================================
         Sección 1: Reglas de envío
    ========================================================= --}}
    <div class="card mb-3">
        <div class="card-header border-bottom p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Reglas de envío</h5>
                    <small class="text-muted">Configura reglas para calcular la tarifa de envío por país y zona</small>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#select-country-modal">
                    <i class="fas fa-plus me-1"></i> Seleccionar país
                </button>
            </div>
        </div>
        <div class="card-body">
            @include('core::components.alerts')

            <div class="alert alert-info py-2 small mb-3">
                Si deseas establecer la tarifa según código postal, habilita "Código postal" en Configuración → Pago.
            </div>

            <div id="zones-container">
        @forelse($zones as $zone)
            <div class="card mb-3 zone-block" data-zone-id="{{ $zone->id }}">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong>{{ $zone->title }}</strong>
                        <div class="d-flex gap-3">
                            <a href="#" class="btn-add-rule small" data-zone-id="{{ $zone->id }}">Agregar regla</a>
                            <a href="#" class="btn-delete-zone small text-danger" data-zone-id="{{ $zone->id }}" data-zone-title="{{ $zone->title }}">Eliminar</a>
                        </div>
                    </div>
                    <div class="accordion accordion-flush border rounded" id="accordion-zone-{{ $zone->id }}">
                        @foreach($zone->rules as $rule)
                            @include('ecommerce::settings.partials.shipping-rule-item', compact('rule', 'zone'))
                        @endforeach
                        @if($zone->rules->isEmpty())
                            <div class="text-center text-muted py-3 small no-rules-msg">No hay reglas. Haz clic en "Agregar regla".</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4" id="no-zones-msg">
                No hay zonas de envío. Crea una haciendo clic en "Seleccionar país".
            </div>
        @endforelse
            </div>
        </div>
    </div>

    {{-- =========================================================
         Sección 2: Configuraciones de envío
    ========================================================= --}}
    <form action="{{ route('settings.ecommerce.shipping.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-header border-bottom p-3">
                <h5 class="mb-0 fw-bold">Configuraciones generales de envío</h5>
                <small class="text-muted">Comportamiento de las opciones de envío en checkout</small>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="hide_other_shipping_options_if_it_has_free_shipping" value="1"
                                id="hide_other_shipping_options_if_it_has_free_shipping"
                                {{ old('hide_other_shipping_options_if_it_has_free_shipping', $settings['hide_other_shipping_options_if_it_has_free_shipping'] ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_other_shipping_options_if_it_has_free_shipping">
                                Ocultar otras opciones de envío si tiene envío gratuito en la lista
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Ordenar la dirección de las opciones de envío</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    name="sort_shipping_options_direction" id="sort_lower_to_higher"
                                    value="price_lower_to_higher"
                                    {{ old('sort_shipping_options_direction', $settings['sort_shipping_options_direction'] ?? '') == 'price_lower_to_higher' ? 'checked' : '' }}>
                                <label class="form-check-label" for="sort_lower_to_higher">Precio de menor a mayor</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    name="sort_shipping_options_direction" id="sort_higher_to_lower"
                                    value="price_higher_to_lower"
                                    {{ old('sort_shipping_options_direction', $settings['sort_shipping_options_direction'] ?? '') == 'price_higher_to_lower' ? 'checked' : '' }}>
                                <label class="form-check-label" for="sort_higher_to_lower">Precio de mayor a menor</label>
                            </div>
                        </div>
                        <div class="form-text">Ordene las opciones de envío por precio, de menor a mayor o de mayor a menor.</div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="disable_shipping_options" value="1"
                                id="disable_shipping_options"
                                {{ old('disable_shipping_options', $settings['disable_shipping_options'] ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="disable_shipping_options">
                                Deshabilitar las opciones de envío
                            </label>
                        </div>
                        <div class="form-text">Las opciones de envío se eliminarán en la página de pago, el cliente no podrá seleccionar opciones de envío.</div>
                    </div>

                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar ajustes
                </button>
            </div>
        </div>
    </form>

    {{-- =========================================================
         Modal: Seleccionar país / crear zona
    ========================================================= --}}
    <div class="modal fade" id="select-country-modal" tabindex="-1" aria-labelledby="selectCountryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectCountryModalLabel">Nueva zona de envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="zone-title" class="form-label">Nombre de la zona</label>
                        <input type="text" id="zone-title" class="form-control" placeholder="Ej: Envio nacional">
                    </div>
                    <div class="mb-3">
                        <label for="zone-country" class="form-label">País</label>
                        <select id="zone-country" class="form-select">
                            <option value="">Seleccionar país...</option>
                            <option value="AR">Argentina</option>
                            <option value="BO">Bolivia</option>
                            <option value="BR">Brasil</option>
                            <option value="CL">Chile</option>
                            <option value="CO">Colombia</option>
                            <option value="CR">Costa Rica</option>
                            <option value="CU">Cuba</option>
                            <option value="EC">Ecuador</option>
                            <option value="SV">El Salvador</option>
                            <option value="GT">Guatemala</option>
                            <option value="HN">Honduras</option>
                            <option value="MX">México</option>
                            <option value="NI">Nicaragua</option>
                            <option value="PA">Panamá</option>
                            <option value="PY">Paraguay</option>
                            <option value="PE">Perú</option>
                            <option value="DO">República Dominicana</option>
                            <option value="UY">Uruguay</option>
                            <option value="VE">Venezuela</option>
                            <option value="ES">España</option>
                            <option value="US">Estados Unidos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch">
                    <button type="button" id="btn-create-zone" class="btn btn-primary w-100 mb-2">Crear zona</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Agregar regla --}}
    <div class="modal fade" id="add-rule-modal" tabindex="-1" aria-labelledby="addRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRuleModalLabel">Agregar regla de envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rule-zone-id">
                    <div class="mb-3">
                        <label for="rule-name" class="form-label">Nombre</label>
                        <input type="text" id="rule-name" class="form-control" placeholder="Ej: Envio estandar">
                    </div>
                    <div class="mb-3">
                        <label for="rule-type" class="form-label">Tipo</label>
                        <select id="rule-type" class="form-select">
                            <option value="based_on_price">Basado en el monto total del pedido</option>
                            <option value="based_on_weight">Basado en el peso total del pedido</option>
                            <option value="based_on_zipcode">Basado en codigo postal</option>
                            <option value="based_on_location">Basado en la ubicacion</option>
                        </select>
                    </div>
                    <div id="rule-from-to-block">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="rule-from" class="form-label">Desde</label>
                                <input type="number" id="rule-from" class="form-control" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-6">
                                <label for="rule-to" class="form-label">Hasta</label>
                                <input type="number" id="rule-to" class="form-control" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div id="rule-price-block" class="mb-3">
                        <label for="rule-price" class="form-label">Precio</label>
                        <input type="number" id="rule-price" class="form-control" min="0" step="0.01" value="0">
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch">
                    <button type="button" id="btn-save-rule" class="btn btn-primary w-100 mb-2">Agregar regla</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Agregar tarifa de ciudad --}}
    <div class="modal fade" id="add-city-rate-modal" tabindex="-1" aria-labelledby="addCityRateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCityRateModalLabel">Agregar tarifa de ciudad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="city-rate-rule-id">
                    <input type="hidden" id="city-rate-zone-id">
                    <div class="mb-3">
                        <label for="city-rate-state" class="form-label">Estado / Departamento</label>
                        <input type="text" id="city-rate-state" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="city-rate-city" class="form-label">Ciudad</label>
                        <input type="text" id="city-rate-city" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="city-rate-price" class="form-label">Precio de ajuste</label>
                        <input type="number" id="city-rate-price" class="form-control" step="0.01" value="0">
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch">
                    <button type="button" id="btn-save-city-rate" class="btn btn-primary w-100 mb-2">Guardar tarifa</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar eliminar zona --}}
    <div class="modal fade" id="delete-zone-modal" tabindex="-1" aria-labelledby="deleteZoneModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteZoneModalLabel">Eliminar zona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de eliminar la zona <strong id="delete-zone-name"></strong>? Se eliminarán todas sus reglas.</p>
                </div>
                <div class="modal-footer flex-column align-items-stretch">
                    <button type="button" id="btn-confirm-delete-zone" class="btn btn-danger w-100 mb-2">Eliminar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar eliminar regla --}}
    <div class="modal fade" id="delete-rule-modal" tabindex="-1" aria-labelledby="deleteRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRuleModalLabel">Eliminar regla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de eliminar la regla <strong id="delete-rule-name"></strong>?</p>
                </div>
                <div class="modal-footer flex-column align-items-stretch">
                    <button type="button" id="btn-confirm-delete-rule" class="btn btn-danger w-100 mb-2">Eliminar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');

    var ruleTypes = {
        'based_on_price':    { label: 'Basado en el monto total del pedido', showFromTo: true },
        'based_on_weight':   { label: 'Basado en el peso total del pedido', showFromTo: true },
        'based_on_zipcode':  { label: 'Basado en codigo postal', showFromTo: true },
        'based_on_location': { label: 'Basado en la ubicacion', showFromTo: false },
    };

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    function getZoneStoreUrl(zoneId) {
        return '{{ route("ecommerce.shipping.rules.store", ":zone") }}'.replace(':zone', zoneId);
    }
    function getRuleUpdateUrl(zoneId, ruleId) {
        return '{{ route("ecommerce.shipping.rules.update", [":zone", ":rule"]) }}'
            .replace(':zone', zoneId).replace(':rule', ruleId);
    }
    function getRuleDestroyUrl(zoneId, ruleId) {
        return '{{ route("ecommerce.shipping.rules.destroy", [":zone", ":rule"]) }}'
            .replace(':zone', zoneId).replace(':rule', ruleId);
    }
    function getItemsUrl(zoneId, ruleId) {
        return '{{ route("ecommerce.shipping.rules.items.index", [":zone", ":rule"]) }}'
            .replace(':zone', zoneId).replace(':rule', ruleId);
    }
    function getItemStoreUrl(zoneId, ruleId) {
        return '{{ route("ecommerce.shipping.rules.items.store", [":zone", ":rule"]) }}'
            .replace(':zone', zoneId).replace(':rule', ruleId);
    }
    function getItemDestroyUrl(zoneId, ruleId, itemId) {
        return '{{ route("ecommerce.shipping.rules.items.destroy", [":zone", ":rule", ":item"]) }}'
            .replace(':zone', zoneId).replace(':rule', ruleId).replace(':item', itemId);
    }
    function getZoneDestroyUrl(zoneId) {
        return '{{ route("ecommerce.shipping.destroy", ":zone") }}'.replace(':zone', zoneId);
    }

    function buildItemsTable(items, ruleId, zoneId) {
        if (!items.length) {
            return '<p class="text-muted small mb-0">No hay tarifas por ciudad.</p>';
        }
        var rows = items.map(function (item) {
            return '<tr>' +
                '<td>' + (item.state || '—') + '</td>' +
                '<td>' + (item.city || '—') + '</td>' +
                '<td>$ ' + parseFloat(item.adjustment_price).toLocaleString() + '</td>' +
                '<td class="text-end">' +
                    '<div class="dropdown">' +
                        '<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">' +
                            '<i class="fas fa-ellipsis-vertical"></i>' +
                        '</button>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<li><a class="dropdown-item btn-delete-item" href="#" data-item-id="' + item.id + '" data-rule-id="' + ruleId + '" data-zone-id="' + zoneId + '">Eliminar</a></li>' +
                        '</ul>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        }).join('');
        return '<table class="table table-sm mb-0">' +
            '<thead><tr><th>Estado</th><th>Ciudad</th><th>Precio</th><th></th></tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table>';
    }

    function loadItems(ruleId, zoneId) {
        var $container = $('.items-container[data-rule-id="' + ruleId + '"]');
        var $wrapper = $container.find('.items-table-wrapper');
        $wrapper.html('<p class="text-muted small">Cargando...</p>');
        $.ajax({
            url: getItemsUrl(zoneId, ruleId),
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            success: function (res) {
                $wrapper.html(buildItemsTable(res.data, ruleId, zoneId));
            },
            error: function () {
                $wrapper.html('<p class="text-danger small">Error al cargar tarifas.</p>');
            }
        });
    }

    // ---------------------------------------------------------------
    // Rule type toggle (in add-rule modal)
    // ---------------------------------------------------------------
    $('#rule-type').on('change', function () {
        var type = $(this).val();
        var info = ruleTypes[type] || { showFromTo: true };
        if (info.showFromTo) {
            $('#rule-from-to-block, #rule-price-block').show();
        } else {
            $('#rule-from-to-block, #rule-price-block').hide();
        }
    });

    // ---------------------------------------------------------------
    // Rule type toggle (in inline edit forms) — delegated
    // ---------------------------------------------------------------
    $(document).on('change', '.rule-type-select', function () {
        var $form = $(this).closest('.rule-edit-form');
        var type = $(this).val();
        var info = ruleTypes[type] || { showFromTo: true };
        if (info.showFromTo) {
            $form.find('.rule-from-to-fields, .rule-price-field').removeClass('d-none');
            $form.closest('.accordion-item').find('.items-container').hide();
        } else {
            $form.find('.rule-from-to-fields, .rule-price-field').addClass('d-none');
            var ruleId = $form.data('rule-id');
            var zoneId = $form.data('zone-id');
            $form.closest('.accordion-item').find('.items-container').show();
            loadItems(ruleId, zoneId);
        }
    });

    // ---------------------------------------------------------------
    // Load items when accordion opens (for based_on_location rules)
    // ---------------------------------------------------------------
    $(document).on('shown.bs.collapse', '.accordion-collapse', function () {
        var $item = $(this).closest('.accordion-item');
        var ruleId = $item.data('rule-id');
        var zoneId = $item.data('zone-id');
        var $container = $item.find('.items-container');
        if ($container.is(':visible')) {
            loadItems(ruleId, zoneId);
        }
    });

    // ---------------------------------------------------------------
    // Create zone
    // ---------------------------------------------------------------
    var deleteZoneId = null;
    var deleteRuleId = null;
    var deleteRuleZoneId = null;

    $('#btn-create-zone').on('click', function () {
        var title = $('#zone-title').val().trim();
        var country = $('#zone-country').val();
        if (!title) {
            toastr.warning('El nombre de la zona es obligatorio.');
            return;
        }
        var $btn = $(this).prop('disabled', true).text('Guardando...');
        $.ajax({
            url: '{{ route("ecommerce.shipping.store") }}',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            data: JSON.stringify({ title: title, country: country }),
            success: function (res) {
                toastr.success(res.message);
                $('#select-country-modal').modal('hide');
                $('#zone-title').val('');
                $('#zone-country').val('');
                var zone = res.data;
                var html = buildZoneHtml(zone);
                $('#no-zones-msg').remove();
                $('#zones-container').append(html);
            },
            error: function (xhr) {
                toastr.error('Error al crear la zona.');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Crear zona');
            }
        });
    });

    function buildZoneHtml(zone) {
        return '<div class="card mb-3 zone-block" data-zone-id="' + zone.id + '">' +
            '<div class="card-body">' +
                '<div class="d-flex align-items-center justify-content-between mb-2">' +
                    '<strong>' + $('<div>').text(zone.title).html() + '</strong>' +
                    '<div class="d-flex gap-3">' +
                        '<a href="#" class="btn-add-rule small" data-zone-id="' + zone.id + '">Agregar regla</a>' +
                        '<a href="#" class="btn-delete-zone small text-danger" data-zone-id="' + zone.id + '" data-zone-title="' + $('<div>').text(zone.title).html() + '">Eliminar</a>' +
                    '</div>' +
                '</div>' +
                '<div class="accordion accordion-flush border rounded" id="accordion-zone-' + zone.id + '">' +
                    '<div class="text-center text-muted py-3 small no-rules-msg">No hay reglas. Haz clic en "Agregar regla".</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    // ---------------------------------------------------------------
    // Delete zone
    // ---------------------------------------------------------------
    $(document).on('click', '.btn-delete-zone', function (e) {
        e.preventDefault();
        deleteZoneId = $(this).data('zone-id');
        $('#delete-zone-name').text($(this).data('zone-title'));
        $('#delete-zone-modal').modal('show');
    });

    $('#btn-confirm-delete-zone').on('click', function () {
        if (!deleteZoneId) { return; }
        var $btn = $(this).prop('disabled', true).text('Eliminando...');
        $.ajax({
            url: getZoneDestroyUrl(deleteZoneId),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            success: function (res) {
                toastr.success(res.message);
                $('.zone-block[data-zone-id="' + deleteZoneId + '"]').remove();
                deleteZoneId = null;
                $('#delete-zone-modal').modal('hide');
                if (!$('.zone-block').length) {
                    $('#zones-container').append('<div class="text-center text-muted py-4" id="no-zones-msg">No hay zonas de envío. Crea una haciendo clic en "Seleccionar país".</div>');
                }
            },
            error: function () {
                toastr.error('Error al eliminar la zona.');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Eliminar');
            }
        });
    });

    // ---------------------------------------------------------------
    // Add rule
    // ---------------------------------------------------------------
    $(document).on('click', '.btn-add-rule', function (e) {
        e.preventDefault();
        $('#rule-zone-id').val($(this).data('zone-id'));
        $('#rule-name').val('');
        $('#rule-type').val('based_on_price').trigger('change');
        $('#rule-from').val('0');
        $('#rule-to').val('');
        $('#rule-price').val('0');
        $('#add-rule-modal').modal('show');
    });

    $('#btn-save-rule').on('click', function () {
        var zoneId = $('#rule-zone-id').val();
        var name = $('#rule-name').val().trim();
        var type = $('#rule-type').val();
        if (!name) {
            toastr.warning('El nombre de la regla es obligatorio.');
            return;
        }
        var payload = { name: name, type: type, price: 0 };
        if (ruleTypes[type] && ruleTypes[type].showFromTo) {
            payload.from = $('#rule-from').val() || null;
            payload.to = $('#rule-to').val() || null;
            payload.price = parseFloat($('#rule-price').val()) || 0;
        }
        var $btn = $(this).prop('disabled', true).text('Guardando...');
        $.ajax({
            url: getZoneStoreUrl(zoneId),
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            data: JSON.stringify(payload),
            success: function (res) {
                toastr.success(res.message);
                $('#add-rule-modal').modal('hide');
                var $accordion = $('#accordion-zone-' + zoneId);
                $accordion.find('.no-rules-msg').remove();
                var rule = res.data;
                // Build accordion item HTML inline for new rule
                var typeLabel = ruleTypes[rule.type] ? ruleTypes[rule.type].label : rule.type;
                var isLocation = rule.type === 'based_on_location';
                var priceDisplay = isLocation ? '' : '$ ' + parseFloat(rule.price).toLocaleString();
                var html = buildRuleAccordionHtml(rule, zoneId, typeLabel, isLocation, priceDisplay);
                $accordion.append(html);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errs = xhr.responseJSON.errors || {};
                    var msgs = Object.values(errs).flat();
                    toastr.error(msgs[0] || 'Validacion fallida.');
                } else {
                    toastr.error('Error al crear la regla.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).text('Agregar regla');
            }
        });
    });

    function buildRuleAccordionHtml(rule, zoneId, typeLabel, isLocation, priceDisplay) {
        var fromToClass = isLocation ? 'd-none' : '';
        var priceFieldClass = isLocation ? 'd-none' : '';
        var itemsDisplay = isLocation ? '' : 'style="display:none"';
        var escapedName = $('<div>').text(rule.name).html();
        return '<div class="accordion-item" id="rule-item-' + rule.id + '" data-rule-id="' + rule.id + '" data-zone-id="' + zoneId + '">' +
            '<div class="accordion-header">' +
                '<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-rule-' + rule.id + '">' +
                    '<div class="w-100 d-flex justify-content-between align-items-center pe-2">' +
                        '<div>' +
                            '<span class="fw-semibold rule-name-label">' + escapedName + '</span>' +
                            '<div class="small text-muted rule-type-label">' + typeLabel + '</div>' +
                        '</div>' +
                        (!isLocation ? '<span class="rule-price-label text-muted small">' + priceDisplay + '</span>' : '') +
                    '</div>' +
                '</button>' +
            '</div>' +
            '<div id="collapse-rule-' + rule.id + '" class="accordion-collapse collapse">' +
                '<div class="accordion-body">' +
                    '<form class="rule-edit-form row g-2 align-items-end" data-rule-id="' + rule.id + '" data-zone-id="' + zoneId + '">' +
                        '<div class="col-12 col-md-3">' +
                            '<label class="form-label form-label-sm mb-1">Nombre</label>' +
                            '<input type="text" name="name" class="form-control form-control-sm" value="' + escapedName + '" required>' +
                        '</div>' +
                        '<div class="col-12 col-md-3">' +
                            '<label class="form-label form-label-sm mb-1">Tipo</label>' +
                            '<select name="type" class="form-select form-select-sm rule-type-select">' +
                                '<option value="based_on_price"' + (rule.type === 'based_on_price' ? ' selected' : '') + '>Basado en el monto total</option>' +
                                '<option value="based_on_weight"' + (rule.type === 'based_on_weight' ? ' selected' : '') + '>Basado en el peso</option>' +
                                '<option value="based_on_zipcode"' + (rule.type === 'based_on_zipcode' ? ' selected' : '') + '>Basado en codigo postal</option>' +
                                '<option value="based_on_location"' + (rule.type === 'based_on_location' ? ' selected' : '') + '>Basado en ubicacion</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="rule-from-to-fields col-12 col-md-3 ' + fromToClass + '">' +
                            '<div class="row g-1">' +
                                '<div class="col-6"><label class="form-label form-label-sm mb-1">Desde</label><input type="number" name="from" class="form-control form-control-sm" value="' + (rule.from || '') + '" min="0" step="0.01"></div>' +
                                '<div class="col-6"><label class="form-label form-label-sm mb-1">Hasta</label><input type="number" name="to" class="form-control form-control-sm" value="' + (rule.to || '') + '" min="0" step="0.01"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="rule-price-field col-12 col-md-2 ' + priceFieldClass + '">' +
                            '<label class="form-label form-label-sm mb-1">Precio</label>' +
                            '<input type="number" name="price" class="form-control form-control-sm" value="' + (rule.price || 0) + '" min="0" step="0.01">' +
                        '</div>' +
                        '<div class="col-12 col-md-1 d-flex gap-2 justify-content-end">' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary btn-delete-rule" data-rule-id="' + rule.id + '" data-zone-id="' + zoneId + '" data-rule-name="' + escapedName + '">Eliminar</button>' +
                            '<button type="submit" class="btn btn-sm btn-primary">Guardar</button>' +
                        '</div>' +
                    '</form>' +
                    '<div class="items-container mt-3" data-rule-id="' + rule.id + '" data-zone-id="' + zoneId + '" ' + itemsDisplay + '>' +
                        '<div class="items-table-wrapper mb-2"><p class="text-muted small">Cargando tarifas...</p></div>' +
                        '<button type="button" class="btn btn-sm btn-outline-primary btn-add-city-rate" data-rule-id="' + rule.id + '" data-zone-id="' + zoneId + '">Agregar tarifa de ciudad</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    // ---------------------------------------------------------------
    // Save rule (inline edit form)
    // ---------------------------------------------------------------
    $(document).on('submit', '.rule-edit-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var ruleId = $form.data('rule-id');
        var zoneId = $form.data('zone-id');
        var type = $form.find('[name="type"]').val();
        var payload = {
            name: $form.find('[name="name"]').val(),
            type: type,
            price: parseFloat($form.find('[name="price"]').val()) || 0,
            from: $form.find('[name="from"]').val() || null,
            to: $form.find('[name="to"]').val() || null,
        };
        var $btn = $form.find('[type="submit"]').prop('disabled', true);
        $.ajax({
            url: getRuleUpdateUrl(zoneId, ruleId),
            method: 'PUT',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            data: JSON.stringify(payload),
            success: function (res) {
                toastr.success(res.message);
                var $item = $('#rule-item-' + ruleId);
                $item.find('.rule-name-label').text(res.data.name);
                var typeLabel = ruleTypes[type] ? ruleTypes[type].label : type;
                $item.find('.rule-type-label').text(typeLabel);
                var isLocation = type === 'based_on_location';
                if (!isLocation) {
                    $item.find('.rule-price-label').text('$ ' + parseFloat(res.data.price).toLocaleString());
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errs = xhr.responseJSON.errors || {};
                    toastr.error(Object.values(errs).flat()[0] || 'Validacion fallida.');
                } else {
                    toastr.error('Error al guardar la regla.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // ---------------------------------------------------------------
    // Delete rule
    // ---------------------------------------------------------------
    $(document).on('click', '.btn-delete-rule', function () {
        deleteRuleId = $(this).data('rule-id');
        deleteRuleZoneId = $(this).data('zone-id');
        $('#delete-rule-name').text($(this).data('rule-name'));
        $('#delete-rule-modal').modal('show');
    });

    $('#btn-confirm-delete-rule').on('click', function () {
        if (!deleteRuleId) { return; }
        var $btn = $(this).prop('disabled', true).text('Eliminando...');
        $.ajax({
            url: getRuleDestroyUrl(deleteRuleZoneId, deleteRuleId),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            success: function (res) {
                toastr.success(res.message);
                $('#rule-item-' + deleteRuleId).remove();
                deleteRuleId = null;
                deleteRuleZoneId = null;
                $('#delete-rule-modal').modal('hide');
            },
            error: function () {
                toastr.error('Error al eliminar la regla.');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Eliminar');
            }
        });
    });

    // ---------------------------------------------------------------
    // Add city rate
    // ---------------------------------------------------------------
    $(document).on('click', '.btn-add-city-rate', function () {
        $('#city-rate-rule-id').val($(this).data('rule-id'));
        $('#city-rate-zone-id').val($(this).data('zone-id'));
        $('#city-rate-state').val('');
        $('#city-rate-city').val('');
        $('#city-rate-price').val('0');
        $('#add-city-rate-modal').modal('show');
    });

    $('#btn-save-city-rate').on('click', function () {
        var ruleId = $('#city-rate-rule-id').val();
        var zoneId = $('#city-rate-zone-id').val();
        var payload = {
            state: $('#city-rate-state').val().trim(),
            city: $('#city-rate-city').val().trim(),
            adjustment_price: parseFloat($('#city-rate-price').val()) || 0,
        };
        var $btn = $(this).prop('disabled', true).text('Guardando...');
        $.ajax({
            url: getItemStoreUrl(zoneId, ruleId),
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            data: JSON.stringify(payload),
            success: function (res) {
                toastr.success(res.message);
                $('#add-city-rate-modal').modal('hide');
                loadItems(ruleId, zoneId);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errs = xhr.responseJSON.errors || {};
                    toastr.error(Object.values(errs).flat()[0] || 'Validacion fallida.');
                } else {
                    toastr.error('Error al guardar la tarifa.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).text('Guardar tarifa');
            }
        });
    });

    // ---------------------------------------------------------------
    // Delete city rate item
    // ---------------------------------------------------------------
    $(document).on('click', '.btn-delete-item', function (e) {
        e.preventDefault();
        var itemId = $(this).data('item-id');
        var ruleId = $(this).data('rule-id');
        var zoneId = $(this).data('zone-id');
        if (!confirm('¿Eliminar esta tarifa?')) { return; }
        $.ajax({
            url: getItemDestroyUrl(zoneId, ruleId, itemId),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            success: function (res) {
                toastr.success(res.message);
                loadItems(ruleId, zoneId);
            },
            error: function () {
                toastr.error('Error al eliminar la tarifa.');
            }
        });
    });
});
</script>
@endpush
