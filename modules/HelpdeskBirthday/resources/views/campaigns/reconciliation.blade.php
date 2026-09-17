@extends('layouts.theme')
@section('title', 'Descuadre · Campaña del '.$campaign->campaign_date->format('d/m/Y'))
@section('page_header')
    @include('core::components.card', ['title' => 'Descuadre con gestión'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

<div class="bd-panel">

@include('helpdeskbirthday::campaigns._header')

<div class="row g-3 mb-3">
    @php
        $kpis = [
            ['label' => 'Sin registrar', 'value' => $summary['pending'], 'hint' => 'bonos de esta campaña'],
            ['label' => 'Importe en riesgo', 'value' => number_format($summary['amount'], 2, ',', '.').' €', 'hint' => 'se puede volver a gastar'],
            ['label' => 'Se pueden marcar', 'value' => $summary['markable'], 'hint' => $summary['unmarkable'] > 0 ? $summary['unmarkable'].' sin código conocido' : 'todos tienen código'],
            ['label' => 'En toda la tienda', 'value' => $global['pending'], 'hint' => number_format($global['amount'], 0, ',', '.').' € acumulados'],
        ];
    @endphp

    @foreach($kpis as $kpi)
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase bd-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="fw-bold bd-kpi-value">{{ $kpi['value'] }}</div>
                    <div class="text-muted small">{{ $kpi['hint'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- El descuadre no lo causó este módulo, y decirlo evita que alguien salga a
     marcar cientos de bonos creyendo que arregla un fallo de hoy. --}}
@if($global['pending'] > $summary['pending'])
    <div class="alert alert-warning">
        <strong>El descuadre viene de antes de este módulo.</strong>
        En toda la tienda hay {{ $global['pending'] }} bonos gastados sin registrar en gestión, por
        {{ number_format($global['amount'], 2, ',', '.') }} €
        @if($global['oldest'])
            —el más antiguo del {{ \Illuminate\Support\Carbon::parse($global['oldest'])->format('d/m/Y') }}—.
        @else
            .
        @endif
        La tienda dejó de registrarlos en mayo de 2025: el código que lo hacía ya no está en
        PrestaShop —la clase sigue ahí, pero no la llama nadie—, así que marcar desde aquí es la
        única vía que queda y no puede producir un consumo doble.
        Aun así, cada bono se comprueba en gestión al marcarlo: si estaba caducado, ya consumido o
        su pedido acabó devuelto, el ERP lo rechaza y el motivo queda en su fila.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-1">Bonos gastados que gestión no registró</h6>
        <p class="text-muted small mb-3">
            La tienda ya le hizo el descuento al cliente, pero el ERP no restó el bono: sigue activo
            y se puede volver a usar. Marcar escribe en gestión y <strong>no se puede deshacer</strong>,
            así que se hace sobre lo que selecciones, no sobre todo.
        </p>

        <p class="text-muted small bd-table-hint">Desliza la tabla para ver el resto de columnas.</p>

        <form method="POST" action="{{ route('helpdeskbirthday.campaigns.reconcile', $campaign) }}" id="bd-reconcile-form">
            @csrf

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            @can('helpdeskbirthday.manage')
                                <th class="bd-check-col">
                                    <input type="checkbox" class="form-check-input" id="bd-check-all" aria-label="Seleccionar todos los marcables">
                                </th>
                            @endcan
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Bono</th>
                            <th>Pedido</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Descuento</th>
                            <th>Gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                @can('helpdeskbirthday.manage')
                                    <td>
                                        @if($row->canBeMarkedInErp())
                                            <input type="checkbox" class="form-check-input bd-reconcile-check" name="ids[]" value="{{ $row->id }}"
                                                   aria-label="Marcar el bono {{ $row->coupon_code }}">
                                        @endif
                                    </td>
                                @endcan
                                <td class="bd-timeline-slot">
                                    {{ $row->ordered_at?->format('d/m/Y H:i') ?: '—' }}
                                </td>
                                <td>{{ $row->customer_email ?: '—' }}</td>
                                <td>
                                    @if($row->coupon_code)
                                        <code class="small">{{ $row->coupon_code }}</code>
                                        <small class="text-muted d-block">
                                            {{ $row->code_source === 'erp' ? 'según gestión' : 'según la tienda' }}
                                        </small>
                                    @else
                                        {{-- Ni la tienda ni gestión lo conservan: se ve el canje,
                                             pero no hay bono que marcar. --}}
                                        <span class="text-muted">Sin código</span>
                                        <small class="text-muted d-block">no se puede marcar</small>
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $row->ps_order_reference ?: '#'.$row->ps_order_id }}</code>
                                    <small class="text-muted d-block">PS #{{ $row->ps_order_id }}</small>
                                </td>
                                <td>
                                    <span class="badge bd-badge {{ $row->order_valid ? 'bd-badge--live' : 'bd-badge--done' }}">
                                        {{ $row->order_state ?: '—' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->order_total, 2, ',', '.') }} €</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $row->discount, 2, ',', '.') }} €</td>
                                <td>
                                    @if($row->erp_response)
                                        {{-- Se intentó y gestión contestó otra cosa: el texto es
                                             accionable (bono caducado, importe mínimo, código
                                             incorrecto), así que se enseña tal cual. --}}
                                        <span class="badge bd-badge bd-badge--done">Rechazado</span>
                                        <small class="text-muted d-block" title="{{ $row->erp_response }}">
                                            {{ \Illuminate\Support\Str::limit($row->erp_response, 40) }}
                                        </small>
                                    @else
                                        <span class="text-muted">Sin registro</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Todos los bonos gastados de esta campaña están registrados en gestión.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @can('helpdeskbirthday.manage')
                @if($summary['markable'] > 0)
                    <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
                        {{-- type="button": con submit enviaría el formulario Y abriría el modal. --}}
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bd-reconcile-modal">
                            Marcar los seleccionados en gestión
                        </button>
                        <span class="text-muted small" id="bd-reconcile-count">Ninguno seleccionado</span>
                    </div>
                @endif
            @endcan
        </form>

        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>

@can('helpdeskbirthday.manage')
    <div class="modal fade" id="bd-reconcile-modal" tabindex="-1" aria-labelledby="bd-reconcile-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bd-reconcile-title">Marcar los bonos en gestión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p><span id="bd-reconcile-modal-count">0</span> bonos se marcarán como consumidos en el ERP.</p>
                    <p class="text-muted small mb-0">
                        Esto escribe en gestión y no se puede deshacer. Si alguno estaba caducado, ya
                        consumido, o su pedido acabó devuelto, gestión lo rechazará y el motivo quedará
                        en su fila.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="bd-reconcile-form" class="btn btn-primary w-100 mb-2">Marcarlos en gestión</button>
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Volver</button>
                </div>
            </div>
        </div>
    </div>
@endcan

@include('helpdeskbirthday::campaigns._cancel-modal')

</div>

@push('scripts')
<script src="{{ asset('modules/helpdeskbirthday/js/reconciliation.js') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/js/reconciliation.js')) }}" defer></script>
@endpush

@endsection
