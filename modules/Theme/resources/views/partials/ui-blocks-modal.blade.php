{{-- UI Blocks Modal — Shortcode Manager (editorial style) --}}
<div class="modal fade" id="uiBlocksModal" tabindex="-1" aria-labelledby="uiBlocksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-md-down modal-dialog-scrollable">
        <div class="modal-content h-100">

            {{-- Top bar --}}
            <div class="uib-topbar d-flex align-items-center">
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <i class="fas fa-cubes uib-brand-icon"></i>
                    <span class="uib-brand-text">Shortcode Manager</span>
                </div>
                <div class="flex-grow-1 mx-4 position-relative">
                    <span class="position-absolute uib-search-icon">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="uiBlocksSearch" autocomplete="off"
                           class="form-control uib-search-input"
                           placeholder="Buscar componente...">
                    <button id="uiSearchClear" type="button" title="Limpiar">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                <button type="button" class="uib-close-btn" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="uiSearchMsg" class="ui-blocks-search-msg px-4"></div>

            @if(empty($shortcodes))
                <div class="modal-body d-flex align-items-center justify-content-center">
                    <div class="text-center py-5" style="color: #abb3b7;">
                        <i class="fas fa-cubes fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">No hay bloques disponibles.</p>
                    </div>
                </div>
            @else
                {{-- Mobile category pills (hidden on lg+) --}}
                <div class="d-lg-none uib-mobile-cats">
                    <div class="d-flex overflow-auto gap-2 px-3 py-2">
                        <button type="button" class="ui-cat-pill active" data-cat="__all__">
                            Todos <span class="ui-cat-pill-count">{{ $uiTotalBlocks ?? '' }}</span>
                        </button>
                        @foreach($orderedCats as $catSlug)
                            @php
                                $catLabel = $uiCategoryLabels->get($catSlug, ucfirst($catSlug));
                                $catCount = $uiGrouped->get($catSlug, collect())->count();
                            @endphp
                            <button type="button" class="ui-cat-pill" data-cat="{{ $catSlug }}">
                                {{ $catLabel }} <span class="ui-cat-pill-count">{{ $catCount }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex uib-body">

                    {{-- Sidebar (hidden on mobile) --}}
                    <div class="d-none d-lg-flex flex-column uib-sidebar">
                        <p class="uib-sidebar-label">Categorías</p>
                        <nav class="overflow-auto uib-sidebar-nav" id="mp-sidebar-tabs">
                            <button type="button" class="uib-nav-btn ui-cat-btn active" data-cat="__all__">
                                <i class="fas fa-border-all uib-nav-icon"></i>
                                <span>Todos</span>
                            </button>
                            @foreach($orderedCats as $catSlug)
                                @php
                                    $catIcon  = $uiCatIconMap[$catSlug] ?? 'fa-circle';
                                    $catLabel = $uiCategoryLabels->get($catSlug, ucfirst($catSlug ?: 'General'));
                                @endphp
                                <button type="button" class="uib-nav-btn ui-cat-btn" data-cat="{{ $catSlug }}">
                                    <i class="fas {{ $catIcon }} uib-nav-icon ui-cat-icon-{{ $catSlug }}"></i>
                                    <span>{{ $catLabel }}</span>
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    {{-- Grid --}}
                    <div class="flex-grow-1 overflow-auto" id="uiBlocksContent">

                        {{-- Content header --}}
                        <div class="uib-content-header">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h3 class="uib-content-title mb-1">Bloques de interfaz</h3>
                                    <p class="uib-content-subtitle mb-0">
                                        Explora y gestiona tu colección de componentes. Selecciona uno para insertarlo directamente en el editor.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- All blocks pane --}}
                        <div class="uib-grid-area">
                            <div class="ui-blocks-pane" data-pane="__all__">
                                <div class="row g-3">
                                    @foreach(collect($shortcodes) as $sc)
                                        @php
                                            $scCat  = $sc['category'] ?? 'otros';
                                            $scIcon = $uiIconMap[$sc['name']] ?? 'fa-cube';
                                            $scDesc = $sc['description'] ?? '';
                                        @endphp
                                        <div class="col-6 col-lg-4 ui-block-item" data-name="{{ strtolower($sc['name']) }}" data-category="{{ $scCat }}">
                                            <div class="card uib-card ui-block-card h-100" data-block-key="{{ $sc['name'] }}" data-block-name="{{ $sc['name'] }}">
                                                <div class="uib-card-icon ui-cat-bg-{{ $scCat }}">
                                                    <i class="fas {{ $scIcon }} ui-cat-icon-{{ $scCat }}"></i>
                                                </div>
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start justify-content-between">
                                                        <div class="uib-card-name" title="[{{ $sc['name'] }}]">[{{ $sc['name'] }}]</div>
                                                        <button type="button" class="uib-card-copy" title="Copiar shortcode">
                                                            <i class="far fa-copy"></i>
                                                        </button>
                                                    </div>
                                                    @if($scDesc)<div class="uib-card-desc mt-1">{{ Str::limit($scDesc, 80) }}</div>@endif
                                                    <span class="uib-card-tag ui-cat-tag-{{ $scCat }}">{{ $uiCategoryLabels->get($scCat, ucfirst($scCat)) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Per-category panes --}}
                            @foreach($orderedCats as $catSlug)
                                @php $catItems = $uiGrouped->get($catSlug, collect()); @endphp
                                <div class="ui-blocks-pane d-none" data-pane="{{ $catSlug }}">
                                    <div class="row g-3">
                                        @foreach($catItems as $sc)
                                            @php
                                                $scIcon = $uiIconMap[$sc['name']] ?? 'fa-cube';
                                                $scDesc = $sc['description'] ?? '';
                                            @endphp
                                            <div class="col-6 col-lg-4 ui-block-item" data-name="{{ strtolower($sc['name']) }}" data-category="{{ $catSlug }}">
                                                <div class="card uib-card ui-block-card h-100" data-block-key="{{ $sc['name'] }}" data-block-name="{{ $sc['name'] }}">
                                                    <div class="uib-card-icon ui-cat-bg-{{ $catSlug }}">
                                                        <i class="fas {{ $scIcon }} ui-cat-icon-{{ $catSlug }}"></i>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start justify-content-between">
                                                            <div class="uib-card-name" title="[{{ $sc['name'] }}]">[{{ $sc['name'] }}]</div>
                                                            <button type="button" class="uib-card-copy" title="Copiar shortcode">
                                                                <i class="far fa-copy"></i>
                                                            </button>
                                                        </div>
                                                        @if($scDesc)<div class="uib-card-desc mt-1">{{ Str::limit($scDesc, 80) }}</div>@endif
                                                        <span class="uib-card-tag ui-cat-tag-{{ $catSlug }}">{{ $uiCategoryLabels->get($catSlug, ucfirst($catSlug)) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            {{-- No search results --}}
                            <div id="uiEmptySearch" class="ui-empty-search">
                                <i class="fas fa-search fa-2x mb-3 opacity-25"></i>
                                <p class="fw-semibold mb-1">Sin resultados</p>
                                <p class="mb-0" style="font-size:12px;">Intenta con otro término</p>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

            {{-- Footer --}}
            <div class="uib-footer d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="uib-footer-text">Shortcode Engine</span>
                    <span class="uib-footer-version">v{{ config('shortcode.version', '1.0') }}</span>
                    <span class="uib-footer-dot"></span>
                </div>
                <button type="button" class="btn btn-sm uib-close-footer-btn" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>
