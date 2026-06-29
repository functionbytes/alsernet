{{--
    Document module · Modal Ver documento detalle (2 columnas: main + side).

    Uso:
        @include('helpdeskdocument::modals.doc-view', [
            'id'         => 'docView_xyz',
            'document'   => $document,       // Model | array (con uuid, created_at, updated_at)
            'customer'   => $customer,
            'products'   => $products,       // (opcional) array
            'uploaded'   => $uploaded,       // (opcional) documentos cargados
            'notes'      => $notes,          // (opcional) array de notas
            'sent_count' => 7,               // (opcional) envios totales
        ])
--}}
@php
    $id          = $id ?? 'docView';
    $document    = $document ?? null;
    $customer    = $customer ?? null;
    $products    = $products ?? [];
    $uploaded    = $uploaded ?? [];
    $notes       = $notes ?? [];
    $sentCount   = $sent_count ?? 0;

    $cFirst      = is_array($customer) ? ($customer['firstname'] ?? '—') : ($customer?->firstname ?? '—');
    $cLast       = is_array($customer) ? ($customer['lastname'] ?? '')   : ($customer?->lastname ?? '');
    $cDoc        = is_array($customer) ? ($customer['identifier'] ?? null) : ($customer?->identifier ?? null);
    $cEmail      = is_array($customer) ? ($customer['email'] ?? null)    : ($customer?->email ?? null);
    $cPhone      = is_array($customer) ? ($customer['phone'] ?? null)    : ($customer?->phone ?? null);
    $cCompany    = is_array($customer) ? ($customer['company'] ?? null)  : ($customer?->company ?? null);

    $docUuid     = is_array($document) ? ($document['uuid'] ?? null)     : ($document?->uuid ?? null);
    $docCreated  = is_array($document) ? ($document['created_human'] ?? null) : ($document?->created_at?->format('d/m/Y H:i'));
    $docUpdated  = is_array($document) ? ($document['updated_human'] ?? null) : ($document?->updated_at?->format('d/m/Y H:i'));
    $docTypeLb   = is_array($document) ? ($document['type_label'] ?? 'Documento') : ($document?->type_label ?? 'Documento');
    $docStatusLb = is_array($document) ? ($document['status_label'] ?? 'Subida') : ($document?->status_label ?? 'Subida');
    $docOrigin   = is_array($document) ? ($document['origin'] ?? '—')    : ($document?->origin ?? '—');
    $docSystem   = is_array($document) ? ($document['system'] ?? '—')    : ($document?->system ?? '—');
    $docDocType  = is_array($document) ? ($document['doc_type'] ?? '—')  : ($document?->doc_type ?? '—');
    $docUploadBy = is_array($document) ? ($document['uploaded_by'] ?? '—') : ($document?->uploaded_by ?? '—');
@endphp

@include('helpdeskdocument::modals._assets')

<div class="docs-backdrop" data-docs-modal-backdrop-for="{{ $id }}"></div>
<div id="{{ $id }}" class="docs-modal docs-modal-lg" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="docs-head">
        <div class="docs-head-icon"><i class="fa-regular fa-file-lines"></i></div>
        <div class="docs-head-text">
            <div class="docs-head-label">Dashboard · Documentos</div>
            <div class="docs-head-title" id="{{ $id }}-title">
                Ver documento
                <span class="docs-breadcrumb">
                    Dashboard <i class="fa-solid fa-chevron-right"></i> Ver documento
                </span>
            </div>
        </div>
        <button type="button" class="docs-close" aria-label="Cerrar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="docs-vd-grid">
        {{-- Columna principal --}}
        <div class="docs-vd-main">
            <div class="docs-vd-card">
                <div class="h">
                    <span class="t">Información del cliente</span>
                    <span class="s">Datos de contacto del cliente</span>
                </div>
                <div class="docs-vd-kv">
                    <div><div class="k">Nombres</div><div class="v">{{ $cFirst }}</div></div>
                    <div><div class="k">Apellidos</div><div class="v">{{ $cLast }}</div></div>
                    <div><div class="k">DNI/NIE/CIF</div><div class="v mono">{{ $cDoc ?? '—' }}</div></div>
                    <div><div class="k">Correo electrónico</div><div class="v mono">{{ $cEmail ?? '—' }}</div></div>
                    <div><div class="k">Teléfono</div><div class="v mono">{{ $cPhone ?? '—' }}</div></div>
                    <div><div class="k">Empresa</div><div class="v {{ $cCompany ? '' : 'muted' }}">{{ $cCompany ?? '—' }}</div></div>
                </div>
            </div>

            <div class="docs-vd-card">
                <div class="h">
                    <span class="t">Productos</span>
                    <span class="s">Productos asociados a la orden</span>
                </div>
                @if(count($products))
                    @foreach($products as $product)
                        @php
                            $pName = is_array($product) ? ($product['name'] ?? '—')  : ($product->name ?? '—');
                            $pSku  = is_array($product) ? ($product['sku'] ?? '—')   : ($product->sku ?? '—');
                            $pQty  = is_array($product) ? ($product['qty'] ?? '1ud') : ($product->qty ?? '1ud');
                        @endphp
                        <div class="docs-vd-prod-row">
                            <div>
                                <div class="nm">{{ $pName }}</div>
                                <div class="sku">Ref: {{ $pSku }}</div>
                            </div>
                            <div class="qty">{{ $pQty }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="docs-vd-empty">
                        <i class="fa-regular fa-box"></i>
                        Sin productos asociados
                    </div>
                @endif
            </div>

            <div class="docs-vd-card">
                <div class="h">
                    <span class="t">Documentos cargados</span>
                    <span class="s">Archivos enviados por el cliente</span>
                </div>
                @if(count($uploaded))
                    @foreach($uploaded as $upload)
                        @php
                            $uName   = is_array($upload) ? ($upload['name'] ?? '—')        : ($upload->name ?? '—');
                            $uStatus = is_array($upload) ? ($upload['status'] ?? 'pending'): ($upload->status ?? 'pending');
                        @endphp
                        <div class="docs-action-row">
                            <div class="body">
                                <div class="nm">{{ $uName }}</div>
                            </div>
                            <span class="docs-status {{ $uStatus }}">{{ ucfirst($uStatus) }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="docs-vd-empty">
                        <i class="fa-regular fa-folder-open"></i>
                        No hay documentos cargados
                    </div>
                @endif
            </div>

            <div class="docs-vd-card">
                <div class="h">
                    <span class="t">Notas del documento</span>
                    <span class="s">Anotaciones y comentarios</span>
                </div>
                @if(count($notes))
                    @foreach($notes as $note)
                        <div class="docs-action-row">
                            <div class="body">
                                <div class="nm">{{ $note['author'] ?? 'Sistema' }}</div>
                                <div class="ds">{{ $note['text'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="docs-vd-empty">
                        <i class="fa-regular fa-note-sticky"></i>
                        No hay notas registradas
                    </div>
                @endif
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="docs-vd-side">
            <div class="docs-vd-card">
                <div class="h">
                    <span class="t">Estado del documento</span>
                    <span class="s">Configuración actual</span>
                </div>
                <div class="docs-vd-status-list">
                    <div class="docs-vd-status-row"><span class="k">Tipo</span><span class="v">{{ $docTypeLb }}</span></div>
                    <div class="docs-vd-status-row"><span class="k">Estado</span><span class="v">{{ $docStatusLb }}</span></div>
                    <div class="docs-vd-status-row"><span class="k">Origen</span><span class="v">{{ $docOrigin }}</span></div>
                    <div class="docs-vd-status-row"><span class="k">Sistema</span><span class="v">{{ $docSystem }}</span></div>
                    <div class="docs-vd-status-row"><span class="k">Tipo doc.</span><span class="v">{{ $docDocType }}</span></div>
                    <div class="docs-vd-status-row"><span class="k">Cargado por</span><span class="v">{{ $docUploadBy }}</span></div>
                </div>
            </div>

            <div class="docs-vd-card">
                <div class="h"><span class="t">Acciones</span></div>
                <button type="button" class="btn btn-primary w-100" data-docs-manage>Gestionar</button>
                <button type="button" class="btn btn-outline-secondary w-100" data-docs-sent>
                    Ver envíos enviados
                    @if($sentCount)<span class="badge bg-light text-dark ms-1">{{ $sentCount }}</span>@endif
                </button>
                <button type="button" class="btn btn-outline-secondary w-100" data-docs-modal-close>
                    Volver a documentos
                </button>
            </div>

            <div class="docs-vd-card">
                <div class="h">
                    <span class="t">Metadatos</span>
                    <span class="s">Información técnica</span>
                </div>
                <div class="docs-vd-meta-block">
                    @if($docUuid)
                        <div class="item">
                            <span class="k">UUID</span>
                            <span class="v">{{ $docUuid }}</span>
                        </div>
                    @endif
                    @if($docCreated)
                        <div class="item">
                            <span class="k">Creado</span>
                            <span class="v">{{ $docCreated }}</span>
                        </div>
                    @endif
                    @if($docUpdated)
                        <div class="item">
                            <span class="k">Actualizado</span>
                            <span class="v">{{ $docUpdated }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
