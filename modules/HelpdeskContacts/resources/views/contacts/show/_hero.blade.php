<div class="ctf-hero">
    @if($avatarUrl && ! str_contains($avatarUrl, 'ui-avatars.com'))
        <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}" width="54" height="54" loading="lazy"
             class="ctf-hero-ring rounded-circle object-fit-cover">
    @else
        <span class="ctf-hero-ring"><span class="ctf-hero-ring-inner">{{ $customer->initials }}</span></span>
    @endif

    <div class="ctf-hero-body">
        <div class="ctf-hero-name-row">
            <span class="ctf-hero-name contact-hero-name">{{ $customer->name ?: 'Sin nombre' }}</span>
            {{-- "VIP · salud 86" en un solo badge (mockup B): VIP sale del Blade
                 (columna is_vip) y renderCtfHeroBadge() añade la salud al llegar el resumen. --}}
            <span id="ctf-hero-badge" class="ctf-hero-badge{{ $customer->is_vip ? '' : ' d-none' }}"
                  data-vip="{{ $customer->is_vip ? '1' : '0' }}">{{ $customer->is_vip ? 'VIP' : '' }}</span>
            @if($customer->banned_at)
                <span class="ctf-hero-badge is-danger">Bloqueado</span>
            @endif
        </div>
        <div class="ctf-hero-meta">
            @if($customer->email)<span class="mono">{{ $customer->email }}</span>@endif
            @if($customerPhone)<span class="mono">{{ $customerPhone }}</span>@endif
            @if($customer->company || $customer->created_at)
                <span>{{ collect([$customer->company?->name, $customer->created_at ? 'cliente desde '.$customer->created_at->format('Y') : null])->filter()->implode(' · ') }}</span>
            @endif
        </div>
        {{-- Etiquetas: render inicial desde Blade; renderCtfHeroTags() las repinta con resumen.tags --}}
        <div id="ctf-hero-tags" class="ctf-tags{{ $customer->tags->isEmpty() ? ' d-none' : '' }}">
            @foreach($customer->tags as $tag)
                <span class="ctf-tag">{{ $tag->name }}</span>
            @endforeach
        </div>
    </div>

    <div class="ctf-hero-actions">
        @if($customer->whatsapp_phone)
            <button type="button" class="ctf-hero-btn send-hsm-trigger"
                    data-customer-id="{{ $customer->id }}"
                    data-customer-phone="{{ $customer->whatsapp_phone }}"
                    data-customer-name="{{ $customer->name ?: 'este contacto' }}">
                Enviar plantilla
            </button>
        @endif
        @can('contacts.update')
            <button type="button" class="ctf-hero-btn-ghost" data-contact-edit-trigger>Editar</button>
        @endcan
        <div class="dropdown">
                <button type="button" class="ctf-hero-icon-btn" data-bs-toggle="dropdown"
                        aria-expanded="false" title="Más acciones">
                    <i class="fas fa-ellipsis"></i>
                </button>
                {{-- Menú de acciones del contacto (mockup pieza 15): mismo orden que el
                     de la fila del listado; lo destructivo al final sobre fondo gris. --}}
                <ul class="dropdown-menu dropdown-menu-end ct-menu">
                    @can('contacts.update')
                        <li><button type="button" class="dropdown-item" data-contact-edit-trigger>Editar contacto</button></li>
                        <li class="d-none" id="contact-ticket-trigger-item">
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#contact-ticket-modal">Crear ticket</button>
                        </li>
                    @endcan
                    @can('contacts.merge')
                        <li><button type="button" id="contact-merge-btn" class="dropdown-item">Fusionar duplicado</button></li>
                    @endcan
                    @can('contacts.update')
                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#contact-sync-modal">Sincronizar fuentes</button></li>
                @if($linksOn ?? false)
                    <li><button type="button" class="dropdown-item" data-c3k-open>Vínculos e identidad</button></li>
                @endif
                        {{-- Sin Ecommerce no hay carrito asistido local: abre el carrito real de la tienda. --}}
                        <li><button type="button" class="dropdown-item" data-contact-cart-trigger>{{ class_exists('Modules\\Ecommerce\\Services\\OrderService') ? 'Carrito asistido' : 'Carrito de la tienda' }}</button></li>
                        @if(helpdesk_integration_enabled())
                            <li><button type="button" class="dropdown-item external-link-trigger" data-customer-id="{{ $customer->id }}">Vincular plataforma</button></li>
                        @endif
                    @endcan
                    <li><a class="dropdown-item" href="{{ route('manager.helpdesk.conversations.create', ['customer' => $customer->id]) }}">Nueva conversación</a></li>
                    {{-- Recursos del panel PrestaShop del chat (partials/_ps-chat-bridge). --}}
                    @if($erpBridgeOn ?? false)
                        <li><button type="button" class="dropdown-item" data-erp-open="orders">Gestión: pedidos</button></li>
                        <li><button type="button" class="dropdown-item" data-erp-open="finance" data-erp-pane="balance">Gestión: saldo y facturas</button></li>
                        <li><button type="button" class="dropdown-item" data-erp-open="customer">Gestión: ficha de cliente</button></li>
                        <li><button type="button" class="dropdown-item" data-erp-open="loyalty" data-erp-pane="points">Gestión: puntos y vales</button></li>
                    @endif
                    @if($psBridgeOn)
                        <li><button type="button" class="dropdown-item" data-c3-ps-call="openPsCustomerWorkspace" data-c3-ps-arg="account-edit">Cuenta en la tienda</button></li>
                        @if($psCan['voucherCreate'])
                            <li><button type="button" class="dropdown-item" data-c3-ps-call="openPsVoucherCreate">Crear vale de compensación</button></li>
                        @endif
                        <li><button type="button" class="dropdown-item" data-c3-ps-call="openProductRecommend">Recomendar producto</button></li>
                        <li><button type="button" class="dropdown-item" data-c3-ps-call="openPsWishlistSend">Lista de deseos</button></li>
                    @endif
                    @can('contacts.update')
                        <li class="ct-menu-danger">
                            @if($customer->banned_at)
                                <button type="button" id="contact-unban-btn" class="dropdown-item">Desbloquear contacto</button>
                            @else
                                <button type="button" id="contact-ban-btn" class="dropdown-item">Bloquear contacto</button>
                            @endif
                        </li>
                    @endcan
                </ul>
            </div>
    </div>
</div>
