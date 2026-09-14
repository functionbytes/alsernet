{{-- Subnavegación cruzada entre las pantallas secundarias del módulo
     (antes solo se llegaba a algunas desde otras de forma asimétrica).
     Mismo evx-toolbar/evx-crumbs/evx-header-btn que emails/index.blade.php.

     $current identifica la pestaña activa. Admite además pantallas que NO
     son un botón de la barra (hoy, la papelera): en ese caso se pasa
     'current' => null y 'currentLabel' => '…' para el breadcrumb, y así se
     puede navegar desde ellas sin engordar la barra con un botón más. --}}
@php
    $emaillogSubnavLinks = [
        'settings' => ['route' => 'settings.helpdeskemailactivity.index', 'label' => __('helpdeskemailactivity::emaillog.subnav.settings'), 'icon' => 'fa-sliders'],
        'analytics' => ['route' => 'helpdeskemailactivity.analytics.index', 'label' => __('helpdeskemailactivity::emaillog.subnav.analytics'), 'icon' => 'fa-chart-simple'],
        'reputation' => ['route' => 'helpdeskemailactivity.reputation.index', 'label' => __('helpdeskemailactivity::emaillog.subnav.reputation'), 'icon' => 'fa-shield-halved'],
        'suppressions' => ['route' => 'settings.helpdeskemailactivity.suppressions.index', 'label' => __('helpdeskemailactivity::emaillog.subnav.suppressions'), 'icon' => 'fa-ban'],
        'bounce-mailboxes' => ['route' => 'settings.helpdeskemailactivity.bounce-mailboxes.index', 'label' => __('helpdeskemailactivity::emaillog.subnav.bounce_mailboxes'), 'icon' => 'fa-inbox'],
        'webhook-events' => ['route' => 'settings.helpdeskemailactivity.webhook-events.index', 'label' => __('helpdeskemailactivity::emaillog.subnav.webhook_events'), 'icon' => 'fa-plug-circle-bolt'],
    ];
@endphp
<div class="evx-toolbar">
    <nav class="evx-crumbs" aria-label="breadcrumb">
        <i class="fa-solid fa-headset" aria-hidden="true"></i>
        <span>{{ __('helpdeskemailactivity::emaillog.crumbs.panel') }}</span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>{{ __('helpdeskemailactivity::emaillog.crumbs.helpdesk') }}</span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>{{ __('helpdeskemailactivity::emaillog.title') }}</span>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span class="is-current">{{ $emaillogSubnavLinks[$current]['label'] ?? ($currentLabel ?? '') }}</span>
    </nav>

    <div class="evx-toolbar-actions">
        <a href="{{ route('helpdeskemailactivity.index') }}" class="evx-header-btn">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ __('helpdeskemailactivity::emaillog.actions.back_to_list') }}
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
