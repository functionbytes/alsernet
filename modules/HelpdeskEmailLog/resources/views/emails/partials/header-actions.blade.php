{{--
    Acciones de la cabecera de página (@section('page_header') de
    emails/index.blade.php) — mismo peso visual para los 3 botones
    (.evx-header-btn, ver emaillog.css). Colores literales en vez de las
    variables --evx-*: este partial se renderiza en @yield('page_header'),
    ANTES de .emaillog-index (ver layouts/theme.blade.php), así que esas
    variables no cascan hasta aquí.
--}}
<a href="{{ route('settings.helpdeskemaillog.index') }}" class="evx-header-btn">
    <i class="fas fa-gear" aria-hidden="true"></i>{{ __('helpdeskemaillog::emaillog.actions.settings') }}
</a>
<a href="{{ request()->fullUrl() }}" class="evx-header-btn">
    <i class="fa-solid fa-rotate" aria-hidden="true"></i>{{ __('helpdeskemaillog::emaillog.actions.refresh') }}
</a>
<a href="{{ route('helpdeskemaillog.reputation.index') }}" class="evx-header-btn">
    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>{{ __('helpdeskemaillog::emaillog.actions.reputation') }}
</a>

{{-- Tag de módulo (mockup): identificador técnico fijo del módulo/ruta, no
     copy de usuario — por eso no pasa por __(), igual que un Message-ID. --}}
<span class="evx-header-module-tag">helpdesk_email_log · panel/helpdeskemaillog</span>
