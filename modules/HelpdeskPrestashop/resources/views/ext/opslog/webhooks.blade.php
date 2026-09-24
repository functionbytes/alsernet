@extends('layouts.theme')

@section('title', 'Eventos recibidos')

@section('page_header')
    @include('core::components.card', [
        'title' => 'Eventos recibidos',
        'description' => 'Lo que PrestaShop avisa al helpdesk: pedidos, carritos, clientes y productos.',
    ])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-chat.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/prestashop-chat.css')) }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/opslog.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/opslog.css')) }}">
@endpush

@section('content')
@php
    $filterUrl = fn (string $s) => route('manager.helpdesk.ps.ext.opslog.events', array_filter(['status' => $s === 'all' ? null : $s, 'event' => $event]));
@endphp
<div class="psc-opslog psc-opslog--events" id="pscOpslogEvents">

    @if (! $available)
        <div class="psc-card">
            <div class="psc-state">
                <i class="fa fa-inbox"></i>
                <span class="t">El registro de eventos aún no está instalado</span>
                <span class="s">Falta ejecutar la migración de HelpdeskPrestashop que crea la tabla de eventos recibidos. Hasta entonces los eventos se siguen procesando, pero no se guardan.</span>
            </div>
        </div>
    @else
        <div class="psc-opslog-toolbar">
            <div class="psc-chips" role="group" aria-label="Estado">
                <a href="{{ $filterUrl('all') }}" class="psc-chip {{ $status === 'all' ? 'is-on' : '' }}">Todos {{ number_format($counts['all'], 0, ',', '.') }}</a>
                <a href="{{ $filterUrl('processed') }}" class="psc-chip {{ $status === 'processed' ? 'is-on' : '' }}">Procesados {{ number_format($counts['processed'], 0, ',', '.') }}</a>
                <a href="{{ $filterUrl('pending') }}" class="psc-chip {{ $status === 'pending' ? 'is-on' : '' }}">Pendientes {{ number_format($counts['pending'], 0, ',', '.') }}</a>
            </div>
            <form method="GET" action="{{ route('manager.helpdesk.ps.ext.opslog.events') }}" class="psc-field psc-opslog-evfilter" id="pscOpslogEventFilter">
                @if ($status !== 'all')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <label class="visually-hidden" for="pscOpslogEventSelect">Tipo de evento</label>
                <select name="event" id="pscOpslogEventSelect">
                    <option value="">Todos los tipos</option>
                    @foreach ($eventNames as $name)
                        <option value="{{ $name }}" @selected($event === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="psc-card">
            <div class="psc-card-body psc-opslog-evlist">
                @forelse ($events as $row)
                    @include('helpdeskprestashop::ext.opslog.partials.event-row', ['row' => $row, 'canReprocess' => $canReprocess])
                @empty
                    <div class="psc-state">
                        <i class="fa fa-inbox"></i>
                        <span class="t">{{ $status === 'pending' ? 'No hay eventos pendientes' : 'Todavía no ha llegado ningún evento' }}</span>
                        <span class="s">
                            @if ($status === 'pending')
                                Todos los eventos recibidos se han podido vincular a un cliente del helpdesk.
                            @else
                                Aparecerán aquí en cuanto PrestaShop envíe el primero a este panel.
                            @endif
                        </span>
                    </div>
                @endforelse
            </div>
        </div>

        @if ($events && $events->hasPages())
            <div class="psc-opslog-pages">{{ $events->links() }}</div>
        @endif

        <div class="psc-note psc-note--info">
            <span class="psc-note-txt">
                Un evento queda <b>pendiente</b> cuando trae un cliente de PrestaShop que no existe en el helpdesk.
                «Reprocesar» vuelve a buscarlo y, si ya está, repite el evento para que se aplique.
                Se guardan los últimos {{ $retentionDays }} días.
            </span>
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/opslog.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/opslog.js')) }}" defer></script>
@endpush
