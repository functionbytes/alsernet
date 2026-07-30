{{-- Contenido de la lista de expedientes del tab Documentos. Extraído a
     partial para poder re-renderizarlo vía AJAX (refresco del tab). Espera
     $rpDocuments (DocumentPanelPresenter::list) y $rpConvo (Conversation). --}}
@php
    $docList   = $rpDocuments ?? [];
    $docCount  = count($docList);

    $statusGroup = function (string $key): string {
        return match ($key) {
            'approved', 'completed'  => 'approved',
            'rejected', 'cancelled'  => 'rejected',
            default                  => 'pending',
        };
    };

    $grouped   = collect($docList)->groupBy(fn ($d) => $statusGroup($d['status_key'] ?? 'pending'));
    $pendCount = $grouped->get('pending', collect())->count();
    $apprCount = $grouped->get('approved', collect())->count();
    $rejCount  = $grouped->get('rejected', collect())->count();
@endphp

@if($docCount === 0)
    <div class="bv-tab-empty">
        <i class="far fa-folder-open"></i>
        <div class="bv-tab-empty-title">Sin documentación</div>
        <div class="bv-tab-empty-sub">Este cliente no tiene expedientes registrados</div>
        <button type="button" class="btn btn-primary btn-sm mt-2" data-docs-create>
            Crear expediente
        </button>
    </div>
@else
    <div class="tk-panel-head">
        <span class="num">{{ $docCount }}</span>
        <div class="meta">
            <span class="lbl">Documentos</span>
            <span class="sub">
                {{ $pendCount }} pendiente{{ $pendCount === 1 ? '' : 's' }}
                @if($apprCount > 0) · {{ $apprCount }} aprobado{{ $apprCount === 1 ? '' : 's' }} @endif
            </span>
        </div>
        <button type="button" class="btn-icon ms-auto" data-docs-create
                title="Nuevo expediente" aria-label="Nuevo expediente">
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>

    <div class="tk-search-row">
        <div class="tk-search-wrap">
            <i class="fa-solid fa-magnifying-glass tk-search-icon"></i>
            <input type="text" class="tk-search-input docs-list-search"
                   placeholder="Buscar por ref. o tipo…">
        </div>
    </div>

    <div class="tk-filter-row">
        <button type="button" class="media-pill docs-list-filter on" data-filter="all">
            Todos <span class="c">{{ $docCount }}</span>
        </button>
        @if($pendCount > 0)
            <button type="button" class="media-pill docs-list-filter" data-filter="pending">
                Pendientes <span class="c">{{ $pendCount }}</span>
            </button>
        @endif
        @if($apprCount > 0)
            <button type="button" class="media-pill docs-list-filter" data-filter="approved">
                Aprobados <span class="c">{{ $apprCount }}</span>
            </button>
        @endif
        @if($rejCount > 0)
            <button type="button" class="media-pill docs-list-filter" data-filter="rejected">
                Rechazados <span class="c">{{ $rejCount }}</span>
            </button>
        @endif
    </div>

    <div class="sort-row">
        <span class="sort-lbl">Ordenar por</span>
        <div class="sort-dd">
            <button type="button" class="sort-trigger docs-sort-trigger">
                <i class="fa-solid fa-arrow-down-short-wide lead"></i>
                <span class="docs-sort-label">Más reciente</span>
                <i class="fa-solid fa-chevron-down chev"></i>
            </button>
        </div>
        <div class="sort-backdrop docs-sort-backdrop"></div>
        <div class="sort-menu docs-sort-menu">
            <button type="button" class="sort-item sel" data-sort="date-desc">
                <span class="ico"><i class="fa-solid fa-clock-rotate-left"></i></span>
                <span class="sort-item-lbl">Más reciente</span>
                <span class="ck"><i class="fa-solid fa-check"></i></span>
            </button>
            <button type="button" class="sort-item" data-sort="date-asc">
                <span class="ico"><i class="fa-solid fa-clock"></i></span>
                <span class="sort-item-lbl">Más antiguo</span>
                <span class="ck"></span>
            </button>
            <button type="button" class="sort-item" data-sort="name">
                <span class="ico"><i class="fa-solid fa-font"></i></span>
                <span class="sort-item-lbl">Por tipo</span>
                <span class="ck"></span>
            </button>
            <button type="button" class="sort-item" data-sort="status">
                <span class="ico"><i class="fa-solid fa-tag"></i></span>
                <span class="sort-item-lbl">Por estado</span>
                <span class="ck"></span>
            </button>
        </div>
    </div>

    <div class="tk-list" data-docs-list>
        @foreach($docList as $d)
            @php
                $grp = $statusGroup($d['status_key'] ?? 'pending');
                $panelUrl = $rpConvo
                    ? route('manager.helpdesk.conversations.documents.panel', [$rpConvo->id, $d['id']])
                    : '#';
                $fileUploaded = $d['file_uploaded'] ?? $d['file_count'] ?? 0;
                $fileTotal    = $d['file_total']    ?? $d['file_count'] ?? 0;
                $progressPct  = $d['progress_pct'] ?? ($fileTotal > 0 ? (int) round(($fileUploaded / max($fileTotal, 1)) * 100) : 0);
                $agoHuman     = $d['ago_human'] ?? $d['created_human'] ?? '—';
            @endphp
            <button type="button" class="tk-card docs-list-card"
                    data-docs-open="{{ $panelUrl }}"
                    data-doc-tags="all {{ $grp }}"
                    data-doc-status="{{ $grp }}"
                    data-doc-name="{{ strtolower($d['type_label']) }}"
                    data-doc-ref="{{ strtolower($d['order_reference']) }}"
                    data-doc-date="{{ $d['created_human'] ?? '' }}">
                <div class="docs-card-header">
                    <div class="docs-card-info">
                        <span class="title">{{ $d['customer_name'] ?: $d['type_label'] }}</span>
                        <span class="id">#{{ $d['order_reference'] }} · {{ $d['type_label'] }}</span>
                    </div>
                    <span class="docs-status {{ $grp }}">{{ $d['status_label'] }}</span>
                </div>
                @php $desc = !empty($d['description']) ? __($d['description']) : ''; @endphp
                @if($desc && $desc !== $d['description'])
                    <div class="docs-card-desc">{{ $desc }}</div>
                @endif
                @if($fileTotal > 0)
                    <div class="docs-card-prog">
                        <div class="docs-card-prog-track">
                            <div class="docs-card-prog-fill {{ $grp }}" style="--pct: {{ $progressPct }}%"></div>
                        </div>
                        <span class="docs-card-docs-lbl">{{ $fileTotal }} docs</span>
                    </div>
                @endif
                <div class="foot">
                    <span class="seg">
                        <i class="fa-regular fa-file"></i>
                        @if($fileTotal > 0)
                            {{ $fileUploaded }}/{{ $fileTotal }}
                        @else
                            {{ $fileUploaded }} archivo{{ $fileUploaded !== 1 ? 's' : '' }}
                        @endif
                    </span>
                    <span class="seg docs-card-time">
                        <i class="fa-regular fa-clock"></i> {{ $agoHuman }}
                    </span>
                </div>
            </button>
        @endforeach
    </div>

    {{-- Atajos de teclado de la lista (mockup .kbd-hints) --}}
    <div class="docs-kbd-hints">
        <span class="kh"><kbd>↑</kbd><kbd>↓</kbd> navegar</span>
        <span class="kh"><kbd>↵</kbd> abrir</span>
        <span class="kh"><kbd>Esc</kbd> cerrar</span>
        <span class="kh"><kbd>/</kbd> buscar</span>
    </div>
@endif
