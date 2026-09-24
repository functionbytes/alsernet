@extends('layouts.theme')

@section('title', $customer->name ?: 'Contacto')

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Tokens --psc-* (paleta verde compartida con HelpdeskPrestashop) — contacts.css los consume para su semántica de color. --}}
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-chat.css') }}?v={{ filemtime(public_path('modules/helpdeskprestashop/css/prestashop-chat.css')) }}">
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
        // Contactos creados desde un canal social (WhatsApp/Facebook/Instagram) solo
        // tienen su identificador de plataforma relleno — `phone` queda null. Usamos
        // el de WhatsApp como teléfono de contacto cuando no hay uno genérico.
        $customerPhone = $customer->phone ?: $customer->whatsapp_phone;

        // ?action= (whitelist ya aplicada en el controlador): los modales se
        // renderizan siempre, sin @can, así que el permiso se exige aquí para
        // que un agente de solo lectura no abra formularios que luego darían 403.
        // ban/unban se descartan si el estado real del contacto los contradice.
        // Recursos del panel PrestaShop del chat (partials/_ps-chat-bridge): solo
        // si el agente puede usarlos — sin permiso, las acciones no se pintan
        // (sus controladores lo exigen igualmente y responderían 403).
        $psUser = auth()->user();
        $psBridgeOn = helpdesk_integration_enabled()
            && (bool) \Nwidart\Modules\Facades\Module::find('HelpdeskPrestashop')?->isEnabled()
            && view()->exists('helpdeskprestashop::modals.order-workspace')
            && $psUser?->can('helpdeskprestashop.view')
            && $psUser->can('view', $customer);
        // Gestión (ERP) en la ficha: los modales de HelpdeskErp para el chat
        // (pedidos, ficha de cliente, finanzas, fidelización), solo lectura.
        $erpBridgeOn = helpdesk_integration_enabled()
            && (bool) \Nwidart\Modules\Facades\Module::find('HelpdeskErp')?->isEnabled()
            && view()->exists('helpdeskerp::modals.order-workspace')
            && $psUser?->can('helpdeskerp.view')
            && $psUser->can('view', $customer);
        $psCan = [
            'orders' => $psBridgeOn && $psUser->can('helpdeskprestashop.orders.view'),
            'voucherCreate' => $psBridgeOn && ($psUser->can('helpdeskprestashop.vouchers.create') || $psUser->can('helpdeskprestashop.vouchers.approve')),
            'voucherEdit' => $psBridgeOn && $psUser->can('helpdeskprestashop.vouchers.edit'),
        ];

        $notes = $customer->notes()->with('author')->limit(20)->get();

        // Modal "Vínculos e identidad": necesita HelpdeskIntegration encendido.
        $linksOn = helpdesk_integration_enabled()
            && class_exists(\Modules\HelpdeskIntegration\Services\CustomerIntegrationService::class);

        // Pestañas de detalle: solo las fuentes cuyo módulo está encendido.
        $moduleOn = fn (string $name) => (bool) \Nwidart\Modules\Facades\Module::find($name)?->isEnabled();
        $detailTabs = array_filter([
            'perfil' => 'Resumen',
            'conversaciones' => 'Conversaciones',
            // Sin "Chats web": los chats de la web y sociales ya son conversaciones;
            // la navegación del cliente se pinta al final de Conversaciones.
            'erp' => $moduleOn('HelpdeskErp') ? 'ERP' : null,
            'prestashop' => $moduleOn('HelpdeskPrestashop') ? 'PrestaShop' : null,
            'tienda' => $moduleOn('Remarketing') ? 'Tienda local' : null,
            'actividad' => 'Actividad',
            'tickets' => function_exists('helpdesk_tickets_enabled') && helpdesk_tickets_enabled() ? 'Tickets' : null,
        ]);

        $layout = $layout ?? \Modules\HelpdeskContacts\Support\ContactLayouts::DEFAULT;

        $autoAction = $autoAction ?? null;
        $canUpdateContact = (bool) auth()->user()?->can('contacts.update');
        $autoAction = match (true) {
            $autoAction === 'merge' => auth()->user()?->can('contacts.merge') ? 'merge' : null,
            $autoAction === 'ban' => $canUpdateContact && ! $customer->banned_at ? 'ban' : null,
            $autoAction === 'unban' => $canUpdateContact && $customer->banned_at ? 'unban' : null,
            $autoAction !== null => $canUpdateContact ? $autoAction : null,
            default => null,
        };
    @endphp

    <div id="contact360" class="c360-layout-{{ $layout }}"
         data-layout="{{ $layout }}"
         data-rail-url="{{ route('contacts.rail') }}"
         data-customer-id="{{ $customer->id }}"
         data-customer-email="{{ $customer->email }}"
         data-base-url="{{ url('panel/helpdesk/contacts/'.$customer->id) }}"
         data-ps-cart-base-url="{{ url('panel/helpdesk/customers/'.$customer->id.'/ps/cart') }}"
         data-ps-cartpay-base="{{ url('panel/helpdesk/customers/'.$customer->id.'/ps/ext/cartpay') }}"
         data-ps-rma-base="{{ url('panel/helpdesk/customers/'.$customer->id.'/ps/ext/refunds/rma') }}"
         data-ps-addresses-url="{{ url('panel/helpdesk/customers/'.$customer->id.'/ps/addresses') }}"
         data-ps-country-states-url="{{ url('panel/helpdesk/ps/country-states') }}"
         data-ps-returns-url="{{ url('panel/helpdesk/customers/'.$customer->id.'/ps/returns') }}"
         @if($psBridgeOn) data-ps-rever-url="{{ url('panel/helpdesk/customers/'.$customer->id.'/ps/ext/rever/exchanges') }}" @endif
         data-ps-products-url="{{ url('panel/helpdesk/customers/'.$customer->id.'/ps/products') }}"
         data-cart-base-url="{{ url('panel/helpdesk/contacts/'.$customer->id.'/cart') }}"
         data-update-url="{{ route('contacts.update', $customer) }}"
         data-can-update="{{ $canUpdateContact ? '1' : '0' }}"
         data-erp-chat="{{ $erpBridgeOn ? '1' : '0' }}"
         data-ps-can-orders="{{ $psCan['orders'] ? '1' : '0' }}"
         data-ps-can-voucher-create="{{ $psCan['voucherCreate'] ? '1' : '0' }}"
         data-ps-can-voucher-edit="{{ $psCan['voucherEdit'] ? '1' : '0' }}"
         data-assisted-cart="{{ class_exists('Modules\\Ecommerce\\Services\\OrderService') ? '1' : '0' }}"
         @if($autoAction) data-auto-action="{{ $autoAction }}" @endif
         data-merge-search-url="{{ route('contacts.merge.search', $customer) }}"
         data-merge-preview-url="{{ route('contacts.merge.preview', $customer) }}"
         data-merge-execute-url="{{ route('contacts.merge.execute', $customer) }}"
         data-ban-url="{{ url('panel/helpdesk/contacts/'.$customer->id.'/ban') }}"
         data-unban-url="{{ url('panel/helpdesk/contacts/'.$customer->id.'/unban') }}">

        {{-- Ficha 360: el estilo (Ajustes → Helpdesk · Contactos) compone las
             mismas piezas de contacts/show/ — ids idénticos en todos. --}}
        @include('contacts::contacts.show.layouts.'.$layout)

    </div>

    {{-- Los 4 modales de abajo vivían fuera de @section('content'): contenido
         suelto en una vista con @extends se renderiza en cuanto Blade lo
         ejecuta, antes de que el layout compagine el resto — se colaban
         delante del <!DOCTYPE html>, metiendo la página entera en Quirks
         Mode. Movidos dentro de la sección para que salgan donde deben. --}}

    {{-- Modal: editar contacto --}}
<div class="modal fade ct-mdl" id="contact-edit-modal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-pen"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Datos</div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="editModalLabel">
                        Editar contacto
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="contact-edit-form">
                <div class="modal-body">
                    <div class="row g-3">
                        @php
                            // La ficha guarda un único "name"; el formulario lo parte en la
                            // primera palabra (Nombre) y el resto (Apellidos), como el mockup.
                            [$editFirst, $editLast] = array_pad(explode(' ', trim((string) $customer->name), 2), 2, '');
                        @endphp
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="edit-first-name">Nombre</label>
                            <input type="text" class="form-control" id="edit-first-name" name="first_name"
                                   value="{{ $editFirst }}" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="edit-last-name">Apellidos</label>
                            <input type="text" class="form-control" id="edit-last-name" name="last_name"
                                   value="{{ $editLast }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-company">Empresa</label>
                            <input type="text" class="form-control" id="edit-company" name="company"
                                   value="{{ $customer->company?->name }}" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-email">Email principal</label>
                            <input type="email" class="form-control ct-mono" id="edit-email" name="email"
                                   value="{{ $customer->email }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-phone">Teléfono</label>
                            <input type="text" class="form-control ct-mono" id="edit-phone" name="phone"
                                   value="{{ $customerPhone }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-tags">Etiquetas</label>
                            {{-- select2 en modo tags (creación libre); las opciones sugeridas se cargan
                                 por AJAX (route contacts.tags.index) al abrir el modal. --}}
                            <select class="form-select" id="edit-tags" name="tags[]" multiple
                                    data-tags-url="{{ route('contacts.tags.index') }}"
                                    data-placeholder="Añadir etiquetas…">
                                @foreach($customer->tags as $tag)
                                    <option value="{{ $tag->name }}" selected>{{ $tag->name }}</option>
                                @endforeach
                            </select>
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
                            <label class="form-label" for="edit-owner">Responsable</label>
                            {{-- Solo el responsable actual va renderizado; el resto de agentes
                                 asignables se piden por AJAX (route contacts.owners.index) al
                                 abrir el modal. Si el actual ya no es asignable, sigue
                                 apareciendo para que guardar otros campos no lo quite. --}}
                            <select class="form-select" id="edit-owner" name="owner_id"
                                    data-owners-url="{{ route('contacts.owners.index') }}">
                                <option value="">Sin responsable</option>
                                @if($customer->owner_id)
                                    <option value="{{ $customer->owner_id }}" selected>{{ $customer->owner?->full_name ?: 'Agente #'.$customer->owner_id }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="ct-fcheck" for="edit-is-vip">
                                <input type="checkbox" id="edit-is-vip" name="is_vip" value="1" @checked($customer->is_vip)>
                                <span class="ct-fcheck-grow">Cliente VIP</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex" for="edit-notes">
                                Notas internas
                                <span class="ms-auto small text-muted fw-normal">solo visible para agentes</span>
                            </label>
                            <textarea class="form-control" id="edit-notes" name="internal_notes"
                                      rows="3">{{ $customer->internal_notes }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="ct-note-box">Cambiar el email puede romper la vinculación con ERP y tienda: se avisa y queda en la pestaña de actividad.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="psc-btn psc-btn--primary">Guardar cambios</button>
                    <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: fusionar duplicados (mockup pieza 06). Paso 1: elegir el duplicado;
     paso 2: "Se conserva / Se absorbe" con lo que se moverá. --}}
<div class="modal fade ct-mdl" id="contact-merge-modal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ct-mdl-md">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon"><i class="fas fa-compress"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contactos · Fusión</div>
                    <h5 class="modal-title" id="mergeModalLabel">Fusionar duplicados</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-2">
                <div id="merge-step-search" class="d-flex flex-column gap-2">
                    <div class="search-field-inline">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" class="ct-finput" id="merge-search-input"
                               placeholder="Busca el duplicado por nombre, email o teléfono…">
                    </div>
                    <div id="merge-search-results"></div>
                </div>
                <div id="merge-preview" class="d-none d-flex flex-column gap-2">
                    <div id="merge-preview-content" class="d-flex flex-column gap-2"></div>
                    <div class="ct-note-box">
                        <i class="fas fa-triangle-exclamation mt-1"></i>
                        <span>La fusión archiva el contacto absorbido y reasigna su historial al que se conserva. Queda auditada y no se puede deshacer desde aquí.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="psc-btn psc-btn--danger d-none" id="merge-execute-btn">Fusionar contactos</button>
                <button type="button" class="psc-btn psc-btn--outline d-none" id="merge-back-btn">Elegir otro duplicado</button>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

    @include('contacts::contacts.partials._send-hsm-modal')
    @include('contacts::contacts.partials._ps-chat-bridge')
    @include('contacts::contacts.show._command-palette')
    @if($linksOn)
        @include('contacts::contacts.show._links-modal')
    @endif
    @if(helpdesk_integration_enabled())
        @include('contacts::contacts.partials._external-search-modal')
    @endif

{{-- Modal: bloquear / desbloquear contacto --}}
<div class="modal fade ct-mdl" id="contact-ban-modal" tabindex="-1" aria-labelledby="banModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-ban"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Acceso</div>
                    <h5 class="modal-title" id="banModalLabel">
                        {{ $customer->banned_at ? 'Desbloquear contacto' : 'Bloquear contacto' }}
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                @if($customer->banned_at)
                    <div class="d-flex flex-column gap-2">
                        <div class="ct-kv"><span class="k">Bloqueado por</span><span class="v">{{ $bannedBy ?? 'sin registro' }}</span></div>
                        <div class="ct-kv"><span class="k">Fecha</span><span class="v">{{ $customer->banned_at->translatedFormat('d M Y · H:i') }}</span></div>
                        <div class="ct-kv"><span class="k">Motivo</span><span class="v">{{ $customer->ban_reason ?: '—' }}</span></div>
                    </div>
                @else
                    <div class="ct-note-box mb-3">
                        <i class="fas fa-ban mt-1"></i>
                        <span>Bloquear oculta al contacto del inbox y descarta sus mensajes entrantes. No borra el historial.</span>
                    </div>
                    <div class="mb-2">
                        <label class="ct-flabel" for="contact-ban-reason">Motivo</label>
                        <select class="ct-fselect" id="contact-ban-reason">
                            <option value="Spam reiterado">Spam reiterado</option>
                            <option value="Abuso al agente">Abuso al agente</option>
                            <option value="Petición del cliente">Petición del cliente</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="ct-flabel" for="contact-ban-note">Nota interna (opcional)</label>
                        <textarea class="ct-finput" id="contact-ban-note" rows="2"></textarea>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                @if($customer->banned_at)
                    <button type="button" class="psc-btn psc-btn--primary" id="contact-unban-confirm-btn">Desbloquear</button>
                @else
                    <button type="button" class="psc-btn psc-btn--danger" id="contact-ban-confirm-btn">Bloquear contacto</button>
                @endif
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: sincronizar fuentes. La lista se pinta por JS (renderCtfSyncModal(),
     contacts-360.js) desde ctfData.resumen.integrations, ya cargado — sin
     petición nueva al abrir. Solo existe un endpoint POST {base}/sync que
     sincroniza TODAS las plataformas a la vez (no hay uno por-plataforma), así
     que "Solo la fuente con error" del mockup se sustituye por un único botón
     "Sincronizar todo" que solo se muestra si hace falta. --}}
<div class="modal fade ct-mdl" id="contact-sync-modal" tabindex="-1" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-arrows-rotate"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Fuentes</div>
                    <h5 class="modal-title" id="syncModalLabel">Sincronizar fuentes</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-2 mb-3 ct-sync-list" id="contact-sync-list">
                    <div class="ctf-skel-line"></div>
                    <div class="ctf-skel-line"></div>
                </div>
                <div class="ct-note-box">
                    Al terminar, cada pestaña ya cargada se marca como caducada y se vuelve a pedir solo cuando la abres.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="psc-btn psc-btn--primary" id="contact-sync-confirm-btn">Sincronizar todo</button>
                <button type="button" class="psc-btn psc-btn--outline d-none" id="contact-sync-failed-btn">Solo la fuente con error</button>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: crear ticket — formulario estático; el <select> de categorías se
     rellena por JS con los datos ya cargados en CTF_TABS (fetch de 'tickets'). --}}
<div class="modal fade ct-mdl" id="contact-ticket-modal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-ticket"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Ticket</div>
                    <h5 class="modal-title" id="ticketModalLabel">Crear ticket</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="ticket-create-form">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="ticket-subject">Asunto</label>
                        <input type="text" class="form-control" id="ticket-subject" name="subject" placeholder="Describe el motivo del ticket" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" for="ticket-category">Categoría</label>
                            <select class="form-select" id="ticket-category" name="category_id">
                                <option value="">Sin categoría</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="ticket-priority">Prioridad</label>
                            <select class="form-select" id="ticket-priority" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="ticket-message">Mensaje al cliente (opcional)</label>
                        <textarea class="form-control" id="ticket-message" name="message" rows="3"></textarea>
                    </div>
                    {{-- Se añade como nota interna del ticket (solo agentes). --}}
                    <label class="ct-fcheck">
                        <input type="checkbox" name="attach_context" value="1" checked>
                        <span class="ct-fcheck-grow">Adjuntar el contexto de la ficha 360 al ticket</span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="psc-btn psc-btn--primary">Crear ticket</button>
                    <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: carrito asistido — venta directa del agente (distinto del carrito
     en vivo de PrestaShop de #ps-cart-detail-modal). Contenido ya existente en
     loadCart()/renderCarrito(), antes oculto en el viejo #pane-carrito. --}}
<div class="modal fade ct-mdl" id="contact-cart-modal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-cart-plus"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Carrito asistido</div>
                    <h5 class="modal-title" id="cartModalLabel">Carrito de {{ $customer->name ?: 'este contacto' }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="pane-carrito" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 3])
            </div>
        </div>
    </div>
</div>

{{-- Carrito en vivo de PrestaShop con el diseño del "Carrito asistido" (mockup
     pieza 11). Sin el módulo Ecommerce (carrito asistido local) es el carrito
     que abre "Carrito asistido": el real del cliente en la tienda. --}}
<div class="modal fade ct-mdl" id="ps-cart-detail-modal" tabindex="-1" aria-labelledby="psCartDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ct-mdl-md">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon"><i class="fas fa-cart-plus"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Carrito asistido</div>
                    <h5 class="modal-title" id="psCartDetailModalLabel">Carrito de {{ $customer->name ?: 'este contacto' }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-2" id="ps-cart-detail-body">
                @include('contacts::contacts.partials._skeleton', ['rows' => 3])
            </div>
            <div class="modal-footer">
                <div id="ps-cart-foot-actions" class="ext-foot-stack"></div>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Resolver devolución: estados reales de la RMA en PrestaShop (extensión
     refunds de HelpdeskPrestashop, refunds.rma_set_state). --}}
<div class="modal fade ct-mdl" id="contact-rma-modal" tabindex="-1" aria-labelledby="rmaModalLabel" aria-hidden="true"
     data-denied-state="{{ (int) config('helpdeskprestashop.ext.refunds.rma_states.denied', 4) }}">
    <div class="modal-dialog modal-dialog-centered ct-mdl-md">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon"><i class="fas fa-rotate-left"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">PrestaShop · Devolución</div>
                    <h5 class="modal-title" id="rmaModalLabel">Resolver devolución</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-2" id="contact-rma-body">
                @include('contacts::contacts.partials._skeleton', ['rows' => 3])
            </div>
            <div class="modal-footer">
                <button type="button" class="psc-btn psc-btn--primary d-none" id="contact-rma-save">Guardar estado</button>
                <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Selector de dirección (para cambiar la dirección del carrito) --}}
<div class="modal fade ct-mdl" id="ps-address-picker-modal" tabindex="-1" aria-labelledby="psAddressPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-location-dot"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">PrestaShop · Direcciones</div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="psAddressPickerModalLabel">
                        Elegir dirección de envío
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="ps-address-picker-body">
                @include('contacts::contacts.partials._skeleton', ['rows' => 3])
            </div>
        </div>
    </div>
</div>

{{-- Crear / editar dirección --}}
<div class="modal fade ct-mdl" id="ps-address-form-modal" tabindex="-1" aria-labelledby="psAddressFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-location-dot"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">PrestaShop · Direcciones</div>
                    <h5 class="modal-title" id="psAddressFormModalLabel">Añadir dirección</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="ps-address-form">
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="ct-flabel">Nombre de la dirección</label>
                            <input type="text" class="ct-finput" name="alias" placeholder="Ej. Casa, Oficina">
                        </div>
                        <div class="col-6">
                            <label class="ct-flabel">Nombre</label>
                            <input type="text" class="ct-finput" name="firstname" required>
                        </div>
                        <div class="col-6">
                            <label class="ct-flabel">Apellidos</label>
                            <input type="text" class="ct-finput" name="lastname" required>
                        </div>
                        <div class="col-12">
                            <label class="ct-flabel">Empresa</label>
                            <input type="text" class="ct-finput" name="company">
                        </div>
                        <div class="col-12">
                            <label class="ct-flabel">Dirección</label>
                            <input type="text" class="ct-finput" name="address1" required>
                        </div>
                        <div class="col-12">
                            <label class="ct-flabel">Dirección (línea 2)</label>
                            <input type="text" class="ct-finput" name="address2">
                        </div>
                        <div class="col-4">
                            <label class="ct-flabel">C.P.</label>
                            <input type="text" class="ct-finput" name="postcode" required>
                        </div>
                        <div class="col-8">
                            <label class="ct-flabel">Ciudad</label>
                            <input type="text" class="ct-finput" name="city" required>
                        </div>
                        <div class="col-12">
                            <label class="ct-flabel">Provincia</label>
                            <select class="ct-fselect" name="id_state" id="ps-address-state-select">
                                <option value="">Selecciona...</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="ct-flabel">Teléfono</label>
                            <input type="text" class="ct-finput" name="phone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="psc-btn psc-btn--primary" id="ps-address-form-submit">Guardar dirección</button>
                    <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('modules/contacts/js/contacts-360.js') }}?v={{ filemtime(public_path('modules/contacts/js/contacts-360.js')) }}"></script>
    <script src="{{ asset('modules/contacts/js/contacts-360-layouts.js') }}?v={{ filemtime(public_path('modules/contacts/js/contacts-360-layouts.js')) }}"></script>
    <script src="{{ asset('modules/contacts/js/contacts-links.js') }}?v={{ filemtime(public_path('modules/contacts/js/contacts-links.js')) }}"></script>
    <script src="{{ asset('modules/contacts/js/send-hsm-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/send-hsm-modal.js')) }}"></script>
    @if(helpdesk_integration_enabled())
    <script src="{{ asset('modules/contacts/js/external-search-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/external-search-modal.js')) }}"></script>
    @endif
@endpush
