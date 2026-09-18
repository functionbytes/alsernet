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
            Ir al inbox
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
                        <button type="button" class="btn btn-outline-success sim-channel-btn active" data-channel="whatsapp">WhatsApp</button>
                        <button type="button" class="btn btn-outline-success sim-channel-btn" data-channel="facebook">Facebook</button>
                        <button type="button" class="btn btn-outline-success sim-channel-btn" data-channel="instagram">Instagram</button>
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
                        Simular mensaje entrante
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
    window.HelpdeskSimulatorUrls = {
        customers: @json(route('manager.helpdesk.simulator.customers')),
        orders: @json(route('manager.helpdesk.simulator.orders')),
        simulate: @json(route('manager.helpdesk.simulator.simulate')),
    };
</script>
<script src="{{ asset('vendor/helpdesk/manager-simulator.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/manager-simulator.js')) }}" defer></script>
@endpush
