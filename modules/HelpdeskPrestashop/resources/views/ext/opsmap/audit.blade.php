@extends('layouts.theme')

@section('title', 'Auditoría de acciones en PrestaShop')

@section('page_header')
    @include('core::components.card', ['title' => 'Auditoría de acciones', 'subtitle' => 'Todo lo que los agentes han cambiado en la tienda desde el panel'])
@endsection

@include('helpdeskprestashop::ext.opsmap._assets')

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Acciones contra la tienda</h5>
                    <small class="text-muted">Cada acción escrita contra la tienda queda ligada al agente y a la conversación desde la que se hizo.</small>
                </div>

                <div class="card-body psc-opsmap-body">
                    <form method="GET" action="{{ route('manager.helpdesk.ps.ext.opsmap.audit') }}" class="psc-opsmap-filters" id="psOpsmapAuditFilters">
                        <label class="psc-opsmap-search">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <input type="search" name="q" value="{{ $search }}" placeholder="Agente, pedido o cliente…" autocomplete="off" aria-label="Buscar por agente, pedido o cliente">
                        </label>
                        <select name="days" class="form-select psc-opsmap-select" aria-label="Periodo" data-opsmap-autosubmit>
                            @foreach ($periods as $period)
                                <option value="{{ $period }}" @selected($days === $period)>{{ $period }} días</option>
                            @endforeach
                        </select>
                        <select name="agent" class="form-select psc-opsmap-select" aria-label="Agente" data-opsmap-autosubmit>
                            <option value="">Todos los agentes</option>
                            @foreach ($agents as $id => $name)
                                <option value="{{ $id }}" @selected($agentId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select name="action" class="form-select psc-opsmap-select" aria-label="Acción" data-opsmap-autosubmit>
                            <option value="">Todas las acciones</option>
                            @foreach ($actionOptions as $key => $label)
                                <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="psc-btn psc-btn--outline psc-opsmap-apply">Buscar</button>
                    </form>

                    <div class="psc-opsmap-list">
                        @forelse ($rows as $row)
                            <div class="psc-opsmap-arow {{ $row['muted'] ? 'is-muted' : '' }}">
                                <span class="psc-opsmap-aic"><i class="fas {{ $row['icon'] }}" aria-hidden="true"></i></span>
                                <span class="psc-opsmap-abody">
                                    <span class="psc-opsmap-atext">{!! $row['html'] !!}</span>
                                    <span class="psc-opsmap-ameta">
                                        {{ $row['agent'] }}
                                        @if ($row['conversation_label'])
                                            ·
                                            @if ($row['conversation_url'])
                                                <a href="{{ $row['conversation_url'] }}">{{ $row['conversation_label'] }}</a>
                                            @else
                                                {{ $row['conversation_label'] }}
                                            @endif
                                        @endif
                                        · <span title="{{ $row['at'] }}">{{ $row['when'] }}</span>
                                    </span>
                                    @if ($row['customer'])
                                        <span class="psc-opsmap-acust">
                                            Cliente:
                                            @if ($row['customer_url'])
                                                <a href="{{ $row['customer_url'] }}">{{ $row['customer'] }}</a>
                                            @else
                                                {{ $row['customer'] }}
                                            @endif
                                        </span>
                                    @endif
                                </span>
                            </div>
                        @empty
                            <div class="psc-state">
                                <i class="fas fa-clipboard-check"></i>
                                <div class="t">Sin acciones en este periodo</div>
                                <div class="s">Aquí aparecerá cada cambio que un agente haga en la tienda: estados, direcciones, carritos, vales…</div>
                            </div>
                        @endforelse
                    </div>

                    @if ($rows->hasPages())
                        <div class="psc-opsmap-pages">
                            {{ $rows->links() }}
                        </div>
                    @endif
                </div>

                <div class="card-footer psc-opsmap-foot">
                    <a href="{{ route('manager.helpdesk.ps.ext.opsmap.audit.export', array_filter(['days' => $days, 'agent' => $agentId, 'action' => $action, 'q' => $search])) }}"
                       class="psc-btn psc-btn--outline">Exportar a CSV</a>
                    <span class="psc-opsmap-caption">{{ $rows->total() }} {{ $rows->total() === 1 ? 'acción' : 'acciones' }} en los últimos {{ $days }} días</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Qué se registra</h6>
                </div>
                <div class="card-body psc-opsmap-help">
                    <p>Toda acción que cambia algo en PrestaShop desde el panel y que la tienda acepta: estado, seguimiento, dirección y correos del pedido, devoluciones, carrito, cupones, direcciones del cliente y vales.</p>
                    <p>De cada una se guarda el agente, el cliente, el pedido o carrito y la conversación desde la que se hizo.</p>
                    <p class="mb-0">No se copian al registro datos personales como direcciones, teléfonos o el texto de las notas: solo qué campos se enviaron.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
