@extends('layouts.theme')

@section('title', 'Ver Documento')

@section('content')

    @include('core::components.card', ['title' => 'Ver Documento'])

    <div class="row">
        <div class="col-lg-8">
            <!-- Order Details (Permission-Controlled) -->
            @if(auth()->user()->canDocument('order-details'))
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Detalle de la orden</h5>
                    <p class="small mb-0 text-muted">Información de la orden y fechas</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Orden ID</label>
                            <p class="mb-0">{{ $document->order_id ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Referencia</label>
                            <p class="mb-0">{{ $document->order_reference ?? '-' }}</p>
                        </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Tipo de documento</label>
                            <p class="mb-0">
                                @if($document->documentType)
                                    <span class="badge bg-primary">{{ $document->documentType->label }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Fecha de orden</label>
                            <p class="mb-0">{{ $document->order_date ? \Carbon\Carbon::parse($document->order_date)->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Fecha de confirmación</label>
                            <p class="mb-0">{{ $document->confirmed_at ? \Carbon\Carbon::parse($document->confirmed_at)->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Fecha de creación</label>
                            <p class="mb-0">{{ $document->created_at ? $document->created_at->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Customer Information (Permission-Controlled) -->
            @if(auth()->user()->canDocument('customer-information'))
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información del cliente</h5>
                    <p class="small mb-0 text-muted">Datos de contacto del cliente</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Nombres</label>
                            <p class="mb-0">{{ $document->customer_firstname ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Apellidos</label>
                            <p class="mb-0">{{ $document->customer_lastname ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">DNI/NIE/CIF</label>
                            <p class="mb-0">{{ $document->customer_dni ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Correo electrónico</label>
                            <p class="mb-0">
                                @if($document->customer_email)
                                    <a href="mailto:{{ $document->customer_email }}">{{ $document->customer_email }}</a>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Teléfono</label>
                            <p class="mb-0">{{ $document->customer_cellphone ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Empresa</label>
                            <p class="mb-0">{{ $document->customer_company ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($products->count() && auth()->user()->canDocument('products-list'))
                <!-- Products List (Permission-Controlled) -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Productos</h5>
                        <p class="small mb-0 text-muted">Productos relacionados con la orden</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item->product_name }}</div>
                                                @if($item->product_reference)
                                                    <small class="text-muted">Ref: {{ $item->product_reference }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ $item->quantity }} ud</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Uploaded Document -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Documentos cargados</h5>
                    <p class="small mb-0 text-muted">Archivos subidos por el cliente</p>
                </div>
                @if($document->media->count())
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Tamaño</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($document->media as $media)
                                        <tr>
                                            <td>
                                                <i class="fas fa-file me-2 text-muted"></i>
                                                {{ $media->file_name }}
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ $media->getCustomProperty('document_type', 'documento') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($media->size / 1024, 2) }} KB
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ parse_url($media->getUrl(), PHP_URL_PATH) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($document->media->count() > 1)
                            <div class="border-top p-3">
                                <a href="{{ route('documents.summary', $document->uid) }}" target="_blank" class="btn btn-primary w-100">
                                    <i class="fas fa-file-archive me-2"></i> Ver todos los documentos
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="card-body">
                        <p class="text-muted mb-0 text-center">No hay documentos cargados</p>
                    </div>
                @endif
            </div>

            <!-- Document Notes (Permission-Controlled) -->
            @if(auth()->user()->canDocument('document-notes'))
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Notas del documento</h5>
                    <p class="small mb-0 text-muted">Anotaciones y comentarios registrados</p>
                </div>
                @if($document->notes && $document->notes->count() > 0)
                    <div class="card-body p-0">
                        <div class="notes-list-readonly" style="max-height: 400px; overflow-y: auto;">
                            @foreach($document->notes->sortByDesc('created_at') as $note)
                                <div class="border-bottom p-3">
                                    <div class="d-flex align-items-start gap-2 mb-2">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <span class="fw-semibold small text-muted">
                                                    {{ strtoupper(substr($note->author->firstname ?? 'S', 0, 1) . substr($note->author->lastname ?? '', 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold small">
                                                    @if($note->author)
                                                        {{ $note->author->firstname }} {{ substr($note->author->lastname ?? '', 0, 1) }}.
                                                    @else
                                                        Sistema
                                                    @endif
                                                </span>
                                                <small class="text-muted">{{ $note->created_at->format('d/m/Y H:i') }}</small>
                                            </div>
                                            <p class="mb-0 small text-dark mt-1">{{ $note->content }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="card-body text-center">
                        <i class="fas fa-sticky-note fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No hay notas registradas</p>
                    </div>
                @endif
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Document Status -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Estado del documento</h5>
                    <p class="small mb-0 text-muted">Configuración actual</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Tipo de documento</label>
                        <p class="mb-0">
                            @if($document->documentType)
                                <span class="badge bg-primary">{{ $document->documentType->label }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Estado</label>
                        <p class="mb-0">{{ $document->status->label ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Origen (canal)</label>
                        <p class="mb-0">{{ $document->source->label ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Método de carga</label>
                        <p class="mb-0">{{ $document->documentLoad->label ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Tipo de sincronización</label>
                        <p class="mb-0">{{ $document->sync->label ?? '-' }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold text-muted small">Tipo de subida</label>
                        <p class="mb-0">{{ $document->uploadType->label ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Acciones</h5>
                </div>
                <div class="card-body">
                    @if(auth()->user()->canDocument('route-manage-documents'))
                        <a href="{{ route('documents.manage', $document->uid) }}" class="btn btn-primary w-100 mb-2">
                            Gestionar
                        </a>
                    @endif
                    @if($document->mails()->count() > 0 && auth()->user()->canDocument('route-mails-documents'))
                        <a href="{{ route('documents.emails', $document->uid) }}" class="btn btn-outline-info w-100 mb-2">
                           Ver emails enviados
                           <span class="badge bg-black ms-1">{{ $document->mails()->count() }}</span>
                        </a>
                    @endif
                    @if(auth()->user()->canDocument('route-all-documents'))
                        <a href="{{ route('documents.index') }}" class="btn btn-secondary w-100">
                            Volver a documentos
                        </a>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Metadatos</h5>
                    <p class="small mb-0 text-muted">Información técnica</p>
                </div>
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">UID</span>
                        <span class="small font-monospace">{{ $document->uid }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">ID Idioma</span>
                        <span class="small">{{ $document->lang_id ?? '-' }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">ID Cliente</span>
                        <span class="small">{{ $document->customer_id ?? '-' }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">ID Carrito</span>
                        <span class="small">{{ $document->cart_id ?? '-' }}</span>
                    </div>
                    <div class="mb-0 d-flex justify-content-between">
                        <span class="text-muted small">Actualizado</span>
                        <span class="small">{{ $document->updated_at ? $document->updated_at->format('d/m/Y H:i') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
