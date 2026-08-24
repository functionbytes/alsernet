@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $pageTitle }}</h5>
                        <p class="small mb-0 text-muted">Genera los PDF de sobre y tarjeta a partir del mensaje regalo de un pedido.</p>
                    </div>
                    <div class="d-flex gap-2">
                    </div>
                </div>
            </div>

            {{-- Paso 1: buscar pedidos --}}
            <div class="card-body border-bottom">
                <h6 class="fw-bold mb-1">Paso 1 &middot; Buscar pedidos</h6>
                <p class="text-muted small mb-3">Busca por cualquiera de los numeros del pedido, o revisa el listado reciente que ya trae mensaje regalo.</p>

                <div class="d-flex gap-2 align-items-start">
                    <div class="flex-fill">
                        <input type="text" id="gestion-search" class="form-control"
                               placeholder="Ej: 29394,102204020,833253">
                        <small class="form-text text-muted">Acepta el n. de pedido del ERP (npedidocli), su ID de gestion o el id de pedido de PrestaShop, separados por comas.</small>
                    </div>
                    <button type="button" id="gestion-search-btn" class="btn btn-primary" title="Buscar">
                        <i class="fas fa-magnifying-glass"></i>
                    </button>
                    <button type="button" id="gestion-reset-btn" class="btn btn-secondary" title="Limpiar">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>

            {{-- Paso 2: listado + seleccion --}}
            <div class="card-body {{ count($orders) > 0 ? '' : 'd-none' }}" id="orders-results-card">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Paso 2 &middot; Selecciona los pedidos</h6>
                        <p class="text-muted small mb-0"><span id="orders-count">{{ count($orders) }}</span> pedido(s) con mensaje regalo. Marca los que quieras incluir en el PDF.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="orders-table">
                        <thead class="table-light">
                            <tr>
                                <th width="3%"><input type="checkbox" class="form-check-input" id="select-all"></th>
                                <th>ID pedido</th>
                                <th>N. pedido</th>
                                <th>ID gestion</th>
                                <th>Nombre</th>
                                <th>Mensaje</th>
                                <th>PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                @php
                                    // Ya no tiene sentido seleccionar el pedido para generar si ya
                                    // tiene algun PDF; "Ver sobre"/"Ver tarjeta" en la columna PDF
                                    // son la via para volver a verlos. Ni siquiera se pinta el
                                    // checkbox, para que no parezca una opcion disponible.
                                    $hasPdf = ! empty($order['existing_generations']);
                                @endphp
                                <tr>
                                    <td>
                                        @unless($hasPdf)
                                            <input type="checkbox" class="form-check-input order-checkbox"
                                                   value="{{ $order['id_order'] }}"
                                                   data-id-order="{{ $order['id_order'] }}"
                                                   data-gift-message="{{ $order['gift_message'] }}"
                                                   data-firstname="{{ $order['firstname'] ?? '' }}"
                                                   data-lastname="{{ $order['lastname'] ?? '' }}"
                                                   data-id-gestion="{{ $order['id_gestion'] ?? '' }}"
                                                   data-npedidocli="{{ $order['npedidocli'] ?? '' }}">
                                        @endunless
                                    </td>
                                    <td>{{ $order['id_order'] }}</td>
                                    <td>{{ $order['npedidocli'] ?? '—' }}</td>
                                    <td>{{ $order['id_gestion'] ?? '—' }}</td>
                                    <td>{{ trim(($order['firstname'] ?? '').' '.($order['lastname'] ?? '')) }}</td>
                                    <td>{{ $order['gift_message'] }}</td>
                                    <td>
                                        @if(! empty($order['existing_generations']))
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($order['existing_generations'] as $generation)
                                                    <a href="{{ route('giftmessage.history.view', $generation['id']) }}"
                                                       target="_blank" rel="noopener"
                                                       class="badge bg-secondary-subtle text-secondary text-decoration-none"
                                                       title="Generado el {{ \Illuminate\Support\Carbon::parse($generation['created_at'])->format('d/m/Y H:i') }}">
                                                        <i class="fas fa-file-pdf me-1"></i>Ver {{ $generation['type'] === 'card' ? 'tarjeta' : 'sobre' }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Estado vacio: nada buscado todavia --}}
            <div class="card-body {{ count($orders) > 0 ? 'd-none' : '' }}" id="orders-empty-state">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-gift fa-3x mb-3 d-block opacity-50"></i>
                    <h6 class="mb-1">Todavia no hay pedidos en pantalla</h6>
                    <p class="small mb-0">Busca por numero de pedido arriba para empezar.</p>
                </div>
            </div>

        </div>
    </div>

    {{-- Paso 3: bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Generar PDF
        </button>
    </div>

    {{-- Paso 3: modal para confirmar tipo de PDF --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generar PDF de mensaje regalo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se generara un PDF con <strong><span data-bulk-count>0</span> pedido(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de PDF</label>
                        <select id="bulk-type-select" class="form-select">
                            <option value="envelope">Sobre</option>
                            <option value="card">Tarjeta</option>
                            <option value="both">Ambos (sobre y tarjeta)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Generar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.GIFTMESSAGE_ORDERS = {
            urls: {
                ordersSearch: "{{ route('giftmessage.orders.search') }}",
                generate: "{{ route('giftmessage.generate') }}",
                historyView: "{{ route('giftmessage.history.view', '__ID__') }}",
            },
        };
    </script>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js') }}"></script>
@php
    // Cache-busting: sin esto el navegador puede servir una copia vieja de
    // orders.js tras un deploy y las mejoras nuevas no se ven hasta un hard-refresh.
    $giftmessageAssetVersion = fn (string $path) => file_exists(public_path($path)) ? filemtime(public_path($path)) : time();
@endphp
<script src="{{ asset('modules/giftmessage/js/orders.js') }}?v={{ $giftmessageAssetVersion('modules/giftmessage/js/orders.js') }}"></script>
@endpush
