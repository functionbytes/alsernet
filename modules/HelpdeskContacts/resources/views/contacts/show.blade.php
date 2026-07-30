@extends('layouts.theme')

@section('title', $customer->name ?: 'Contacto')

@push('css')
    <style>
        .contacts-hero-avatar { width: 72px; height: 72px; }
        .contacts-tab-nav .nav-link { color: #6c757d; }
        .contacts-tab-nav .nav-link.active { color: #90bb13; border-color: #90bb13; }
    </style>
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
    @endphp

    <div id="contact360"
         data-customer-id="{{ $customer->id }}"
         data-base-url="{{ url('panel/contacts/'.$customer->id) }}"
         data-cart-base-url="{{ url('panel/contacts/'.$customer->id.'/cart') }}"
         data-update-url="{{ route('contacts.update', $customer) }}"
         data-merge-search-url="{{ route('contacts.merge.search', $customer) }}"
         data-merge-preview-url="{{ route('contacts.merge.preview', $customer) }}"
         data-merge-execute-url="{{ route('contacts.merge.execute', $customer) }}"
         data-ban-url="{{ url('panel/contacts/'.$customer->id.'/ban') }}"
         data-unban-url="{{ url('panel/contacts/'.$customer->id.'/unban') }}">

        {{-- Hero header --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-3">

                    @if($avatarUrl && ! str_contains($avatarUrl, 'ui-avatars.com'))
                        <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}"
                             width="72" height="72" loading="lazy"
                             class="contacts-hero-avatar rounded-circle object-fit-cover">
                    @else
                        <span class="contacts-hero-avatar rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center fw-bold fs-3">{{ $initials }}</span>
                    @endif

                    <div class="flex-grow-1">
                        <h4 class="mb-1 fw-bold d-flex align-items-center gap-2 flex-wrap">
                            {{ $customer->name ?: 'Sin nombre' }}
                            @if($customer->is_verified ?? ($customer->email_verified_at ?? false))
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-circle-check me-1"></i> Verificado
                                </span>
                            @endif
                            @if($customer->is_banned ?? ($customer->banned_at ?? false))
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="fas fa-ban me-1"></i> Suspendido
                                </span>
                            @endif
                            {{-- Sentiment badge: filled by JS from resumen data.sentiment --}}
                            <span id="contact-sentiment"></span>
                        </h4>
                        <div class="d-flex flex-wrap gap-3 small text-muted">
                            @if($customer->email)
                                <span><i class="fas fa-envelope me-1"></i> {{ $customer->email }}</span>
                            @endif
                            @if($customerPhone)
                                <span><i class="fas fa-phone me-1"></i> {{ $customerPhone }}</span>
                            @endif
                        </div>
                        {{-- Integration pills: filled by JS from resumen data.integrations --}}
                        <div id="contact-integrations" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('contacts.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        @can('contacts.update')
                            <button type="button" id="contact-edit-btn" class="btn btn-outline-secondary">
                                <i class="fas fa-pen me-1"></i> Editar
                            </button>
                            <button type="button" id="contact-sync-btn" class="btn btn-primary">
                                <i class="fas fa-rotate me-1"></i> Sincronizar
                            </button>
                            @if($customer->banned_at)
                                <button type="button" id="contact-unban-btn" class="btn btn-outline-success">
                                    <i class="fas fa-circle-check me-1"></i> Reactivar
                                </button>
                            @else
                                <button type="button" id="contact-ban-btn" class="btn btn-outline-danger">
                                    <i class="fas fa-ban me-1"></i> Suspender
                                </button>
                            @endif
                        @endcan
                        @can('contacts.merge')
                            <button type="button" id="contact-merge-btn" class="btn btn-outline-secondary">
                                <i class="fas fa-code-merge me-1"></i> Fusionar
                            </button>
                        @endcan
                        <a href="{{ route('manager.helpdesk.conversations.create', ['customer' => $customer->id]) }}"
                           class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i> Nueva conversación
                        </a>
                    </div>

                </div>
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
                <h5 class="modal-title" id="editModalLabel">Editar contacto</h5>
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
                        <div class="col-12">
                            <label class="form-label" for="edit-email">Email</label>
                            <input type="email" class="form-control" id="edit-email" name="email"
                                   value="{{ $customer->email }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-phone">Teléfono</label>
                            <input type="text" class="form-control" id="edit-phone" name="phone"
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
                            <label class="form-label" for="edit-notes">Notas internas</label>
                            <textarea class="form-control" id="edit-notes" name="internal_notes"
                                      rows="3">{{ $customer->internal_notes }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
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
                <h5 class="modal-title" id="mergeModalLabel">Fusionar contacto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Busca el contacto duplicado. El contacto actual se conservará y el seleccionado será eliminado tras la fusión.
                </p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="merge-search-input"
                           placeholder="Buscar contacto a fusionar...">
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
                <button type="button" class="btn btn-danger w-100 mb-2" id="merge-execute-btn">Fusionar ahora</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('modules/contacts/js/contacts-360.js') }}?v={{ filemtime(public_path('modules/contacts/js/contacts-360.js')) }}"></script>
@endpush
