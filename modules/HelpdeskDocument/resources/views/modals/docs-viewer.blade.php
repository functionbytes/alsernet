{{--
    Document module · Modal Visualizar documentación del cliente.

    Uso:
        @include('helpdeskdocument::modals.docs-viewer', [
            'id'         => 'docsViewerCarlos',
            'customer'   => $customer,        // App\Models\Customer | array
            'order'      => $order,           // App\Models\Order | array | null
            'documents'  => $documents,       // Collection | array
            'counts'     => [                 // (opcional) totales por estado
                'all' => 4,
                'approved' => 3,
                'pending' => 1,
            ],
        ])

    Cada $document debe exponer al menos:
        - name (DNI frontal, Factura...)
        - mime ('image/jpeg' | 'application/pdf' | ...)
        - size_human ('2.4 MB')
        - uploaded_human ('hace 3d')
        - status ('approved' | 'pending' | 'rejected')
        - type_label ('JPG', 'PDF')
        - id (para abrir el modal de gestion)

    Dispara los modales hijos por nombre:
        - data-docs-modal-trigger="docManage_{id}"
--}}
@php
    $id          = $id ?? 'docsViewer';
    $customer    = $customer ?? null;
    $order       = $order ?? null;
    $documents   = $documents ?? [];
    $counts      = $counts ?? null;
    $manageId    = $manage_id ?? null;

    $orderRef    = is_array($order) ? ($order['reference'] ?? null) : ($order?->reference ?? null);
    $customerNm  = is_array($customer) ? ($customer['name'] ?? '—') : ($customer?->name ?? '—');
    $statusLabel = is_array($order) ? ($order['status_label'] ?? null) : ($order?->status_label ?? null);
    $statusCode  = is_array($order) ? ($order['status'] ?? null) : ($order?->status ?? null);

    $total       = $counts['all']      ?? count($documents);
    $approved    = $counts['approved'] ?? collect($documents)->where('status', 'approved')->count();
    $pending     = $counts['pending']  ?? collect($documents)->where('status', 'pending')->count();
@endphp

@include('helpdeskdocument::modals._assets')

<div class="docs-backdrop" data-docs-modal-backdrop-for="{{ $id }}"></div>
<div id="{{ $id }}" class="docs-modal docs-modal-md" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="docs-head">
        <div class="docs-head-icon"><i class="fa-regular fa-folder-open"></i></div>
        <div class="docs-head-text">
            <div class="docs-head-label">Documentos @if($orderRef) · Orden #{{ $orderRef }} @endif</div>
            <div class="docs-head-title" id="{{ $id }}-title">
                Documentación del cliente
                <span class="docs-chip">{{ $total }}</span>
            </div>
        </div>
        <button type="button" class="docs-close" aria-label="Cerrar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="docs-body">
        <div class="docs-info-table">
            <div class="lbl">Cliente</div>
            <div class="val">{{ $customerNm }}</div>
            @if($orderRef)
                <div class="lbl">Orden</div>
                <div class="val mono">#{{ $orderRef }}</div>
            @endif
            @if($statusLabel)
                <div class="lbl">Estado</div>
                <div class="val">
                    <span class="docs-status {{ $statusCode === 'approved' ? 'approved' : ($statusCode === 'rejected' ? 'rejected' : 'pending') }}">
                        <i class="fa-solid fa-check"></i> {{ $statusLabel }}
                    </span>
                </div>
            @endif
        </div>

        <div class="docs-filter-row">
            <button type="button" class="docs-pill on" data-filter="all">
                Todos <span class="c">{{ $total }}</span>
            </button>
            <button type="button" class="docs-pill" data-filter="approved">
                Aprobados <span class="c">{{ $approved }}</span>
            </button>
            <button type="button" class="docs-pill" data-filter="pending">
                Pendientes <span class="c">{{ $pending }}</span>
            </button>
        </div>

        <div class="docs-gallery">
            @forelse($documents as $doc)
                @php
                    $dName      = is_array($doc) ? ($doc['name'] ?? '—')             : ($doc->name ?? '—');
                    $dStatus    = is_array($doc) ? ($doc['status'] ?? 'pending')      : ($doc->status ?? 'pending');
                    $dMime      = is_array($doc) ? ($doc['mime'] ?? null)             : ($doc->mime ?? null);
                    $dType      = is_array($doc) ? ($doc['type_label'] ?? 'FILE')     : ($doc->type_label ?? 'FILE');
                    $dSize      = is_array($doc) ? ($doc['size_human'] ?? '—')        : ($doc->size_human ?? '—');
                    $dUploaded  = is_array($doc) ? ($doc['uploaded_human'] ?? '—')    : ($doc->uploaded_human ?? '—');
                    $dId        = is_array($doc) ? ($doc['id'] ?? null)               : ($doc->id ?? null);
                    $dUrl       = is_array($doc) ? ($doc['url'] ?? null)              : ($doc->url ?? null);
                    $dIsImage   = $dMime && \Illuminate\Support\Str::startsWith($dMime, 'image/');
                    $icon       = config('document.mime_icons.'.$dMime, 'fa-regular fa-file');
                    $statusText = $dStatus === 'approved' ? 'Aprobado' : ($dStatus === 'rejected' ? 'Rechazado' : 'Pendiente');
                @endphp
                <a class="docs-card" data-status="{{ $dStatus }}"
                   href="{{ $dUrl ?: '#' }}" @if($dUrl) target="_blank" rel="noopener" @endif>
                    <div class="thumb">
                        @if($dIsImage && $dUrl)
                            <img class="docs-card-thumb" src="{{ $dUrl }}" alt="{{ $dName }}" loading="lazy">
                        @else
                            <i class="{{ $icon }}"></i>
                        @endif
                        <span class="badge-tp">{{ strtoupper($dType) }}</span>
                        <span class="docs-status {{ $dStatus }}">{{ $statusText }}</span>
                    </div>
                    <div class="info">
                        <span class="nm">{{ $dName }}</span>
                        <span class="meta">{{ $dSize }} · {{ $dUploaded }}</span>
                    </div>
                </a>
            @empty
                <div class="docs-vd-empty docs-gallery-empty">
                    <i class="fa-regular fa-folder-open"></i>
                    Sin documentos cargados
                </div>
            @endforelse
        </div>
    </div>

    <div class="docs-foot">
        <button type="button" class="btn btn-primary"
                @if($manageId) data-docs-modal-trigger="{{ $manageId }}" @endif>
            <i class="fa-regular fa-folder me-1"></i> Gestionar documento
        </button>
        <button type="button" class="btn btn-outline-secondary" data-docs-download-zip>
            <i class="fa-solid fa-file-zipper me-1"></i> Descargar todo (.zip)
        </button>
        <button type="button" class="btn btn-outline-secondary" data-docs-modal-close>
            Cerrar
        </button>
    </div>
</div>
