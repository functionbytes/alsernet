@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Modales del modulo Document — preview'])

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Modales reutilizables del modulo Document</h5>
            <p class="text-muted small mb-3">
                Los 4 modales abajo se pueden incluir en otras vistas con
                <code>@@include('helpdeskdocument::modals.docs-viewer', [...])</code>.
                Click en cada boton para abrir el modal correspondiente.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary"
                        data-docs-modal-trigger="docsViewerDemo">
                    <i class="fa-regular fa-folder-open me-1"></i>
                    Visualizar documentación
                </button>
                <button type="button" class="btn btn-primary"
                        data-docs-modal-trigger="docManageDemo">
                    <i class="fa-regular fa-folder me-1"></i>
                    Gestionar documento
                </button>
                <button type="button" class="btn btn-primary"
                        data-docs-modal-trigger="docViewDemo">
                    <i class="fa-regular fa-file-lines me-1"></i>
                    Ver documento detalle
                </button>
                <button type="button" class="btn btn-primary"
                        data-docs-modal-trigger="docFromChatDemo">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i>
                    Cargar desde galería del chat
                </button>
            </div>
        </div>
    </div>

    {{-- ── Datos demo ─────────────────────────────────────────────────── --}}
    @php
        $demoCustomer = [
            'firstname' => 'JOSE',
            'lastname' => 'RODRIGUEZ LINDES',
            'name' => 'JOSE RODRIGUEZ LINDES',
            'identifier' => '12345678X',
            'email' => 'prlindes@outlook.com',
            'phone' => '+34 612 345 678',
            'company' => null,
        ];

        $demoOrder = [
            'reference' => '829575',
            'status' => 'approved',
            'status_label' => 'Aprobado',
        ];

        $demoDocuments = [
            [
                'id' => 'doc-1', 'name' => 'DNI frontal', 'mime' => 'image/jpeg',
                'type_label' => 'JPG', 'size_human' => '2.4 MB',
                'uploaded_human' => 'hace 3d', 'status' => 'approved',
            ],
            [
                'id' => 'doc-2', 'name' => 'DNI trasera', 'mime' => 'image/jpeg',
                'type_label' => 'JPG', 'size_human' => '2.1 MB',
                'uploaded_human' => 'hace 3d', 'status' => 'approved',
            ],
            [
                'id' => 'doc-3', 'name' => 'Factura proforma', 'mime' => 'application/pdf',
                'type_label' => 'PDF', 'size_human' => '187 KB',
                'uploaded_human' => 'hace 2d', 'status' => 'approved',
            ],
            [
                'id' => 'doc-4', 'name' => 'Contrato firmado', 'mime' => 'application/pdf',
                'type_label' => 'PDF', 'size_human' => '—',
                'uploaded_human' => 'no subido', 'status' => 'pending',
            ],
        ];

        $demoManageDoc = [
            'id' => 'doc-1', 'name' => 'DNI frontal',
            'status' => 'pending', 'status_label' => 'Pendiente de aprobación',
        ];

        $demoHistory = [
            ['ts' => '17/05 21:32', 'actor' => 'cliente', 'description' => 'Documento subido'],
            ['ts' => '17/05 21:35', 'actor' => 'Sistema', 'description' => 'Solicitud enviada'],
            ['ts' => '18/05 09:14', 'actor' => 'Maria Lopez', 'description' => 'Vista'],
        ];

        $demoViewDoc = [
            'uuid' => 'a4d61851-7b54-4ee2-a45a-1f4a9a2d04b3',
            'type_label' => 'Solicitud Inicial',
            'status_label' => 'Subida',
            'origin' => 'Frontend',
            'system' => 'Master',
            'doc_type' => 'DNI',
            'uploaded_by' => 'Automatic',
            'created_human' => '17/05/2026 21:32',
            'updated_human' => '18/05/2026 09:14',
        ];

        $demoViewCustomer = array_merge($demoCustomer, [
            'firstname' => 'CANDIDO',
            'lastname' => 'JIMENEZ VERGARA',
            'email' => 'canjimenez@gmail.com',
        ]);

        $demoProducts = [
            ['name' => 'CARABINA GAMO BIG CAT 1000 BARRICADA CALIBRE 5.5', 'sku' => 'GCB1000-5.5', 'qty' => '1ud'],
            ['name' => 'BALINES IMPACT DIAMOND TS CALIBRE 5.5', 'sku' => 'BID-TS-55', 'qty' => '1ud'],
        ];

        $demoChatFiles = [
            ['id' => 'f1', 'name' => 'dni-front.jpg', 'type' => 'image', 'selected' => true],
            ['id' => 'f2', 'name' => 'dni-back.jpg',  'type' => 'image', 'selected' => true],
            ['id' => 'f3', 'name' => 'factura.pdf',   'type' => 'pdf',   'selected' => false],
            ['id' => 'f4', 'name' => 'recibo.png',    'type' => 'image', 'selected' => false],
            ['id' => 'f5', 'name' => 'screen.png',    'type' => 'image', 'selected' => false],
            ['id' => 'f6', 'name' => 'notas.txt',     'type' => 'text',  'selected' => false],
            ['id' => 'f7', 'name' => 'video.webm',    'type' => 'video', 'selected' => false],
            ['id' => 'f8', 'name' => 'extra.pdf',     'type' => 'pdf',   'selected' => false],
        ];
    @endphp

    @include('helpdeskdocument::modals.docs-viewer', [
        'id' => 'docsViewerDemo',
        'customer' => $demoCustomer,
        'order' => $demoOrder,
        'documents' => $demoDocuments,
    ])

    @include('helpdeskdocument::modals.doc-manage', [
        'id' => 'docManageDemo',
        'document' => $demoManageDoc,
        'customer' => $demoCustomer,
        'order' => $demoOrder,
        'history' => $demoHistory,
    ])

    @include('helpdeskdocument::modals.doc-view', [
        'id' => 'docViewDemo',
        'document' => $demoViewDoc,
        'customer' => $demoViewCustomer,
        'products' => $demoProducts,
        'uploaded' => [],
        'notes' => [],
        'sent_count' => 7,
    ])

    @include('helpdeskdocument::modals.doc-from-chat', [
        'id' => 'docFromChatDemo',
        'order_ref' => '829575',
        'files' => $demoChatFiles,
    ])

@endsection
