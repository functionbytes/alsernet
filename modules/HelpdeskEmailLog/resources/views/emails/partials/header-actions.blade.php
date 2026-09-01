{{--
    Acciones de la cabecera de página (@section('page_header') de
    emails/index.blade.php) — mismo peso visual para los 3 botones
    (.evx-header-btn, ver emaillog.css). Colores literales en vez de las
    variables --evx-*: este partial se renderiza en @yield('page_header'),
    ANTES de .emaillog-index (ver layouts/theme.blade.php), así que esas
    variables no cascan hasta aquí.
--}}
{{-- Actualizar/Reputación/Exportar viven en la barra de herramientas dentro
     de la tarjeta (.evx-toolbar), como en el mockup; aquí arriba queda solo
     Configuración, que es ajuste del módulo, no acción sobre el listado. --}}
<a href="{{ route('settings.helpdeskemaillog.index') }}" class="evx-header-btn">
    <i class="fas fa-gear" aria-hidden="true"></i>{{ __('helpdeskemaillog::emaillog.actions.settings') }}
</a>

{{-- Tag de módulo (mockup): identificador técnico fijo del módulo/ruta, no
     copy de usuario — por eso no pasa por __(), igual que un Message-ID. --}}
<span class="evx-header-module-tag">helpdesk_email_log · panel/helpdeskemaillog</span>
