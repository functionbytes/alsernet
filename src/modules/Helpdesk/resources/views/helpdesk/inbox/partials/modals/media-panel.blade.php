{{-- Modal: Archivos compartidos del chat (#20 ve-media-panel) --}}
<div class="bv-modal" data-bv-modal-name="media-panel">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-folder-open"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Filtros --}}
            <div class="bv-hist-pills bv-media-pills" id="mediaPanelFilter">
                <button class="bv-hist-pill on" data-mpf="all">{{ __('helpdesk::helpdesk.inbox.modals.all_label') }} <span class="bv-pill-count" id="mpCountAll">0</span></button>
                <button class="bv-hist-pill" data-mpf="image"><i class="far fa-image"></i> <span class="bv-pill-count" id="mpCountImage">0</span></button>
                <button class="bv-hist-pill" data-mpf="video"><i class="fas fa-video"></i> <span class="bv-pill-count" id="mpCountVideo">0</span></button>
                <button class="bv-hist-pill" data-mpf="document"><i class="far fa-file-lines"></i> <span class="bv-pill-count" id="mpCountDocument">0</span></button>
            </div>

            {{-- Vista toggle --}}
            <div class="bv-x32">
                <div class="bv-media-view-toggle" id="mpViewToggle">
                    <button type="button" class="on" data-mpv="grid" title="{{ __('helpdesk::helpdesk.inbox.right.view_grid_title') }}"><i class="fas fa-grip"></i></button>
                    <button type="button" data-mpv="list" title="{{ __('helpdesk::helpdesk.inbox.right.view_list_title') }}"><i class="fas fa-list"></i></button>
                </div>
                <div class="bv-x33"></div>
                <select id="mpSort" class="bv-form-input bv-x34">
                    <option value="recent">{{ __('helpdesk::helpdesk.inbox.right.sort_recent') }}</option>
                    <option value="oldest">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_sort_oldest') }}</option>
                    <option value="size">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_sort_size') }}</option>
                </select>
            </div>

            <div id="mediaPanelLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="mediaPanelGrid" class="bv-media-grid" style="display:none"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-media-download" disabled>{{ __('helpdesk::helpdesk.inbox.right.download_selection') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.media_panel_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/media-panel.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/media-panel.js')) }}"></script>
@endpush
@endonce
