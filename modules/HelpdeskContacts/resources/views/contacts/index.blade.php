@extends('layouts.theme')

@section('title', 'Contactos')

@push('css')
    {{-- Tokens --psc-* (paleta verde compartida con HelpdeskPrestashop) — contacts.css los consume para su semántica de color. --}}
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-chat.css') }}?v={{ filemtime(public_path('modules/helpdeskprestashop/css/prestashop-chat.css')) }}">
    <link rel="stylesheet" href="{{ asset('modules/contacts/css/contacts.css') }}?v={{ filemtime(public_path('modules/contacts/css/contacts.css')) }}">
    @if(helpdesk_integration_enabled())
        {{-- Framework visual .bv-modal del modal de búsqueda externa — mismo
             patrón ya usado fuera del inbox por helpdesk/customers/index.blade.php. --}}
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-identity.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-commerce.css') }}">
    @endif
@endpush

@section('page_header')
    @include('core::components.card', [
        'title' => 'Gestión de contactos',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Helpdesk', 'url' => ''],
            ['label' => 'Contactos', 'active' => true],
        ],
    ])
@endsection

@section('content')

    @include('core::components.alerts')
    @if(session('import_rejected_url'))
        <div class="psc-note psc-note--warn mb-3">
            <i class="fas fa-triangle-exclamation mt-1"></i>
            <span class="psc-note-txt">{{ session('import_rejected_count') }} {{ session('import_rejected_count') == 1 ? 'fila rechazada' : 'filas rechazadas' }} en la importación.</span>
            <a class="psc-note-act" href="{{ session('import_rejected_url') }}">Descargar filas rechazadas</a>
        </div>
    @endif

    @php
        // Orden del mockup: Todos · En riesgo · VIP · Posibles duplicados · Bloqueados.
        $views = [
            'all' => ['label' => 'Todos', 'count' => $stats['total']],
            'risk' => ['label' => 'En riesgo', 'count' => $stats['risk']],
            'vip' => ['label' => 'VIP', 'count' => $stats['vip']],
            'duplicates' => ['label' => 'Posibles duplicados', 'count' => $stats['duplicates']],
            'banned' => ['label' => 'Bloqueados', 'count' => $stats['banned']],
        ];

        // Etiquetas → variante visual del chip. tags.color guarda un token de
        // diseño (o null); solo se reconocen tonos verdes/ámbar/oscuros, el
        // resto queda en gris neutro (nunca rojo).
        $tagVariant = static fn (?string $color): string => match (true) {
            $color !== null && (str_contains($color, 'primary') || str_contains($color, 'green')) => 'is-green',
            $color !== null && (str_contains($color, 'warn') || str_contains($color, 'amber')) => 'is-warn',
            $color !== null && str_contains($color, 'dark') => 'is-dark',
            default => '',
        };

        // Permisos y módulos opcionales que condicionan las acciones por fila
        // (enlaces ?action= a la ficha: los modales solo abren si procede).
        $canUpdate = (bool) auth()->user()?->can('contacts.update');
        $canMerge = (bool) auth()->user()?->can('contacts.merge');
        $ticketsAvailable = class_exists('Modules\\HelpdeskTickets\\Services\\CatalogCacheService');

        // Origen del "Valor" (ContactAggregatorService::lifetimeOrders) → texto del tooltip.
        $valueSources = [
            'remarketing' => 'pedidos de la tienda',
            'prestashop' => 'PrestaShop',
            'erp' => 'ERP (total facturado)',
        ];

        // Estado vacío según la vista/filtro activo (en vez del genérico único).
        if (filled(request('q'))) {
            $emptyTitle = 'Ningún contacto coincide';
            $emptyHint = 'No se encontraron resultados para "'.request('q').'"';
        } elseif ($view === 'vip') {
            $emptyTitle = 'Ningún contacto VIP todavía';
            $emptyHint = 'Márcalos como VIP desde "Editar" en su ficha.';
        } elseif ($view === 'duplicates') {
            $emptyTitle = 'No hay posibles duplicados';
            $emptyHint = 'Ningún contacto comparte email ni teléfono con otro.';
        } else {
            $emptyTitle = 'Ningún contacto coincide';
            $emptyHint = 'Prueba a quitar filtros o busca en ERP y tienda';
        }
    @endphp

    <div class="ctl-shell">

        <div class="ctl-toolbar">
            <div class="ctl-breadcrumb">
                <i class="fas fa-address-book"></i><span>Helpdesk</span>
                <i class="fas fa-chevron-right"></i><span class="cur">Contactos</span>
            </div>

            <form method="GET" action="{{ route('contacts.index') }}" id="contactsFilterForm"
                  data-bulk-url="{{ route('contacts.bulk-action') }}" class="d-contents">
                <input type="hidden" name="view" id="ctl-view-input" value="{{ $view }}">

                <label class="ctl-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" name="q" placeholder="Nombre, email, teléfono, NIF o nº de cliente…" value="{{ request('q') }}">
                </label>
            </form>

            <div class="ctl-toolbar-actions">
                @can('contacts.create')
                    <button type="button" class="ctl-btn" data-bs-toggle="modal" data-bs-target="#contact-import-modal">Importar</button>
                @endcan
                @can('contacts.export')<button type="button" class="ctl-btn" data-bs-toggle="modal" data-bs-target="#contact-export-modal">Exportar</button>@endcan
                <button type="button" class="ctl-btn" data-bs-toggle="modal" data-bs-target="#contact-reports-modal">Informes</button>
                @can('contacts.update')
                    @if(helpdesk_integration_enabled())
                        <button type="button" id="external-search-trigger" class="ctl-btn ctl-btn-primary">Buscar en ERP o tienda</button>
                    @endif
                @endcan
            </div>
        </div>

        <div class="ctl-views">
            <span class="ctl-views-label">Vistas</span>
            @foreach($views as $key => $v)
                <a href="{{ route('contacts.index', array_merge(request()->except(['view', 'page']), ['view' => $key])) }}"
                   class="ctl-view-chip {{ $view === $key ? 'is-active' : '' }}">
                    {{ $v['label'] }} <span class="n">{{ number_format($v['count']) }}</span>
                </a>
            @endforeach

            <span class="ctl-view-filters">
                <select id="ctl-channel-filter" class="ctl-filter-select" form="contactsFilterForm" name="channel">
                    <option value="">Canal: todos</option>
                    <option value="email" @selected(request('channel') === 'email')>Email</option>
                    <option value="whatsapp" @selected(request('channel') === 'whatsapp')>WhatsApp</option>
                    <option value="facebook" @selected(request('channel') === 'facebook')>Facebook</option>
                    <option value="instagram" @selected(request('channel') === 'instagram')>Instagram</option>
                </select>
                <select id="ctl-origin-filter" class="ctl-filter-select" form="contactsFilterForm" name="origin"
                        aria-label="Filtrar por origen">
                    <option value="">Origen: todos</option>
                    @foreach($origins as $originValue => $originLabel)
                        <option value="{{ $originValue }}" @selected(request('origin') === $originValue)>{{ $originLabel }}</option>
                    @endforeach
                </select>
                <select id="ctl-tag-filter" class="ctl-filter-select" form="contactsFilterForm" name="tag"
                        @disabled($allTags->isEmpty()) aria-label="Filtrar por etiqueta">
                    <option value="">Etiquetas: todas</option>
                    @foreach($allTags as $tag)
                        <option value="{{ $tag->slug }}" @selected(request('tag') === $tag->slug)>{{ $tag->name }}</option>
                    @endforeach
                </select>
                <select id="ctl-owner-filter" class="ctl-filter-select" form="contactsFilterForm" name="owner"
                        aria-label="Filtrar por responsable">
                    <option value="">Responsable: todos</option>
                    <option value="none" @selected(request('owner') === 'none')>Sin responsable</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner['id'] }}" @selected((string) request('owner') === (string) $owner['id'])>{{ $owner['name'] }}</option>
                    @endforeach
                </select>
            </span>
        </div>

        <div id="bulk-toolbar" class="ctl-bulkbar d-none">
            <span class="ctl-bulkbar-count"><span data-bulk-count>0</span> seleccionados</span>
            <span class="ctl-bulkbar-actions">
                @if($canUpdate)
                    {{-- data-bulk-action="send-hsm": send-hsm-modal.js abre su flujo masivo por delegación. --}}
                    <button type="button" data-bulk-action="send-hsm">Enviar plantilla</button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#bulk-modal">Etiquetar o asignar</button>
                @endif
                @can('contacts.export')<button type="button" data-bs-toggle="modal" data-bs-target="#contact-export-modal" data-export-selection>Exportar selección</button>@endcan
                <button type="button" class="ctl-bulk-clear" id="bulk-clear-btn">Quitar</button>
            </span>
        </div>

        {{-- overflow-x:auto propio: las columnas (checkbox/contacto/canales/salud/
             valor/última vez/acciones) se comprimían hasta solaparse en pantallas
             estrechas — .ctl-grid fija un ancho mínimo legible y este wrapper
             deja hacer scroll horizontal en vez de romper el layout. --}}
        <div class="ctl-table-scroll">
        <div class="ctl-grid ctl-head">
            <span><input type="checkbox" id="check-all"></span>
            <span>Contacto</span><span>Contacto y canales</span><span>Salud</span><span>Valor</span><span>Última vez</span><span></span>
        </div>

        <div>
            @forelse($customers as $customer)
                @php
                    $health = $healthScores[$customer->id] ?? null;
                    $hasHealth = ($customer->conversations_count ?? 0) > 0;
                    $value = $values[$customer->id] ?? ['totalSpent' => 0.0, 'source' => null, 'partial' => false];
                    $healthTier = $health === null ? null : ($health >= 70 ? 'high' : ($health >= 40 ? 'mid' : 'low'));
                @endphp
                <div class="ctl-row ctl-grid {{ $customer->banned_at ? 'is-banned' : '' }}" data-ctl-href="{{ route('contacts.show', $customer) }}">
                    <span><input type="checkbox" class="form-check-input contact-check" value="{{ $customer->id }}"></span>

                    <span class="ctl-contact">
                        <span class="ctl-avatar">{{ $customer->initials }}</span>
                        <span class="ctl-contact-body">
                            <span class="ctl-contact-name-row">
                                <span class="ctl-contact-name">{{ $customer->name ?: 'Sin nombre' }}</span>
                                @if($customer->is_vip)
                                    <span class="ctl-status-tag is-vip">VIP</span>
                                @endif
                                @if($view === 'duplicates')
                                    <span class="ctl-status-tag">Duplicado</span>
                                @endif
                                @if($customer->banned_at)
                                    <span class="ctl-status-tag">Bloqueado</span>
                                @endif
                            </span>
                            <span class="ctl-contact-sub">
                                @if($customer->banned_at)
                                    {{ $customer->ban_reason ?: 'Sin motivo registrado' }} · {{ $customer->banned_at->translatedFormat('d M') }}
                                @elseif($view === 'duplicates')
                                    {{ $duplicateReasons[$customer->id] ?? 'Posible duplicado' }}
                                @else
                                    {{ $customer->company?->name ?: 'Sin empresa' }}
                                @endif
                            </span>
                        </span>
                    </span>

                    <span class="ctl-channels">
                        <span class="ctl-channels-email">{{ $customer->email ?: '—' }}</span>
                        <span class="ctl-channels-chips">
                            @if($customer->email)<span class="ctl-channel-chip">Email</span>@endif
                            @if($customer->whatsapp_phone)<span class="ctl-channel-chip is-active">WhatsApp</span>@endif
                            @if($customer->facebook_psid)<span class="ctl-channel-chip">Facebook</span>@endif
                            @if($customer->instagram_id)<span class="ctl-channel-chip">Instagram</span>@endif
                            @if(! $customer->email && ! $customer->whatsapp_phone && ! $customer->facebook_psid && ! $customer->instagram_id)
                                <span class="ctl-channel-chip">Sin canal</span>
                            @endif
                        </span>
                        @if($customer->tags->isNotEmpty())
                            <span class="ctl-tags">
                                @foreach($customer->tags->take(3) as $tag)
                                    <span class="ctl-tag-chip {{ $tagVariant($tag->color) }}" title="{{ $tag->name }}">{{ $tag->name }}</span>
                                @endforeach
                                @if($customer->tags->count() > 3)
                                    <span class="ctl-tag-chip is-more" title="{{ $customer->tags->skip(3)->pluck('name')->implode(', ') }}">+{{ $customer->tags->count() - 3 }}</span>
                                @endif
                            </span>
                        @endif
                    </span>

                    <span>
                        @if($hasHealth)
                            <span class="ctl-health">
                                <span class="ctl-health-bar"><span class="ctl-health-fill {{ $healthTier === 'mid' ? 'is-mid' : ($healthTier === 'low' ? 'is-low' : '') }}" style="width: {{ $health }}%"></span></span>
                                <span class="ctl-health-num {{ $healthTier === 'mid' ? 'is-mid' : ($healthTier === 'low' ? 'is-low' : '') }}">{{ $health }}</span>
                            </span>
                        @else
                            <span class="ctl-health-empty">—</span>
                        @endif
                    </span>

                    {{-- Sin dato cacheado (source null) no se inventa un total: "—". --}}
                    @if($value['source'] !== null)
                        <span class="ctl-value" title="Según {{ $valueSources[$value['source']] ?? $value['source'] }}{{ $value['partial'] ? ' · parcial: puede haber más pedidos' : '' }}">{{ number_format($value['totalSpent'], 0, ',', '.') }} €</span>
                    @else
                        <span class="ctl-value ctl-health-empty" title="Sin datos de compras en caché todavía">—</span>
                    @endif

                    <span class="ctl-lastseen">{{ $customer->last_seen_at?->diffForHumans() ?? '—' }}</span>

                    <span class="ctl-row-actions">
                        {{-- Acción principal de las filas especiales (mockup): duplicado → Fusionar
                             con el contacto que coincide (se conserva el más antiguo); bloqueado → Desbloquear. --}}
                        @php
                            $dupMatch = $view === 'duplicates' && $canMerge ? ($duplicateMatches[$customer->id] ?? null) : null;
                            // Se conserva siempre el más antiguo (menor id) y se absorbe el otro.
                            $mergeKeep = $dupMatch ? min($customer->id, $dupMatch['partnerId']) : null;
                            $mergeLoser = $dupMatch ? max($customer->id, $dupMatch['partnerId']) : null;
                            $rowTextAction = $dupMatch || ($customer->banned_at && $canUpdate);
                        @endphp
                        @if($dupMatch)
                            <a href="{{ route('contacts.show', ['customer' => $mergeKeep, 'action' => 'merge', 'loser' => $mergeLoser]) }}"
                               class="ctl-row-text-btn">Fusionar</a>
                        @elseif($customer->banned_at && $canUpdate)
                            <a href="{{ route('contacts.show', ['customer' => $customer, 'action' => 'unban']) }}" class="ctl-row-text-btn">Desbloquear</a>
                        @endif
                        @if($customer->whatsapp_phone && ! $rowTextAction)
                            <button type="button" class="ctl-row-action send-hsm-trigger" title="Enviar plantilla"
                                    data-customer-id="{{ $customer->id }}"
                                    data-customer-phone="{{ $customer->whatsapp_phone }}"
                                    data-customer-name="{{ $customer->name ?: 'este contacto' }}">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        @endif
                        @unless($rowTextAction)
                            <a href="{{ $canUpdate ? route('contacts.show', ['customer' => $customer, 'action' => 'edit']) : route('contacts.show', $customer) }}"
                               class="ctl-row-action" title="{{ $canUpdate ? 'Editar' : 'Ver ficha 360' }}">
                                <i class="fas fa-pen"></i>
                            </a>
                        @endunless
                        <div class="dropdown d-inline-block">
                            <button type="button" class="ctl-row-action" data-bs-toggle="dropdown" aria-expanded="false" title="Más">
                                <i class="fas fa-ellipsis"></i>
                            </button>
                            {{-- Menú de acciones del contacto (mockup pieza 15): mismo orden que la
                                 cabecera de la ficha; lo destructivo al final sobre fondo gris. --}}
                            <ul class="dropdown-menu dropdown-menu-end ct-menu">
                                <li><a class="dropdown-item" href="{{ route('contacts.show', $customer) }}">Ver ficha 360</a></li>
                                @if($canUpdate)
                                    <li><a class="dropdown-item" href="{{ route('contacts.show', ['customer' => $customer, 'action' => 'edit']) }}">Editar contacto</a></li>
                                    @if($ticketsAvailable)
                                        <li><a class="dropdown-item" href="{{ route('contacts.show', ['customer' => $customer, 'action' => 'ticket']) }}">Crear ticket</a></li>
                                    @endif
                                @endif
                                @if($canMerge)
                                    <li><a class="dropdown-item" href="{{ route('contacts.show', ['customer' => $customer, 'action' => 'merge']) }}">Fusionar duplicado</a></li>
                                @endif
                                @if($canUpdate)
                                    <li><a class="dropdown-item" href="{{ route('contacts.show', ['customer' => $customer, 'action' => 'sync']) }}">Sincronizar fuentes</a></li>
                                    <li class="ct-menu-danger">
                                        <a class="dropdown-item" href="{{ route('contacts.show', ['customer' => $customer, 'action' => $customer->banned_at ? 'unban' : 'ban']) }}">
                                            {{ $customer->banned_at ? 'Desbloquear contacto' : 'Bloquear contacto' }}
                                        </a>
                                    </li>
                                    <li class="ct-menu-danger">
                                        <button type="button" class="dropdown-item delete-btn"
                                                data-url="{{ route('contacts.destroy', $customer) }}"
                                                data-title="¿Eliminar contacto {{ $customer->name ?: 'sin nombre' }}?">
                                            Eliminar contacto
                                        </button>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </span>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fas fa-address-book fa-2x text-muted mb-3 d-block"></i>
                    <p class="fw-semibold mb-1">{{ $emptyTitle }}</p>
                    <p class="small text-muted mb-0">{{ $emptyHint }}</p>
                </div>
            @endforelse
        </div>
        </div>

        <div class="ctl-foot">
            <span class="ctl-foot-count">{{ $customers->count() }} de {{ number_format($customers->total()) }} contactos</span>
            @if($customers->hasPages() || $customers->total() > 15)
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0">Mostrar:</label>
                    <select class="form-select form-select-sm w-auto" id="per-page-select">
                        @foreach([15, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ms-auto">{{ $customers->appends(request()->input())->links() }}</div>
            @endif
        </div>

    </div>

    @include('contacts::contacts.partials._send-hsm-modal')
    @if(helpdesk_integration_enabled())
        @include('contacts::contacts.partials._external-search-modal')
    @endif
    @include('contacts::contacts.partials._import-modal')
    @can('contacts.export')@include('contacts::contacts.partials._export-modal')@endcan
    @include('contacts::contacts.partials._reports-modal')
    @include('core::components.delete')

    <div class="modal fade ct-mdl" id="bulk-modal" tabindex="-1" aria-labelledby="bulkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="ct-modal-icon me-2"><i class="fas fa-list-check"></i></span>
                    <div class="flex-grow-1">
                        <div class="ct-modal-eyebrow">Contactos · <span data-bulk-count>0</span> seleccionados</div>
                        <h5 class="modal-title" id="bulkModalLabel">Acciones masivas</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column gap-2 mb-3">
                        <label class="ct-radio-opt"><input type="radio" name="bulk-action" value="tag" checked><span>Añadir etiqueta</span></label>
                        <label class="ct-radio-opt"><input type="radio" name="bulk-action" value="assign"><span>Asignar agente responsable</span></label>
                        <label class="ct-radio-opt"><input type="radio" name="bulk-action" value="unban"><span>Desbloquear contactos</span></label>
                        <label class="ct-radio-opt"><input type="radio" name="bulk-action" value="ban"><span>Bloquear contactos</span></label>
                        <label class="ct-radio-opt is-danger"><input type="radio" name="bulk-action" value="delete"><span>Eliminar contactos</span></label>
                    </div>

                    <div class="mb-3" data-bulk-field="tag">
                        <label class="ct-flabel" for="bulk-tag-input">Etiqueta</label>
                        <input type="text" id="bulk-tag-input" class="ct-finput" list="bulk-tag-options"
                               maxlength="60" autocomplete="off" placeholder="Escribe o elige una etiqueta">
                        <datalist id="bulk-tag-options">
                            @foreach($allTags as $tag)
                                <option value="{{ $tag->name }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="mb-3 d-none" data-bulk-field="assign">
                        <label class="ct-flabel" for="bulk-owner-select">Responsable</label>
                        <select id="bulk-owner-select" class="ct-fselect">
                            <option value="">Sin responsable (quitar el actual)</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner['id'] }}">{{ $owner['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" data-bulk-field="confirm">
                        <label class="ct-flabel" for="bulk-confirm-input">Escribe <strong data-bulk-count>0</strong> para confirmar</label>
                        <input type="text" id="bulk-confirm-input" class="ct-finput" inputmode="numeric" autocomplete="off">
                    </div>

                    <div class="ct-note-box">
                        <i class="fas fa-circle-info mt-1"></i>
                        <span>Las acciones destructivas piden escribir el número de contactos afectados antes de continuar.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="psc-btn psc-btn--primary">Aplicar</button>
                    <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal" id="bulk-cancel-btn">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('modules/contacts/js/contacts-index.js') }}?v={{ filemtime(public_path('modules/contacts/js/contacts-index.js')) }}"></script>
<script src="{{ asset('modules/contacts/js/send-hsm-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/send-hsm-modal.js')) }}"></script>
<script src="{{ asset('modules/contacts/js/export-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/export-modal.js')) }}"></script>
@if(helpdesk_integration_enabled())
<script src="{{ asset('modules/contacts/js/external-search-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/external-search-modal.js')) }}"></script>
@endif
@endpush
