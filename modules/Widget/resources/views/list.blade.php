@extends('layouts.theme')

@section('page_title', 'Widgets')

@section('content')
    @include('core::components.card', ['title' => 'Widgets'])
    @include('core::components.alerts')

    <div class="row g-4">

        {{-- ── Available widgets ────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Widgets disponibles</h6>

                    @if(count($availableWidgets) > 0)
                        <div class="d-flex flex-column gap-2" id="availableWidgetList">
                            @foreach($availableWidgets as $widgetId => $widgetClass)
                                @php
                                    try {
                                        $wInst = new $widgetClass();
                                        $wTitle = $wInst->title();
                                        $wGroup = $wInst->group();
                                    } catch (\Throwable $e) {
                                        $wTitle = class_basename($widgetClass);
                                        $wGroup = '';
                                    }
                                @endphp
                                <div class="card card-sm border available-widget"
                                     draggable="true"
                                     data-widget-id="{{ $widgetId }}"
                                     data-widget-title="{{ $wTitle }}">
                                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                                        <i class="fas fa-grip-vertical text-muted" style="cursor:grab;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small">{{ $wTitle }}</div>
                                            @if($wGroup)
                                                <div class="text-muted" style="font-size:0.75rem;">{{ $wGroup }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-puzzle-piece fa-2x opacity-25 mb-2"></i>
                            <p class="small mb-0">No hay widgets registrados.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Sidebar areas ─────────────────────────────────────────────── --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h6 class="fw-bold mb-0">Áreas de widgets</h6>
                        <span class="badge bg-light text-secondary small border">Tema: {{ $themeName }}</span>
                    </div>

                    @if($groups->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-columns fa-2x opacity-25 mb-2"></i>
                            <p class="small mb-0">El tema activo no tiene áreas de widgets registradas.</p>
                        </div>
                    @else
                        <ul class="nav nav-tabs mb-3" id="sidebarTabs" role="tablist">
                            @foreach($groups as $group)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                            id="tab-{{ $group->getName() }}"
                                            data-bs-toggle="tab"
                                            data-bs-target="#panel-{{ $group->getName() }}"
                                            type="button" role="tab">
                                        {{ $group->getTitle() }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach($groups as $group)
                                @php
                                    $sidebarId     = $group->getName();
                                    $activeWidgets = $savedBySidebar->get($sidebarId, collect());
                                    $allWidgets    = \Modules\Widget\Facades\Widget::getWidgets();
                                @endphp
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                     id="panel-{{ $sidebarId }}"
                                     role="tabpanel">

                                    @if($group->getDescription())
                                        <p class="text-muted mb-3">{{ $group->getDescription() }}</p>
                                    @endif

                                    <div class="sidebar-drop-zone border border-2 border-dashed rounded p-3 mb-3 d-flex flex-column gap-2"
                                         data-sidebar="{{ $sidebarId }}"
                                         style="min-height: 100px;">

                                        @if($activeWidgets->isEmpty())
                                            <div class="drop-placeholder text-center text-muted py-3 small">
                                                <i class="fas fa-arrow-down me-1"></i>Arrastra widgets aquí
                                            </div>
                                        @else
                                            @foreach($activeWidgets as $dbWidget)
                                                @php
                                                    $wClass = $allWidgets[$dbWidget->widget_id] ?? null;
                                                    if ($wClass && class_exists($wClass)) {
                                                        try { $wI = new $wClass($dbWidget->data ?? []); $wT = $wI->title(); } catch (\Throwable $e) { $wT = class_basename($wClass); }
                                                    } else {
                                                        $wT = $dbWidget->widget_id;
                                                    }
                                                @endphp
                                                <div class="active-widget card card-sm border"
                                                     draggable="true"
                                                     data-widget-id="{{ $dbWidget->widget_id }}"
                                                     data-widget-title="{{ $wT }}"
                                                     data-position="{{ $dbWidget->position }}">
                                                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                                                        <i class="fas fa-grip-vertical text-muted" style="cursor:grab;"></i>
                                                        <span class="fw-semibold small flex-grow-1">{{ $wT }}</span>
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger btn-remove-widget py-0 px-1 lh-1"
                                                                data-sidebar="{{ $sidebarId }}"
                                                                data-widget-id="{{ $dbWidget->widget_id }}"
                                                                data-position="{{ $dbWidget->position }}"
                                                                title="Eliminar">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>

                                    <button type="button"
                                            class="btn btn-primary btn-sm btn-save-sidebar"
                                            data-sidebar="{{ $sidebarId }}"
                                            data-url="{{ route('widgets.update') }}">
                                        <i class="fas fa-save me-1"></i>Guardar disposición
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var CSRF           = $('meta[name="csrf-token"]').attr('content');
    var draggedId      = null;
    var draggedTitle   = null;
    var draggedEl      = null;

    // ── Drag start ─────────────────────────────────────────────────────────
    $(document).on('dragstart', '.available-widget, .active-widget', function (e) {
        draggedId    = $(this).data('widget-id');
        draggedTitle = $(this).data('widget-title') || $(this).find('.fw-semibold').text().trim();
        draggedEl    = this;
        e.originalEvent.dataTransfer.effectAllowed = 'move';
    });

    // ── Drag over drop zone ────────────────────────────────────────────────
    $(document).on('dragover', '.sidebar-drop-zone', function (e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        $(this).addClass('border-primary');
    });

    $(document).on('dragleave', '.sidebar-drop-zone', function () {
        $(this).removeClass('border-primary');
    });

    // ── Drop into zone ─────────────────────────────────────────────────────
    $(document).on('drop', '.sidebar-drop-zone', function (e) {
        e.preventDefault();
        $(this).removeClass('border-primary');

        if (!draggedId) { return; }

        var $zone   = $(this);
        var sidebar = $zone.data('sidebar');

        // If dragging existing active-widget within the same zone, just reorder
        if (draggedEl && $(draggedEl).hasClass('active-widget') && $(draggedEl).closest('.sidebar-drop-zone').data('sidebar') === sidebar) {
            return; // handled by dragover on active-widget
        }

        $zone.find('.drop-placeholder').remove();

        var $card = $([
            '<div class="active-widget card card-sm border" draggable="true"',
            '     data-widget-id="' + draggedId + '"',
            '     data-widget-title="' + escHtml(draggedTitle) + '">',
            '  <div class="card-body py-2 px-3 d-flex align-items-center gap-2">',
            '    <i class="fas fa-grip-vertical text-muted" style="cursor:grab;"></i>',
            '    <span class="fw-semibold small flex-grow-1">' + escHtml(draggedTitle) + '</span>',
            '    <button type="button"',
            '            class="btn btn-sm btn-outline-danger btn-remove-widget py-0 px-1 lh-1"',
            '            data-sidebar="' + sidebar + '"',
            '            data-widget-id="' + draggedId + '"',
            '            title="Eliminar">',
            '      <i class="fas fa-times"></i>',
            '    </button>',
            '  </div>',
            '</div>'
        ].join(''));

        $zone.append($card);
        draggedId = null;
        draggedEl = null;
    });

    // ── Reorder active widgets within zone ─────────────────────────────────
    $(document).on('dragover', '.active-widget', function (e) {
        e.preventDefault();
        if (!draggedEl || draggedEl === this) { return; }
        var $this = $(this);
        if (!$this.closest('.sidebar-drop-zone').length) { return; }
        $this.before(draggedEl);
    });

    // ── Remove widget ──────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove-widget', function () {
        var $widget   = $(this).closest('.active-widget');
        var $zone     = $widget.closest('.sidebar-drop-zone');
        var sidebarId = $(this).data('sidebar');
        var widgetId  = $(this).data('widget-id');
        var position  = $(this).data('position');

        $widget.remove();

        if ($zone.find('.active-widget').length === 0) {
            $zone.html('<div class="drop-placeholder text-center text-muted py-3 small"><i class="fas fa-arrow-down me-1"></i>Arrastra widgets aquí</div>');
        }

        if (position !== undefined && position !== '') {
            $.ajax({
                url: '{{ route("widgets.destroy") }}',
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF },
                data: { sidebar_id: sidebarId, widget_id: widgetId, position: position },
                error: function () { toastr.error('Error al eliminar el widget.'); }
            });
        }
    });

    // ── Save sidebar layout ────────────────────────────────────────────────
    $(document).on('click', '.btn-save-sidebar', function () {
        var $btn      = $(this);
        var sidebarId = $btn.data('sidebar');
        var url       = $btn.data('url');

        var items = [];
        $('[data-sidebar="' + sidebarId + '"].sidebar-drop-zone .active-widget').each(function () {
            items.push('id=' + encodeURIComponent($(this).data('widget-id')));
        });

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: { sidebar_id: sidebarId, items: items },
            success: function (res) {
                toastr.success(res.message || 'Disposición guardada.');
            },
            error: function () {
                toastr.error('Error al guardar la disposición.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar disposición');
            }
        });
    });

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}());
</script>
@endpush
