@extends('layouts.theme')

@section('title', 'Mapeo de estados de PrestaShop')

@section('page_header')
    @include('core::components.card', ['title' => 'Mapeo de estados', 'subtitle' => 'Qué pasa en el helpdesk cuando un pedido cambia de estado en la tienda'])
@endsection

@include('helpdeskprestashop::ext.opsmap._assets')

@php
    $mappedCount = count($map);
    $totalCount = count($states);
@endphp

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('manager.helpdesk.ps.ext.opsmap.state-map.update') }}" method="POST" id="psOpsmapForm">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Estado del pedido en la tienda → estado de la conversación</h5>
                        <small class="text-muted">Elige, para cada estado de PrestaShop, a qué estado pasa la conversación del cliente. Lo que quede en «Sin acción» no cambia nada.</small>
                    </div>

                    <div class="card-body psc-opsmap-body">
                        @include('helpdeskprestashop::ext.opsmap._flash')

                        @unless ($ready)
                            <div class="psc-note psc-note--warn" role="alert">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span class="psc-note-txt">Falta crear la tabla del mapeo de estados: hay una migración pendiente de ejecutar. Hasta entonces no se guarda ni se aplica ningún mapeo.</span>
                            </div>
                        @endunless

                        @unless ($live)
                            <div class="psc-note psc-note--warn" role="alert">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span class="psc-note-txt">PrestaShop no ha devuelto su lista de estados. Se muestran solo los estados ya mapeados; vuelve a cargar la página para ver el resto.</span>
                            </div>
                        @endunless

                        <div class="psc-opsmap-toolbar">
                            <label class="psc-opsmap-search">
                                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" id="psOpsmapFilter" placeholder="Buscar estado de la tienda…" autocomplete="off" aria-label="Buscar estado de la tienda">
                            </label>
                            <div class="psc-chips" role="tablist" aria-label="Filtrar estados">
                                <button type="button" class="psc-chip is-on" data-opsmap-show="all">Todos <span data-opsmap-count="all">{{ $totalCount }}</span></button>
                                <button type="button" class="psc-chip" data-opsmap-show="mapped">Con acción <span data-opsmap-count="mapped">{{ $mappedCount }}</span></button>
                                <button type="button" class="psc-chip" data-opsmap-show="none">Sin acción <span data-opsmap-count="none">{{ $totalCount - $mappedCount }}</span></button>
                            </div>
                        </div>

                        <div class="psc-opsmap-list" id="psOpsmapList">
                            @forelse ($states as $state)
                                @php $current = old('map.'.$state['id'], $map[$state['id']] ?? 'none'); @endphp
                                <div class="psc-maprow psc-opsmap-row {{ $current === 'none' ? 'is-muted' : '' }}"
                                     data-opsmap-row data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($state['name'])) }}">
                                    <span class="from">
                                        {{ $state['name'] }}
                                        <span class="psc-opsmap-id">ID {{ $state['id'] }}</span>
                                    </span>
                                    <i class="fas fa-arrow-right psc-opsmap-arrow" aria-hidden="true"></i>
                                    <span class="to">
                                        <input type="hidden" name="names[{{ $state['id'] }}]" value="{{ $state['name'] }}">
                                        <select name="map[{{ $state['id'] }}]" aria-label="Acción para {{ $state['name'] }}" data-opsmap-select @disabled(! $ready)>
                                            @foreach ($mapActions as $key => $label)
                                                <option value="{{ $key }}" @selected($current === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </span>
                                </div>
                            @empty
                                <div class="psc-state">
                                    <i class="fas fa-store-slash"></i>
                                    <div class="t">Sin estados de pedido</div>
                                    <div class="s">PrestaShop no responde y todavía no hay ningún estado mapeado.</div>
                                </div>
                            @endforelse
                            <div class="psc-state psc-state--compact psc-opsmap-hidden" id="psOpsmapNoMatch">
                                <div class="s">Ningún estado coincide con la búsqueda.</div>
                            </div>
                        </div>

                        <input type="hidden" name="create_note" value="0">
                        <label class="psc-check psc-opsmap-note">
                            <input type="checkbox" name="create_note" value="1" @checked(old('create_note', $createNote)) @disabled(! $ready)>
                            Crear nota en el ticket al cambiar de estado en la tienda
                        </label>
                    </div>

                    <div class="card-footer psc-opsmap-foot">
                        <button type="submit" class="psc-btn psc-btn--primary" @disabled(! $ready || $states === [])>Guardar mapeo</button>
                        @if ($updatedAt)
                            <span class="psc-opsmap-caption">Último cambio: {{ \Illuminate\Support\Carbon::parse($updatedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}{{ $updatedBy ? ' · '.$updatedBy : '' }}</span>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Cómo se aplica</h6>
                </div>
                <div class="card-body psc-opsmap-help">
                    <p>Cuando la tienda avisa de que un pedido cambió de estado, se busca al cliente vinculado a PrestaShop y su conversación más reciente con actividad en los últimos {{ $windowDays }} días.</p>
                    <p>Esa conversación pasa al estado elegido. También los tickets del cliente ligados a esa conversación o cuyo formulario traía ese número de pedido.</p>
                    <p class="mb-0">Si la conversación ya está en ese estado no se toca. «Abierto» no mueve una conversación que ya esté abierta: solo la reabre o la saca de «Pendiente».</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">La nota</h6>
                </div>
                <div class="card-body psc-opsmap-help">
                    <p>Con la nota activada, cada cambio de estado de un pedido deja una nota interna en esa conversación y en sus tickets, aunque el estado no tenga acción: «PrestaShop: el pedido #829575 ha pasado a Enviado».</p>
                    <p class="mb-0">El cliente nunca ve estas notas.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
