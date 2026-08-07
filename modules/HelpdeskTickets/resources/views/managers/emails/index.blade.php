@extends('layouts.theme')

@section('title', 'Emails enviados')

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/tickets.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/emails.css') }}">
@endpush

@php
    // TicketMail::toListRow() es la única fuente del contrato de fila: la
    // usan tanto esta hidratación SSR como TicketMailsController::index()/
    // data() (JSON re-consultado al filtrar/paginar, ver emails.js::refetch()).
    // Tenerlo en un solo sitio evita que SSR y AJAX diverjan en los nombres
    // de campo — ya pasó dos veces mientras se escribía esta pantalla.
    $mailsPayload = $mails->getCollection()->map(fn ($mail) => $mail->toListRow())->values();
@endphp

@section('page_header')
    @include('core::components.card', ['title' => 'Emails enviados'])
@endsection

@section('content')
<div class="eml">

    {{-- Datos para el JS --}}
    <div id="eml-data"
         data-mails="{{ json_encode($mailsPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-stats="{{ json_encode($stats, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-categories="{{ json_encode($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-agents="{{ json_encode($agents->map(fn ($a) => ['id' => $a->id, 'name' => trim($a->firstname.' '.$a->lastname)]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-initial-view="{{ $filters['view'] ?? 'outbound' }}"
         data-index-url="{{ route('manager.helpdesk.tickets.emails.index') }}"
         data-store-url="{{ route('manager.helpdesk.tickets.emails.store') }}"
         data-bulk-url="{{ route('manager.helpdesk.tickets.emails.bulk') }}"
         data-export-url="{{ route('manager.helpdesk.tickets.emails.export') }}"
         hidden>
    </div>

    <div class="eml-app-card">

        {{-- Barra superior --}}
        <div class="eml-app-bar">
            <div class="eml-search-field" style="flex:1 1 240px;min-width:0;max-width:340px">
                <i class="fa-solid fa-magnifying-glass" style="font-size:11px;color:var(--eml-text-muted)"></i>
                <input id="eml-search" placeholder="Buscar por asunto, destinatario, ticket…">
            </div>
            <button type="button" class="eml-btn eml-btn-primary" style="width:auto;margin-left:auto" data-eml-open="compose">
                <i class="fas fa-pen"></i> Redactar
            </button>
        </div>

        {{-- Tabs --}}
        <div class="eml-app-tabs">
            <div class="eml-app-tab" data-eml-view="outbound">Enviados</div>
            <div class="eml-app-tab" data-eml-view="scheduled">Programados <span class="eml-mono" id="eml-tab-scheduled-count"></span></div>
            <div class="eml-app-tab" data-eml-view="bounced">Rebotados</div>
            <div class="eml-app-tab" data-eml-view="failed">Fallidos</div>
            <div class="eml-app-tab" data-eml-view="inbound">Recibidos</div>
        </div>

        {{-- Filtros --}}
        <div class="eml-filter-bar">
            <select id="eml-filter-category" class="eml-fselect" style="width:auto">
                <option value="">Categoría: todas</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select id="eml-filter-agent" class="eml-fselect" style="width:auto">
                <option value="">Agente: todos</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}">{{ trim($agent->firstname.' '.$agent->lastname) }}</option>
                @endforeach
            </select>
            <input type="date" id="eml-filter-from" class="eml-finput" style="width:auto">
            <span style="color:var(--eml-text-muted)">–</span>
            <input type="date" id="eml-filter-to" class="eml-finput" style="width:auto">
            <span id="eml-count" class="eml-mono" style="margin-left:auto;color:var(--eml-text-muted);font-size:10.5px"></span>
        </div>

        {{-- KPIs --}}
        <div class="eml-kpi-bar">
            <div class="eml-kpi"><div class="eml-kpi-value" id="eml-kpi-total">{{ $stats['total'] }}</div><div class="eml-kpi-label">Enviados</div></div>
            <div class="eml-kpi"><div class="eml-kpi-value" id="eml-kpi-bounced">{{ $stats['bounced'] }}</div><div class="eml-kpi-label">Rebotados</div></div>
            <div class="eml-kpi"><div class="eml-kpi-value" id="eml-kpi-bounce-rate">{{ $stats['bounce_rate'] }}%</div><div class="eml-kpi-label">Tasa de rebote</div></div>
            <div class="eml-kpi"><div class="eml-kpi-value" id="eml-kpi-scheduled">{{ $stats['scheduled'] }}</div><div class="eml-kpi-label">Programados</div></div>
        </div>

        <div class="eml-split-scroll">
        <div class="eml-split">

            {{-- Columna: lista --}}
            <div class="eml-list-col">
                <div class="eml-list-head">
                    <input type="checkbox" id="eml-select-all">
                    <span style="font-size:11px;color:var(--eml-text-muted)">Seleccionar todo</span>
                </div>
                <div class="eml-bulk-bar" id="eml-bulk-bar">
                    <span id="eml-bulk-count" style="font-size:11.5px;font-weight:700">0 seleccionados</span>
                    <span style="margin-left:auto;display:flex;gap:8px">
                        <button type="button" class="eml-btn" id="eml-bulk-resend">Reenviar</button>
                        <button type="button" class="eml-btn" id="eml-bulk-export">Exportar</button>
                        <button type="button" class="eml-btn" id="eml-bulk-cancel">Cancelar programados</button>
                        <button type="button" class="eml-btn" id="eml-bulk-clear">Quitar</button>
                    </span>
                </div>
                <div class="eml-list" id="eml-list"></div>
                <div class="eml-list-foot">
                    <span id="eml-pagination-summary">{{ $mails->firstItem() ?? 0 }}–{{ $mails->lastItem() ?? 0 }} de {{ $mails->total() }}</span>
                    @if($mails->hasPages())
                        <span style="margin-left:auto">{{ $mails->onEachSide(1)->links() }}</span>
                    @endif
                </div>
            </div>

            {{-- Columna: detalle --}}
            <div class="eml-detail-col" id="eml-detail-col">
                <div class="eml-empty-state" id="eml-detail-empty">
                    <div style="width:52px;height:52px;border-radius:14px;background:#fff;border:1px solid var(--eml-border);display:grid;place-items:center;color:var(--eml-text-muted);font-size:19px"><i class="fa-regular fa-envelope-open"></i></div>
                    <div style="font-size:14px;font-weight:700">Ningún email seleccionado</div>
                    <div style="font-size:12px;color:var(--eml-text-soft);max-width:300px;line-height:1.6">Elige un correo de la lista para ver su contenido y el hilo del ticket asociado.</div>
                </div>
                <div id="eml-detail" style="display:none"></div>
            </div>

            {{-- Columna: panel lateral --}}
            <div class="eml-side-panel" id="eml-side-panel"></div>

        </div>
        </div>
    </div>
</div>

@include('helpdesktickets::managers.emails.partials._compose-modal')

@endsection

@push('scripts')
    <script src="{{ asset('modules/helpdesktickets/js/emails.js') }}"></script>
@endpush
