@extends('layouts.theme')

@section('title', $customer->name ?: 'Contacto')

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('modules/contacts/css/contacts.css') }}?v={{ filemtime(public_path('modules/contacts/css/contacts.css')) }}">
    @if(helpdesk_integration_enabled())
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-identity.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-commerce.css') }}">
    @endif
@endpush

@section('page_header')
    @include('core::components.card', [
        'title' => $customer->name ?: 'Contacto',
        'breadcrumbs' => [
            ['label' => 'Contactos', 'url' => route('contacts.index')],
            ['label' => $customer->name ?: 'Contacto'],
        ],
    ])
@endsection

@section('content')

    @include('core::components.alerts')

    @php
        $avatarUrl = method_exists($customer, 'getAvatarUrl') ? $customer->getAvatarUrl() : ($customer->avatar_url ?? null);
        $initials = strtoupper(mb_substr($customer->name ?? '?', 0, 1));
        // Contactos creados desde un canal social (WhatsApp/Facebook/Instagram) solo
        // tienen su identificador de plataforma relleno — `phone` queda null. Usamos
        // el de WhatsApp como teléfono de contacto cuando no hay uno genérico.
        $customerPhone = $customer->phone ?: $customer->whatsapp_phone;
        $showActionsMenu = $customer->whatsapp_phone
            || auth()->user()?->can('contacts.update')
            || auth()->user()?->can('contacts.merge');
    @endphp

    <div id="contact360"
         data-customer-id="{{ $customer->id }}"
         data-base-url="{{ url('panel/helpdesk/contacts/'.$customer->id) }}"
         data-cart-base-url="{{ url('panel/helpdesk/contacts/'.$customer->id.'/cart') }}"
         data-update-url="{{ route('contacts.update', $customer) }}"
         data-merge-search-url="{{ route('contacts.merge.search', $customer) }}"
         data-merge-preview-url="{{ route('contacts.merge.preview', $customer) }}"
         data-merge-execute-url="{{ route('contacts.merge.execute', $customer) }}"
         data-ban-url="{{ url('panel/helpdesk/contacts/'.$customer->id.'/ban') }}"
         data-unban-url="{{ url('panel/helpdesk/contacts/'.$customer->id.'/unban') }}">

        {{-- Hero header --}}
        <div class="card mb-4">
            <div class="d-flex align-items-start gap-3 flex-wrap p-3 p-md-4">

                @if($avatarUrl && ! str_contains($avatarUrl, 'ui-avatars.com'))
                    <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}"
                         width="56" height="56" loading="lazy"
                         class="ct-hero-avatar rounded-circle object-fit-cover">
                @else
                    <span class="ct-hero-avatar d-inline-flex align-items-center justify-content-center">{{ $initials }}</span>
                @endif

                <div class="ct-hero-info">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <span class="ct-name contact-hero-name">{{ $customer->name ?: 'Sin nombre' }}</span>
                        @if($customer->is_verified ?? ($customer->email_verified_at ?? false))
                            <span class="ct-chip ct-chip-verified"><i class="fas fa-circle-check"></i> Verificado</span>
                        @endif
                        @if($customer->is_banned ?? ($customer->banned_at ?? false))
                            <span class="ct-chip ct-chip-danger"><i class="fas fa-ban"></i> Suspendido</span>
                        @endif
                        {{-- Sentiment badge: filled by JS from resumen data.sentiment --}}
                        <span id="contact-sentiment"></span>
                        <span class="ct-chip ct-chip-id ct-mono">CT-{{ $customer->id }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 ct-meta mb-2">
                        @if($customer->email)
                            <span><i class="fas fa-envelope"></i> <span class="ct-mono">{{ $customer->email }}</span></span>
                        @endif
                        @if($customerPhone)
                            <span><i class="fas fa-phone"></i> <span class="ct-mono">{{ $customerPhone }}</span></span>
                        @endif
                    </div>
                    {{-- Integration pills: filled by JS from resumen data.integrations --}}
                    <div id="contact-integrations" class="d-flex flex-wrap gap-2"></div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <a href="{{ route('manager.helpdesk.conversations.create', ['customer' => $customer->id]) }}"
                       class="btn ct-btn-dark">
                        <i class="fas fa-comment me-1"></i> Nueva conversación
                    </a>
                    @can('contacts.update')
                        <button type="button" id="contact-edit-btn" class="btn ct-btn-outline">
                            <i class="fas fa-pen me-1"></i> Editar
                        </button>
                    @endcan
                    @if($showActionsMenu)
                        <div class="dropdown">
                            <button type="button" class="btn ct-btn-outline" data-bs-toggle="dropdown"
                                    aria-expanded="false" title="Más acciones">
                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('contacts.update')
                                    <li>
                                        <button type="button" id="contact-sync-btn" class="dropdown-item d-flex align-items-center">
                                            <span>Sincronizar</span>
                                            <span class="ms-auto ct-mono small text-muted">ERP·PS</span>
                                        </button>
                                    </li>
                                @endcan
                                @if($customer->whatsapp_phone)
                                    <li>
                                        <a href="#" class="dropdown-item send-hsm-trigger"
                                           data-customer-id="{{ $customer->id }}"
                                           data-customer-name="{{ $customer->name ?: 'este contacto' }}">
                                            Plantilla WhatsApp
                                        </a>
                                    </li>
                                @endif
                                @can('contacts.merge')
                                    <li>
                                        <button type="button" id="contact-merge-btn" class="dropdown-item">
                                            Fusionar contacto
                                        </button>
                                    </li>
                                @endcan
                                @can('contacts.update')
                                    @if(helpdesk_integration_enabled())
                                        <li>
                                            <button type="button" class="dropdown-item external-link-trigger"
                                                    data-customer-id="{{ $customer->id }}">
                                                Vincular plataforma
                                            </button>
                                        </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        @if($customer->banned_at)
                                            <button type="button" id="contact-unban-btn" class="dropdown-item">Reactivar contacto</button>
                                        @else
                                            <button type="button" id="contact-ban-btn" class="dropdown-item">Suspender contacto</button>
                                        @endif
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    @endif
                </div>

            </div>

            {{-- KPI row: filled by JS (fillResumenHero) from resumen data.stats --}}
            <div id="contact-hero-stats" class="ct-kpi-row">
                @for ($i = 0; $i < 4; $i++)
                    <div class="ct-kpi placeholder-glow"><span class="placeholder col-8"></span></div>
                @endfor
            </div>
        </div>

        {{-- Tabs --}}
        <ul class="nav nav-tabs contacts-tab-nav mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" type="button" role="tab"
                        data-contact-tab="resumen"
                        data-bs-toggle="tab" data-bs-target="#pane-resumen"
                        aria-controls="pane-resumen" aria-selected="true">
                    <i class="fas fa-address-card me-1"></i> Resumen
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab"
                        data-contact-tab="conversaciones"
                        data-bs-toggle="tab" data-bs-target="#pane-conversaciones"
                        aria-controls="pane-conversaciones" aria-selected="false">
                    <i class="fas fa-comments me-1"></i> Conversaciones y chats
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab"
                        data-contact-tab="erp"
                        data-bs-toggle="tab" data-bs-target="#pane-erp"
                        aria-controls="pane-erp" aria-selected="false">
                    <i class="fas fa-database me-1"></i> Pedidos ERP
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab"
                        data-contact-tab="prestashop"
                        data-bs-toggle="tab" data-bs-target="#pane-prestashop"
                        aria-controls="pane-prestashop" aria-selected="false">
                    <i class="fab fa-shopify me-1"></i> Pedidos PrestaShop
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab"
                        data-contact-tab="tienda"
                        data-bs-toggle="tab" data-bs-target="#pane-tienda"
                        aria-controls="pane-tienda" aria-selected="false">
                    <i class="fas fa-bag-shopping me-1"></i> Pedidos tienda
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab"
                        data-contact-tab="actividad"
                        data-bs-toggle="tab" data-bs-target="#pane-actividad"
                        aria-controls="pane-actividad" aria-selected="false">
                    <i class="fas fa-timeline me-1"></i> Actividad y señales
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab"
                        data-contact-tab="tickets"
                        data-bs-toggle="tab" data-bs-target="#pane-tickets"
                        aria-controls="pane-tickets" aria-selected="false">
                    <i class="fas fa-ticket me-1"></i> Tickets
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab"
                        data-contact-tab="carrito"
                        data-bs-toggle="tab" data-bs-target="#pane-carrito"
                        aria-controls="pane-carrito" aria-selected="false">
                    <i class="fas fa-cart-shopping me-1"></i> Carrito
                </button>
            </li>
        </ul>

        {{-- Panes --}}
        <div class="tab-content">

            {{-- Resumen (active by default) --}}
            <div class="tab-pane fade show active" id="pane-resumen" role="tabpanel"
                 aria-labelledby="resumen" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 4])
            </div>

            {{-- Conversaciones y chats --}}
            <div class="tab-pane fade" id="pane-conversaciones" role="tabpanel"
                 aria-labelledby="conversaciones" data-loaded="0">
                <div id="conversaciones-section">
                    @include('contacts::contacts.partials._skeleton', ['rows' => 3])
                </div>
                <hr class="my-4">
                <h6 class="fw-bold mb-3">Chats</h6>
                <div id="chats-section">
                    @include('contacts::contacts.partials._skeleton', ['rows' => 2])
                </div>
            </div>

            {{-- Pedidos ERP --}}
            <div class="tab-pane fade" id="pane-erp" role="tabpanel"
                 aria-labelledby="erp" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 4])
            </div>

            {{-- Pedidos PrestaShop --}}
            <div class="tab-pane fade" id="pane-prestashop" role="tabpanel"
                 aria-labelledby="prestashop" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 4])
            </div>

            {{-- Pedidos tienda --}}
            <div class="tab-pane fade" id="pane-tienda" role="tabpanel"
                 aria-labelledby="tienda" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 4])
            </div>

            {{-- Actividad y señales --}}
            <div class="tab-pane fade" id="pane-actividad" role="tabpanel"
                 aria-labelledby="actividad" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 5])
            </div>

            {{-- Tickets --}}
            <div class="tab-pane fade" id="pane-tickets" role="tabpanel"
                 aria-labelledby="tickets" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 4])
            </div>

            {{-- Carrito --}}
            <div class="tab-pane fade" id="pane-carrito" role="tabpanel"
                 aria-labelledby="carrito" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 3])
            </div>

        </div>

    </div>

@endsection

{{-- Modal: editar contacto --}}
<div class="modal fade" id="contact-edit-modal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-pen"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Ficha</div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="editModalLabel">
                        Editar contacto <span class="ct-chip ct-chip-id ct-mono">CT-{{ $customer->id }}</span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="contact-edit-form">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit-name">Nombre</label>
                            <input type="text" class="form-control" id="edit-name" name="name"
                                   value="{{ $customer->name }}" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="edit-email">Email</label>
                            <input type="email" class="form-control ct-mono" id="edit-email" name="email"
                                   value="{{ $customer->email }}">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="edit-phone">Teléfono</label>
                            <input type="text" class="form-control ct-mono" id="edit-phone" name="phone"
                                   value="{{ $customerPhone }}">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="edit-language">Idioma</label>
                            <input type="text" class="form-control" id="edit-language" name="language"
                                   value="{{ $customer->language }}" maxlength="10"
                                   placeholder="es, en, fr...">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="edit-timezone">Zona horaria</label>
                            <input type="text" class="form-control" id="edit-timezone" name="timezone"
                                   value="{{ $customer->timezone }}"
                                   placeholder="America/Madrid">
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex" for="edit-notes">
                                Notas internas
                                <span class="ms-auto small text-muted fw-normal">solo visible para agentes</span>
                            </label>
                            <textarea class="form-control" id="edit-notes" name="internal_notes"
                                      rows="3">{{ $customer->internal_notes }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn ct-btn-dark w-100 mb-2">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: fusionar contactos --}}
<div class="modal fade" id="contact-merge-modal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-code-merge"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Duplicados</div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="mergeModalLabel">
                        Fusionar contacto <span class="ct-chip ct-chip-id ct-mono">CT-{{ $customer->id }}</span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="ct-modal-warning mb-3">
                    <i class="fas fa-triangle-exclamation mt-1"></i>
                    <span>
                        Se conserva <strong>{{ $customer->name ?: 'este contacto' }}</strong>
                        (<span class="ct-mono">CT-{{ $customer->id }}</span>).
                        El contacto seleccionado se elimina y sus conversaciones, pedidos y tickets se mueven a esta ficha.
                        No se puede deshacer.
                    </span>
                </div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="merge-search-input"
                           placeholder="Buscar contacto duplicado por nombre, email o teléfono...">
                    <span class="input-group-text"><i class="fas fa-magnifying-glass"></i></span>
                </div>
                <div id="merge-search-results"></div>
                <div id="merge-preview" class="d-none mt-3">
                    <hr>
                    <h6 class="fw-semibold mb-3">Vista previa de la fusión</h6>
                    <div id="merge-preview-content"></div>
                </div>
            </div>
            <div class="modal-footer flex-column d-none" id="merge-footer">
                <button type="button" class="btn btn-brand w-100 mb-2" id="merge-execute-btn">
                    <i class="fas fa-code-merge me-1"></i> Fusionar ahora
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@include('contacts::contacts.partials._send-hsm-modal')
@if(helpdesk_integration_enabled())
    @include('contacts::contacts.partials._external-search-modal')
@endif

@push('scripts')
    <script src="{{ asset('modules/contacts/js/contacts-360.js') }}?v={{ filemtime(public_path('modules/contacts/js/contacts-360.js')) }}"></script>
    <script src="{{ asset('modules/contacts/js/send-hsm-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/send-hsm-modal.js')) }}"></script>
    @if(helpdesk_integration_enabled())
    <script src="{{ asset('modules/contacts/js/external-search-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/external-search-modal.js')) }}"></script>
    @endif
@endpush
