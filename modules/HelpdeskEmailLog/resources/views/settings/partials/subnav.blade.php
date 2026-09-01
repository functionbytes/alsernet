{{-- Subnavegación cruzada entre las 4 pantallas secundarias del módulo
     (antes solo se llegaba a algunas desde otras de forma asimétrica).
     Mismo evx-toolbar/evx-crumbs/evx-header-btn que emails/index.blade.php;
     $current identifica la pestaña activa (ver usos de este include). --}}
@php
    $emaillogSubnavLinks = [
        'settings' => ['route' => 'settings.helpdeskemaillog.index', 'label' => 'Configuración', 'icon' => 'fa-sliders'],
        'reputation' => ['route' => 'helpdeskemaillog.reputation.index', 'label' => 'Reputación', 'icon' => 'fa-shield-halved'],
        'suppressions' => ['route' => 'settings.helpdeskemaillog.suppressions.index', 'label' => 'Lista de supresión', 'icon' => 'fa-ban'],
        'bounce-mailboxes' => ['route' => 'settings.helpdeskemaillog.bounce-mailboxes.index', 'label' => 'Buzones de rebote', 'icon' => 'fa-inbox'],
        'webhook-events' => ['route' => 'settings.helpdeskemaillog.webhook-events.index', 'label' => 'Eventos de webhook', 'icon' => 'fa-plug-circle-bolt'],
    ];
@endphp
<div class="evx-toolbar">
    <nav class="evx-crumbs" aria-label="breadcrumb">
        <i class="fa-solid fa-headset" aria-hidden="true"></i>
        <span>{{ __('helpdeskemaillog::emaillog.crumbs.panel') }}</span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>{{ __('helpdeskemaillog::emaillog.crumbs.helpdesk') }}</span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>{{ __('helpdeskemaillog::emaillog.title') }}</span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span class="is-current">{{ $emaillogSubnavLinks[$current]['label'] }}</span>
    </nav>

    <div class="evx-toolbar-actions">
        <a href="{{ route('helpdeskemaillog.index') }}" class="evx-header-btn">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver al listado
        </a>
        <span class="evx-filter-divider" aria-hidden="true"></span>
        {{-- Route::has(): 'webhook-events' se añadió aquí antes de que su
             ruta quedara registrada en routes/web.php (a propósito, ver
             informe del cambio) — sin este guard, CUALQUIER pantalla de
             settings que incluye este subnav rompía con un
             RouteNotFoundException en cuanto se referenciaba una ruta
             todavía no registrada. Se queda el guard aunque la ruta ya
             exista: es gratis y evita que vuelva a pasar. --}}
        @foreach($emaillogSubnavLinks as $key => $link)
            @continue(! \Illuminate\Support\Facades\Route::has($link['route']))
            <a href="{{ route($link['route']) }}" class="evx-header-btn {{ $key === $current ? 'is-active' : '' }}">
                <i class="fa-solid {{ $link['icon'] }}" aria-hidden="true"></i>
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</div>
