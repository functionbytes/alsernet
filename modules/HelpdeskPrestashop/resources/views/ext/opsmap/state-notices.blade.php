@extends('layouts.theme')

@section('title', 'Avisos de cambio de estado')

@section('page_header')
    @include('core::components.card', ['title' => 'Avisos de cambio de estado', 'subtitle' => 'Qué ve el agente antes de cambiar el estado de un pedido desde el chat'])
@endsection

@include('helpdeskprestashop::ext.opsmap._assets')

@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/opsmap-notices.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/opsmap-notices.js')) }}" defer></script>
@endpush

@php
    $withEmail = collect($states)->where('send_email', true)->count();
    $withNotice = collect($config)->filter(fn ($c) => $c['agent_notice'] !== null)->count();
@endphp

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('manager.helpdesk.ps.ext.opsmap.state-notices.update') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Estado de la tienda · correo al cliente · aviso al agente</h5>
                        <small class="text-muted">El correo lo decide PrestaShop para cada estado; aquí eliges si «Notificar al cliente» sale marcado y qué aviso ve el agente antes de aplicar el cambio.</small>
                    </div>

                    <div class="card-body psc-opsmap-body">
                        @include('helpdeskprestashop::ext.opsmap._flash')

                        @unless ($ready)
                            <div class="psc-note psc-note--warn" role="alert">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span class="psc-note-txt">Falta crear la tabla de avisos: hay una migración pendiente de ejecutar. Hasta entonces el chat usa el comportamiento por defecto.</span>
                            </div>
                        @endunless

                        @unless ($live)
                            <div class="psc-note psc-note--warn" role="alert">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span class="psc-note-txt">PrestaShop no ha devuelto su lista de estados. Vuelve a cargar la página en unos minutos.</span>
                            </div>
                        @endunless

                        <div class="psc-opsmap-toolbar">
                            <label class="psc-opsmap-search">
                                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" id="psNoticesFilter" placeholder="Buscar estado de la tienda…" autocomplete="off" aria-label="Buscar estado de la tienda">
                            </label>
                            <div class="psc-chips" role="tablist" aria-label="Filtrar estados">
                                <button type="button" class="psc-chip is-on" data-notices-show="all">Todos {{ count($states) }}</button>
                                <button type="button" class="psc-chip" data-notices-show="email">Envían correo {{ $withEmail }}</button>
                                <button type="button" class="psc-chip" data-notices-show="notice">Con aviso {{ $withNotice }}</button>
                            </div>
                        </div>

                        <div class="psc-notices-list" id="psNoticesList">
                            @forelse ($states as $state)
                                @php
                                    $cfg = $config[$state['id']] ?? ['notify_default' => null, 'agent_notice' => null];
                                    $notify = old('states.'.$state['id'].'.notify', $cfg['notify_default'] === null ? 'default' : ($cfg['notify_default'] ? 'yes' : 'no'));
                                    $notice = old('states.'.$state['id'].'.notice', $cfg['agent_notice']);
                                @endphp
                                <div class="psc-notice-row" data-notices-row
                                     data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($state['name'])) }}"
                                     data-email="{{ $state['send_email'] ? '1' : '0' }}"
                                     data-notice="{{ $notice ? '1' : '0' }}">
                                    <input type="hidden" name="states[{{ $state['id'] }}][name]" value="{{ $state['name'] }}">
                                    <div class="psc-notice-head">
                                        <span class="psc-notice-name">{{ $state['name'] }} <span class="psc-opsmap-id">ID {{ $state['id'] }}</span></span>
                                        @if ($state['send_email'])
                                            <span class="psc-tag psc-tag--progress" title="Plantilla {{ $state['template'] }}">Correo · {{ $state['template_label'] }}</span>
                                        @else
                                            <span class="psc-tag psc-tag--closed">Sin correo</span>
                                        @endif
                                    </div>
                                    <div class="psc-notice-fields">
                                        <label class="psc-field">
                                            <span class="lbl">«Notificar al cliente» por defecto</span>
                                            <select name="states[{{ $state['id'] }}][notify]" @disabled(! $ready)>
                                                <option value="default" @selected($notify === 'default')>Según PrestaShop ({{ $state['send_email'] ? 'marcado' : 'desmarcado' }})</option>
                                                <option value="yes" @selected($notify === 'yes')>Marcado</option>
                                                <option value="no" @selected($notify === 'no')>Desmarcado</option>
                                            </select>
                                        </label>
                                        <label class="psc-field psc-notice-text">
                                            <span class="lbl">Aviso para el agente (opcional)</span>
                                            <input type="text" name="states[{{ $state['id'] }}][notice]" value="{{ $notice }}" maxlength="500"
                                                   placeholder="{{ $state['shipped'] ? 'Ej.: comprueba que el pedido tiene número de seguimiento' : 'Ej.: confirma con el cliente antes de cambiarlo' }}" @disabled(! $ready)>
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="psc-state">
                                    <i class="fas fa-store-slash"></i>
                                    <div class="t">Sin estados de pedido</div>
                                    <div class="s">PrestaShop no ha devuelto su lista de estados.</div>
                                </div>
                            @endforelse
                            <div class="psc-state psc-state--compact psc-opsmap-hidden" id="psNoticesNoMatch">
                                <div class="s">Ningún estado coincide con la búsqueda.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer psc-opsmap-foot">
                        <button type="submit" class="psc-btn psc-btn--primary" @disabled(! $ready || $states === [])>Guardar avisos</button>
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
                    <h6 class="mb-0 fw-bold">Qué ve el agente</h6>
                </div>
                <div class="card-body psc-opsmap-help">
                    <p>Al elegir un estado en el pedido, el chat le dice qué correo enviará PrestaShop al cliente (o que ese estado no envía ninguno) y le muestra el aviso que pongas aquí.</p>
                    <p class="mb-0">«Notificar al cliente» sale marcado o desmarcado según lo que elijas; el agente siempre puede cambiarlo antes de aplicar.</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">El correo</h6>
                </div>
                <div class="card-body psc-opsmap-help">
                    <p class="mb-0">Qué estados envían correo y con qué plantilla se configura en PrestaShop (Pedidos → Estados). Si lo cambias allí, esta pantalla lo refleja en menos de una hora.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
