{{--
  Partial: Cabecera estándar para modales Bootstrap del Helpdesk.

  Variables:
    $icon        — Clase FA completa, ej: 'far fa-envelope-open'
    $label       — Categoría en mayúsculas, ej: 'EMAILS'
    $labelSub    — (opcional) Subcategoría resaltada, ej: 'ENVIADOS'
    $titleId     — (opcional) ID del <span> de título para manipulación JS
    $titleText   — Texto por defecto del título
    $chipId      — (opcional) ID del badge chip (oculto por defecto, JS lo muestra)
    $iconVariant — (opcional) Variante de color del círculo: 'primary' (defecto), 'danger', 'warning', 'success'
--}}
@php
    $iconVariant = $iconVariant ?? 'primary';
@endphp
<div class="modal-header border-bottom gap-3 py-3 align-items-start">
    <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width:0">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $iconVariant }}-subtle flex-shrink-0"
             style="width:38px;height:38px;font-size:14px">
            <i class="{{ $icon }} text-{{ $iconVariant }}"></i>
        </div>
        <div style="min-width:0">
            <div class="small text-uppercase text-muted fw-semibold lh-1 mb-1">
                {{ $label }}@isset($labelSub) · <span class="text-{{ $iconVariant }}">{{ $labelSub }}</span>@endisset
            </div>
            <div class="fw-semibold lh-sm" style="min-width:0">
                @isset($titleId)
                    <span id="{{ $titleId }}">{{ $titleText ?? '' }}</span>
                @else
                    {{ $titleText ?? '' }}
                @endisset
                @isset($chipId)
                    <span class="badge bg-secondary-subtle text-secondary ms-1 d-none" id="{{ $chipId }}"></span>
                @endisset
            </div>
        </div>
    </div>
    <button type="button" class="btn-close flex-shrink-0 mt-1" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>
