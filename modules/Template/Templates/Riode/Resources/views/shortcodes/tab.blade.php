@php
    /**
     * [tab] — Emite un marcador especial que el parent [tabs] parsea para
     * construir nav-items y tab-panes por separado.
     *
     * Formato de salida:
     *   SC_TAB_NAV_START{json}SC_TAB_NAV_END
     *   SC_TAB_PANE_START{html}SC_TAB_PANE_END
     *
     * El parent [tabs] extrae ambas secciones con strpos/substr.
     */
    $title = $attrs['title'] ?? '';
    $isActive = filter_var($attrs['active'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $icon = $attrs['icon'] ?? null;
    $tabId = ! empty($attrs['id'])
        ? htmlspecialchars($attrs['id'])
        : 'tab-' . substr(md5($title . uniqid()), 0, 8);
    $panelClass = ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : '';

    // Validar icono: solo FA6 (fas/far/fab fa-*)
    if ($icon && ! preg_match('/^(fas|far|fab)\s+fa-[a-z0-9-]+/', $icon)) {
        $icon = null;
    }

    $navMeta = json_encode([
        'id'       => $tabId,
        'title'    => $title,
        'isActive' => $isActive,
        'icon'     => $icon,
    ]);
@endphp
SC_TAB_NAV_START{{ $navMeta }}SC_TAB_NAV_END
SC_TAB_PANE_START<div class="tab-pane {{ $isActive ? 'active show' : '' }} {{ $panelClass }}" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
{!! $content !!}
</div>SC_TAB_PANE_END
