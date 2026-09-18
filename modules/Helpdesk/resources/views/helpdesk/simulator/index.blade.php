@extends('layouts.theme')

@section('title', 'Banco de pruebas · Helpdesk')

@push('styles')
<style>
    .sim-customer-card { cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
    .sim-customer-card:hover { border-color: #90bb13; }
    .sim-customer-card.is-selected { border-color: #90bb13; box-shadow: 0 0 0 2px rgba(144,187,19,.25); }
    .sim-order-row { font-size: .85rem; }
    .sim-channel-btn.active { background-color: #90bb13; border-color: #90bb13; color: #fff; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Banco de pruebas · Helpdesk'])
@endsection

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0"><i class="fas fa-flask me-2 text-success"></i>Banco de pruebas omnicanal</h4>
            <small class="text-muted">Busca un cliente de PrestaShop/gestión, simula un mensaje entrante por canal y revisa que se cree la conversación con su contexto.</small>
        </div>
        <a href="{{ route('manager.helpdesk.conversations.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-inbox me-1"></i>Ir al inbox
        </a>
    </div>

    <div class="row g-3">
        {{-- Columna izquierda: buscador de clientes --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-transparent">
                    <strong>1. Buscar cliente</strong>
                    <small class="text-muted d-block">En PrestaShop (con pedidos) y enlazado a gestión (ERP)</small>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="simSearch" class="form-control" placeholder="Email o nombre del cliente…" autocomplete="off">
                    </div>
                    <div id="simResults" class="d-flex flex-column gap-2">
                        <div class="text-muted text-center py-4"><i class="fas fa-user-magnifying-glass fa-2x mb-2 d-block opacity-50"></i>Escribe al menos 2 caracteres para buscar.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna derecha: simular canal + resultado --}}
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header bg-transparent"><strong>2. Simular mensaje entrante</strong></div>
                <div class="card-body">
                    <div id="simSelectedInfo" class="alert alert-light border d-none mb-3"></div>

                    <label class="form-label">Canal</label>
                    <div class="btn-group w-100 mb-3" role="group" id="simChannels">
                        <button type="button" class="btn btn-outline-success sim-channel-btn active" data-channel="whatsapp"><i class="fab fa-whatsapp me-1"></i>WhatsApp</button>
                        <button type="button" class="btn btn-outline-success sim-channel-btn" data-channel="facebook"><i class="fab fa-facebook-messenger me-1"></i>Facebook</button>
                        <button type="button" class="btn btn-outline-success sim-channel-btn" data-channel="instagram"><i class="fab fa-instagram me-1"></i>Instagram</button>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del remitente</label>
                            <input type="text" id="simName" class="form-control" placeholder="Nombre cliente">
                        </div>
                        <div class="col-md-6" id="simPhoneWrap">
                            <label class="form-label">Teléfono (WhatsApp)</label>
                            <input type="text" id="simPhone" class="form-control" placeholder="34600000001">
                        </div>
                    </div>

                    <label class="form-label">Mensaje</label>
                    <textarea id="simMessage" class="form-control mb-3" rows="2" placeholder="Hola, ¿dónde está mi pedido?">Hola, necesito información sobre mi pedido.</textarea>

                    <button type="button" id="simSend" class="btn btn-success w-100">
                        <i class="fas fa-paper-plane me-1"></i>Simular mensaje entrante
                    </button>
                </div>
            </div>

            {{-- Resultado de la simulación --}}
            <div class="card mb-3 d-none" id="simResultCard">
                <div class="card-header bg-transparent"><strong>3. Resultado</strong></div>
                <div class="card-body" id="simResultBody"></div>
            </div>

            {{-- Pedidos PrestaShop del cliente --}}
            <div class="card d-none" id="simOrdersCard">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <strong>Pedidos en PrestaShop</strong>
                    <span class="badge bg-secondary" id="simOrdersTotal">0</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead><tr><th scope="col">Referencia</th><th scope="col">Estado</th><th scope="col" class="text-end">Total</th><th scope="col">Fecha</th></tr></thead>
                            <tbody id="simOrdersBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const urls = {
        customers: @json(route('manager.helpdesk.simulator.customers')),
        orders: @json(route('manager.helpdesk.simulator.orders')),
        simulate: @json(route('manager.helpdesk.simulator.simulate')),
    };
    let selected = null;
    let channel = 'whatsapp';
    let searchTimer = null;

    function escapeHtml(s) { return $('<div>').text(s ?? '').html(); }

    // --- Buscar clientes ---
    $('#simSearch').on('input', function () {
        const q = $(this).val().trim();
        clearTimeout(searchTimer);
        if (q.length < 2) { return; }
        searchTimer = setTimeout(() => searchCustomers(q), 300);
    });

    function searchCustomers(q) {
        $('#simResults').html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm text-success"></span></div>');
        $.get(urls.customers, { q })
            .done(res => renderCustomers(res.data || []))
            .fail(() => $('#simResults').html('<div class="text-danger small py-3">Error al buscar en PrestaShop.</div>'));
    }

    function renderCustomers(list) {
        if (!list.length) {
            $('#simResults').html('<div class="text-muted text-center py-4">Sin resultados.</div>');
            return;
        }
        const html = list.map(c => {
            const gestion = c.in_gestion
                ? `<span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-database me-1"></i>Gestión #${c.gestion_id}</span>`
                : `<span class="badge bg-light text-muted border">Sin gestión</span>`;
            return `<div class="card sim-customer-card border" data-c='${escapeHtml(JSON.stringify(c))}'>
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">${escapeHtml(c.name) || '(sin nombre)'}</div>
                            <div class="small text-muted">${escapeHtml(c.email)}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary-subtle text-primary border border-primary"><i class="fas fa-bag-shopping me-1"></i>${c.ps_orders} pedidos</span>
                        </div>
                    </div>
                    <div class="mt-1">${gestion}</div>
                </div>
            </div>`;
        }).join('');
        $('#simResults').html(html);
    }

    // --- Seleccionar cliente ---
    $('#simResults').on('click', '.sim-customer-card', function () {
        $('.sim-customer-card').removeClass('is-selected');
        $(this).addClass('is-selected');
        selected = JSON.parse($(this).attr('data-c'));
        $('#simSelectedInfo').removeClass('d-none').html(
            `<i class="fas fa-user-check text-success me-1"></i><strong>${escapeHtml(selected.name)}</strong> · ${escapeHtml(selected.email)} · PS #${selected.prestashop_id}` +
            (selected.gestion_id ? ` · Gestión #${selected.gestion_id}` : '')
        );
        $('#simName').val(selected.name);
        loadOrders(selected);
    });

    // --- Cambiar canal ---
    $('#simChannels').on('click', '.sim-channel-btn', function () {
        $('.sim-channel-btn').removeClass('active');
        $(this).addClass('active');
        channel = $(this).data('channel');
        $('#simPhoneWrap').toggleClass('d-none', channel !== 'whatsapp');
    });

    // --- Pedidos del cliente ---
    function loadOrders(c) {
        $('#simOrdersCard').removeClass('d-none');
        $('#simOrdersBody').html('<tr><td colspan="4" class="text-center py-3"><span class="spinner-border spinner-border-sm text-success"></span></td></tr>');
        $.get(urls.orders, { email: c.email, prestashop_id: c.prestashop_id })
            .done(res => {
                $('#simOrdersTotal').text(res.total ?? (res.data || []).length);
                const rows = (res.data || []).map(o => `<tr class="sim-order-row">
                    <td>${escapeHtml(o.reference ?? o.id)}</td>
                    <td><span class="badge bg-light text-dark border">${escapeHtml(o.state_name ?? o.status ?? '—')}</span></td>
                    <td class="text-end">${escapeHtml(String(o.total ?? ''))}</td>
                    <td class="text-muted">${escapeHtml((o.created_at ?? o.placed_at ?? '').substring(0,10))}</td>
                </tr>`).join('');
                $('#simOrdersBody').html(rows || '<tr><td colspan="4" class="text-center text-muted py-3">Sin pedidos.</td></tr>');
            })
            .fail(() => $('#simOrdersBody').html('<tr><td colspan="4" class="text-danger small py-3">Error al cargar pedidos.</td></tr>'));
    }

    // --- Simular ---
    $('#simSend').on('click', function () {
        const message = $('#simMessage').val().trim();
        if (!message) { toastr.warning('Escribe un mensaje.'); return; }
        const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Procesando…');

        $.ajax({
            url: urls.simulate, method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: {
                channel, message,
                name: $('#simName').val(),
                phone: $('#simPhone').val(),
                email: selected ? selected.email : null,
                prestashop_id: selected ? selected.prestashop_id : null,
                gestion_id: selected ? selected.gestion_id : null,
            },
        }).done(res => {
            toastr.success('Conversación #' + res.conversation_id + ' creada por ' + res.channel + '.');
            $('#simResultCard').removeClass('d-none');
            $('#simResultBody').html(
                `<div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold"><i class="fas fa-comments text-success me-1"></i>${escapeHtml(res.subject) || ('Conversación #' + res.conversation_id)}</div>
                        <div class="small text-muted">Canal: ${escapeHtml(res.channel)} · Conversación #${res.conversation_id} · Cliente #${res.customer_id}</div>
                    </div>
                    <a href="${res.url}" class="btn btn-success btn-sm" target="_blank"><i class="fas fa-arrow-up-right-from-square me-1"></i>Abrir conversación</a>
                </div>`
            );
        }).fail(xhr => {
            const msg = xhr.responseJSON?.error || (xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join(' ') : 'Error al simular.');
            toastr.error(msg);
        }).always(() => {
            $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i>Simular mensaje entrante');
        });
    });
});
</script>
@endpush
