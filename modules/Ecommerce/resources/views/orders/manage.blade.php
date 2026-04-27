@extends('layouts.theme')

@section('title', 'Editar productos - ' . $order->code)

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Editar productos de la orden #' . $order->code])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <form action="{{ route('ecommerce.orders.manage.save', $order) }}" method="POST" id="manage-form">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">

                    {{-- Productos --}}
                    <div class="card mb-3">
                        <div class="card-header p-3 border-bottom border-light">
                            <h5 class="mb-0 fw-bold">Informacion del pedido</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Producto</th>
                                        <th class="text-center" style="width:100px">Cantidad</th>
                                        <th class="text-end" style="width:120px">Precio</th>
                                        <th class="text-end" style="width:120px">Total</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="items-body">
                                    @foreach($order->items as $i => $item)
                                        <tr class="item-row">
                                            <td class="ps-3">
                                                <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                                                <input type="hidden" name="items[{{ $i }}][price]" class="item-price" value="{{ $item->price }}">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($item->product_image)
                                                        <img src="{{ $item->product_image }}" class="rounded flex-shrink-0" width="40" height="40" style="object-fit:cover">
                                                    @else
                                                        <div class="rounded bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                                                            <i class="fas fa-box text-muted small"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-semibold small">{{ $item->product_name }}</div>
                                                        @if($item->product?->sku)
                                                            <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[{{ $i }}][qty]" class="form-control form-control-sm text-center item-qty" value="{{ $item->qty }}" min="1" style="width:70px;margin:auto">
                                            </td>
                                            <td class="text-end">${{ number_format($item->price, 2) }}</td>
                                            <td class="text-end fw-semibold item-total">${{ number_format($item->total, 2) }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger remove-item p-0">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="p-3 border-top">
                                <select id="add-product-select" class="form-select form-select-sm">
                                    <option value="">Buscar o agregar producto...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->final_price }}" data-name="{{ $product->name }}">
                                            {{ $product->name }} — ${{ number_format($product->final_price, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Totales --}}
                        <div class="card-footer">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nota del cliente</label>
                                    <textarea name="customer_note" class="form-control" rows="3" placeholder="Nota del pedido...">{{ $order->customer_note }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted">Importe secundario</td>
                                            <td class="text-end fw-semibold" id="summary-subtotal">${{ number_format($order->sub_total, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Importe del impuesto</td>
                                            <td class="text-end">
                                                <input type="number" step="0.01" name="tax_amount" id="tax-amount" class="form-control form-control-sm text-end" value="{{ $order->tax_amount }}" style="width:90px;display:inline-block">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">
                                                Monto de la promocion
                                                <div>
                                                    <button type="button" class="btn btn-outline-danger btn-sm mt-1" id="btn-discount">
                                                        Agregar descuento
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-end fw-semibold" id="summary-discount">-${{ number_format($order->discount_amount, 2) }}</td>
                                        </tr>
                                        <tr id="discount-panel" {{ $order->coupon_code ? '' : 'style="display:none"' }}>
                                            <td colspan="2" class="pt-0">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" id="coupon-input" class="form-control" placeholder="Código de descuento" value="{{ $order->coupon_code }}">
                                                    <button type="button" class="btn btn-outline-secondary" id="apply-coupon">Aplicar</button>
                                                </div>
                                                <input type="hidden" name="discount_amount" id="discount-amount" value="{{ $order->discount_amount }}">
                                                <input type="hidden" name="coupon_code" id="coupon-code" value="{{ $order->coupon_code }}">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#shippingModal">
                                                    Transporte
                                                </button>
                                                <small class="text-muted d-block" id="shipping-label">{{ $order->shipping_amount > 0 ? 'Personalizado' : 'Envío gratis' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <input type="number" step="0.01" name="shipping_amount" id="shipping-amount" class="form-control form-control-sm text-end" value="{{ $order->shipping_amount }}" style="width:90px;display:inline-block">
                                            </td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold">Cantidad total</td>
                                            <td class="text-end fw-bold fs-5 text-primary" id="summary-total">${{ number_format($order->total, 2) }}</td>
                                        </tr>
                                    </table>
                                    <input type="hidden" name="sub_total" id="input-subtotal" value="{{ $order->sub_total }}">
                                    <input type="hidden" name="total" id="input-total" value="{{ $order->total }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pago --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Metodo de pago</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="cash" {{ $order->payment_method === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                        <option value="transfer" {{ $order->payment_method === 'transfer' ? 'selected' : '' }}>Transferencia</option>
                                        <option value="card" {{ $order->payment_method === 'card' ? 'selected' : '' }}>Tarjeta</option>
                                        <option value="cod" {{ $order->payment_method === 'cod' ? 'selected' : '' }}>Pago contra entrega</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Estado de pago</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Pagado</option>
                                        <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Fallido</option>
                                        <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>Reembolsado</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">ID de transaccion</label>
                                    <input type="text" name="transaction_id" class="form-control" value="{{ $order->transaction_id }}" placeholder="Referencia de pago...">
                                    <small class="text-muted">Dejar vacío si el metodo de pago es contra reembolso.</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-credit-card text-muted"></i>
                                <span class="text-muted small fw-semibold text-uppercase">Guardar cambios en la orden</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('ecommerce.orders.show', $order) }}" class="btn btn-outline-secondary">Volver</a>
                                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Sidebar --}}
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Cliente</h6>
                        </div>
                        <div class="card-body">
                            @if($order->customer->id ?? null)
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0" style="width:42px;height:42px">
                                        {{ strtoupper(substr($order->customer->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-semibold">{{ $order->customer->name }}</p>
                                        <small class="text-muted">{{ $order->customer->orders()->count() }} pedidos</small>
                                    </div>
                                </div>
                                <p class="mb-1 small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $order->customer->email ?? '—' }}</p>
                                <p class="mb-0 small"><i class="fas fa-phone me-2 text-muted"></i>{{ $order->customer->phone ?? '—' }}</p>
                            @else
                                <p class="text-muted mb-0">Invitado</p>
                            @endif
                        </div>
                    </div>

                    @if($order->shippingAddress)
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0 fw-bold">Direccion</h6></div>
                            <div class="card-body">
                                <p class="mb-1 small fw-semibold">{{ $order->shippingAddress->name }}</p>
                                @if($order->shippingAddress->phone)
                                    <p class="mb-1 small"><i class="fas fa-phone me-2 text-muted"></i>{{ $order->shippingAddress->phone }}</p>
                                @endif
                                <p class="mb-0 small">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    {{ $order->shippingAddress->address }}, {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->country }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Modal Transporte --}}
    <div class="modal fade" id="shippingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Gastos de envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="shipping_type" id="shipping-free" value="free" checked>
                        <label class="form-check-label" for="shipping-free">Envío gratis</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="shipping_type" id="shipping-custom" value="custom">
                        <label class="form-check-label" for="shipping-custom">Personalizado</label>
                    </div>
                    <div id="custom-shipping-wrap" class="mt-3" style="display:none">
                        <select id="shipping-option-select" class="form-select">
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="apply-shipping">Actualizar</button>
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    let itemIndex = {{ $order->items->count() }};

    function recalculate() {
        let subtotal = 0;
        $('#items-body .item-row').each(function () {
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            const qty = parseInt($(this).find('.item-qty').val()) || 1;
            const total = price * qty;
            $(this).find('.item-total').text('$' + total.toFixed(2));
            subtotal += total;
        });
        const shipping = parseFloat($('#shipping-amount').val()) || 0;
        const tax = parseFloat($('#tax-amount').val()) || 0;
        const discount = parseFloat($('#discount-amount').val()) || 0;
        const total = Math.max(0, subtotal + shipping + tax - discount);
        $('#summary-subtotal').text('$' + subtotal.toFixed(2));
        $('#summary-discount').text('-$' + discount.toFixed(2));
        $('#summary-total').text('$' + total.toFixed(2));
        $('#input-subtotal').val(subtotal.toFixed(2));
        $('#input-total').val(total.toFixed(2));
    }

    $(document).on('input', '.item-qty, #shipping-amount, #tax-amount, #discount-amount', recalculate);

    $(document).on('click', '.remove-item', function () {
        $(this).closest('.item-row').remove();
        recalculate();
    });

    $('#add-product-select').on('change', function () {
        const id = $(this).val();
        if (!id) return;
        const option = $(this).find(':selected');
        const price = parseFloat(option.data('price'));
        const name = option.data('name');
        const row = `<tr class="item-row">
            <td class="ps-3">
                <input type="hidden" name="items[${itemIndex}][product_id]" value="${id}">
                <input type="hidden" name="items[${itemIndex}][price]" class="item-price" value="${price}">
                <div class="fw-semibold small">${name}</div>
            </td>
            <td class="text-center">
                <input type="number" name="items[${itemIndex}][qty]" class="form-control form-control-sm text-center item-qty" value="1" min="1" style="width:70px;margin:auto">
            </td>
            <td class="text-end">$${price.toFixed(2)}</td>
            <td class="text-end fw-semibold item-total">$${price.toFixed(2)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger remove-item p-0">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>`;
        $('#items-body').append(row);
        itemIndex++;
        $(this).val('');
        recalculate();
    });

    $('#apply-coupon').on('click', function () {
        const code = $('#coupon-input').val().trim();
        if (!code) return;
        $.ajax({
            url: '{{ route('ecommerce.discount.calculate') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { code, subtotal: parseFloat($('#input-subtotal').val()) || 0 },
            success: function (res) {
                $('#discount-amount').val(res.discount_amount);
                $('#coupon-code').val(res.code);
                recalculate();
                toastr.success(res.description || 'Descuento aplicado correctamente.');
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Código no válido.');
            }
        });
    });

    $('#btn-discount').on('click', function () {
        $('#discount-panel').toggle();
    });

    let shippingLoaded = false;

    $('input[name="shipping_type"]').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#custom-shipping-wrap').show();
            if (shippingLoaded) return;
            const subtotal = parseFloat($('#input-subtotal').val()) || 0;
            $.get('{{ route('ecommerce.shipping.options') }}', { subtotal }, function (options) {
                const select = $('#shipping-option-select').empty();
                if (!options.length) {
                    select.append('<option value="">Sin opciones disponibles</option>');
                    return;
                }
                options.forEach(function (opt) {
                    select.append(`<option value="${opt.id}" data-price="${opt.price}" data-title="${opt.title}">${opt.title} - $${opt.price.toFixed ? opt.price.toFixed(2) : opt.price}</option>`);
                });
                shippingLoaded = true;
            }).fail(function () {
                $('#shipping-option-select').html('<option value="">Error al cargar</option>');
            });
        } else {
            $('#custom-shipping-wrap').hide();
        }
    });

    $('#apply-shipping').on('click', function () {
        const type = $('input[name="shipping_type"]:checked').val();
        if (type === 'free') {
            $('#shipping-amount').val('0.00');
            $('#shipping-label').text('Envío gratis');
        } else {
            const selected = $('#shipping-option-select').find(':selected');
            const price = parseFloat(selected.data('price')) || 0;
            const title = selected.data('title') || 'Personalizado';
            $('#shipping-amount').val(price.toFixed(2));
            $('#shipping-label').text(title + (price > 0 ? ' — $' + price.toFixed(2) : ' — Envío gratis'));
        }
        recalculate();
        bootstrap.Modal.getInstance(document.getElementById('shippingModal')).hide();
    });

    recalculate();
});
</script>
@endpush
