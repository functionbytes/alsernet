@extends('layouts.theme')

@section('title', 'Detalle de producto — ' . $product->name)

@push('css')
    <style>
        .btn-validate-green { background-color: #7cb01a; border-color: #7cb01a; border-radius: 8px; }
        .btn-validate-green:hover, .btn-validate-green:focus { background-color: #6a9817; border-color: #6a9817; }
        .btn-action-black { background-color: #111; border-color: #111; border-radius: 8px; }
        .btn-action-black:hover, .btn-action-black:focus { background-color: #2a2a2a; border-color: #2a2a2a; }
        .pb-side-icon { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; background:rgba(0,0,0,.06); font-size:.85rem; flex-shrink:0; }
        .pb-section-title { font-size:.85rem; font-weight:700; margin:0; }
        .pb-section-sub   { font-size:.75rem; color:#8a99b5; margin:0; }
    </style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Detalle de producto'])
@endsection

@section('content')

    <div class="widget-content">

        @include('core::components.alerts')

        <div class="row g-3">

            {{-- LEFT: contenido principal --}}
            <div class="col-12 col-lg-8">

                {{-- Info del producto --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">{{ $product->name }}</h5>
                                <code class="small text-muted">{{ $product->code }}</code>
                            </div>
                            <div class="d-flex gap-2">
                                @if($product->available)
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                @else
                                    <span class="badge bg-light text-dark">Inactivo</span>
                                @endif
                                @if($product->web_published)
                                    <span class="badge bg-info-subtle text-info">Web</span>
                                @endif
                                @if($product->is_default)
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="fas fa-star me-1"></i> Por defecto
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <p class="text-muted small mb-1">Proveedor</p>
                                <p class="fw-semibold mb-0">{{ $product->supplier?->label ?? '—' }}</p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted small mb-1">Categoría</p>
                                <p class="fw-semibold mb-0">{{ $product->category?->name ?? '—' }}</p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted small mb-1">ERP ID</p>
                                <p class="fw-semibold mb-0"><code>{{ $product->erp_id ?? '—' }}</code></p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted small mb-1">Última sincronización</p>
                                <p class="fw-semibold mb-0">
                                    {{ $product->last_sync_at ? $product->last_sync_at->format('d/m/Y H:i') : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contenido IA asociado --}}
                @if($latestContent)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0 fw-bold">Contenido generado por IA</h6>
                            <p class="text-muted small mb-0 mt-1">Última versión generada para este producto</p>
                        </div>
                        @if($latestContent->generated_name)
                            <div class="card-body border-bottom">
                                <label class="form-label fw-semibold small text-muted">Nombre generado</label>
                                <input type="text" class="form-control" value="{{ $latestContent->generated_name }}" disabled>
                            </div>
                        @endif
                        @if($latestContent->short_description)
                            <div class="card-body border-bottom">
                                <label class="form-label fw-semibold small text-muted">Descripción corta</label>
                                <textarea class="form-control" rows="3" disabled>{{ $latestContent->short_description }}</textarea>
                            </div>
                        @endif
                        @if($latestContent->long_description)
                            <div class="card-body">
                                <label class="form-label fw-semibold small text-muted">Descripción larga</label>
                                <div class="border rounded p-3 bg-light" style="max-height:300px;overflow-y:auto;font-size:.875rem">
                                    {!! $latestContent->long_description !!}
                                </div>
                            </div>
                        @endif
                        @if(!$latestContent->generated_name && !$latestContent->short_description && !$latestContent->long_description)
                            <div class="card-body text-center py-4 text-muted small">
                                <i class="fas fa-clock me-1"></i> Contenido pendiente de generación
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Artículos / variantes --}}
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Artículos</h5>
                                <p class="small mb-0 text-muted">Variantes / SKUs sincronizados desde el ERP</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">
                                {{ $product->attributes->count() }} artículo(s)
                            </span>
                        </div>
                    </div>

                    @if($product->attributes->count())
                        <div class="card-body border-bottom py-3">
                            <input type="search" id="attr-search" class="form-control"
                                   placeholder="Filtrar por código, referencia o nombre...">
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="attributes-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Referencia</th>
                                            <th>EAN</th>
                                            <th>Nombre</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Web</th>
                                            <th>Últ. sync</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->attributes as $attr)
                                            <tr class="attr-row">
                                                <td><code class="small attr-code">{{ $attr->code ?? '—' }}</code></td>
                                                <td><code class="small attr-reference">{{ $attr->reference ?? '—' }}</code></td>
                                                <td><code class="small">{{ $attr->ean13 ?? $attr->upc ?? '—' }}</code></td>
                                                <td class="attr-name">{{ $attr->name ?? '—' }}</td>
                                                <td class="text-center">
                                                    @if($attr->available)
                                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                                    @else
                                                        <span class="badge bg-light text-dark">Inactivo</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($attr->web_published)
                                                        <span class="badge bg-info-subtle text-info">Sí</span>
                                                    @else
                                                        <span class="text-muted small">No</span>
                                                    @endif
                                                </td>
                                                <td class="small text-muted">
                                                    {{ $attr->last_sync_at?->format('d/m/Y H:i') ?? '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-muted small" id="attr-count-label">
                            Mostrando {{ $product->attributes->count() }} artículo(s)
                        </div>
                    @else
                        <div class="card-body text-center py-5 text-muted">
                            <i class="fas fa-cubes fa-2x mb-3 d-block"></i>
                            <h6 class="mb-1">Sin artículos registrados</h6>
                            <p class="small mb-0">Este producto aún no tiene artículos sincronizados desde el ERP</p>
                        </div>
                    @endif
                </div>

            </div>

            {{-- RIGHT: sidebar --}}
            <div class="col-12 col-lg-4">

                {{-- Acciones --}}
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-sliders"></i></span>
                        <div>
                            <h6 class="pb-section-title">Acciones</h6>
                            <p class="pb-section-sub">Validar, rechazar o ir al chat del producto</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <label class="form-label fw-semibold small">Descripción / Comentario</label>
                        <textarea class="form-control" id="action-description" rows="3" placeholder="Motivo de rechazo u observaciones (opcional)..."></textarea>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex flex-column gap-2">
                            @if(!$product->has_approved_content)
                                <a href="{{ route('settings.suppliers.products.chat', ['uid' => $product->uid]) }}"
                                   class="btn btn-action-black w-100 fw-semibold text-white">
                                    Ir al chat del producto
                                </a>
                            @endif
                            @if($canValidate)
                                <button type="button"
                                        class="btn btn-validate-green w-100 fw-semibold text-white"
                                        id="btn-validate">
                                    Aprobar
                                </button>
                            @endif
                            @if($canReject)
                                <button type="button"
                                        class="btn btn-outline-secondary w-100 fw-semibold"
                                        id="btn-reject">
                                    Rechazar
                                </button>
                            @endif
                            @if($canPublish && $latestContent)
                                <a href="{{ route('settings.suppliers.content.show', $latestContent->uid) }}"
                                   class="btn btn-primary w-100 fw-semibold">
                                    Ver contenido aprobado
                                </a>
                            @endif
                            @if($latestContent && !$canPublish)
                                <a href="{{ route('settings.suppliers.content.show', $latestContent->uid) }}"
                                   class="btn btn-outline-secondary w-100 fw-semibold">
                                    Ver detalle completo
                                </a>
                            @endif
                            @if(!$latestContent)
                                <p class="text-muted small text-center mb-0">Sin contenido IA generado aún</p>
                            @endif
                            <hr class="my-1">
                            @can('suppliers.view.products')
                                <a href="{{ route('settings.suppliers.products.edit', $product->id) }}"
                                   class="btn btn-outline-primary w-100 fw-semibold">
                                    Editar producto
                                </a>
                            @endcan
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary w-100 fw-semibold">
                                Volver
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Info del producto (lateral) --}}
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-box"></i></span>
                        <div>
                            <h6 class="pb-section-title">Información</h6>
                            <p class="pb-section-sub">Datos del producto en el ERP</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Proveedor</span>
                                <span class="small fw-semibold text-end">{{ $product->supplier?->label ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Categoría</span>
                                <span class="small fw-semibold text-end">{{ $product->category?->name ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Código</span>
                                <span class="small fw-semibold font-monospace text-end">{{ $product->code ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">ERP ID</span>
                                <span class="small fw-semibold font-monospace text-end">{{ $product->erp_id ?? '—' }}</span>
                            </li>
                            @if($latestContent)
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Estado contenido</span>
                                <span class="badge {{ $latestContent->contentStatus?->badge_class ?? 'bg-secondary' }}">
                                    {{ $latestContent->contentStatus?->label ?? $latestContent->status }}
                                </span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Prompt</span>
                                <span class="small fw-semibold text-end">{{ $latestContent->prompt?->label ?? '—' }}</span>
                            </li>
                            @endif
                            <li class="d-flex justify-content-between py-2 gap-2">
                                <span class="text-muted small">Últ. sync</span>
                                <span class="small fw-semibold text-end">
                                    {{ $product->last_sync_at?->format('d/m/Y H:i') ?? '—' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal confirmar aprobar --}}
    @if($canValidate && $latestContent)
    <div class="modal fade" id="modal-validate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aprobar contenido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Confirmas que quieres aprobar y sincronizar este contenido con el ERP?</p>
                    <label class="form-label fw-semibold" for="modal-publicar">Publicar en tienda</label>
                    <select class="form-select" id="modal-publicar">
                        <option value="1" selected>1 — Publicar</option>
                        <option value="0">0 — No publicar</option>
                    </select>
                </div>
                <div class="modal-footer d-flex flex-column gap-1">
                    <button type="button" class="btn btn-validate-green fw-semibold text-white w-100" id="btn-validate-confirm">
                        Validar y sincronizar
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal rechazar --}}
    <div class="modal fade" id="modal-reject" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rechazar contenido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Motivo del rechazo <span class="text-danger">*</span></label>
                    <textarea id="reject-reason-input" class="form-control" rows="3" placeholder="Escribe el motivo..."></textarea>
                    <div class="invalid-feedback" id="reject-reason-error">Debes escribir un motivo.</div>
                </div>
                <div class="modal-footer d-flex flex-column gap-1">
                    <button id="reject-confirm-btn" type="button" class="btn btn-danger w-100 mb-1">Rechazar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Filtro artículos
    const totalCount = {{ $product->attributes->count() }};
    $('#attr-search').on('input', function () {
        const term = $(this).val().toLowerCase().trim();
        let visible = 0;
        $('#attributes-table .attr-row').each(function () {
            const matches = !term ||
                $(this).find('.attr-code').text().toLowerCase().includes(term) ||
                $(this).find('.attr-reference').text().toLowerCase().includes(term) ||
                $(this).find('.attr-name').text().toLowerCase().includes(term);
            $(this).toggle(matches);
            if (matches) visible++;
        });
        $('#attr-count-label').text(term ? `Mostrando ${visible} de ${totalCount} artículo(s)` : `Mostrando ${totalCount} artículo(s)`);
    });

    @if($canValidate && $latestContent)
    const actionUrl    = '{{ route("settings.suppliers.content.action", $latestContent->uid) }}';
    const validateModal = new bootstrap.Modal(document.getElementById('modal-validate'));
    const rejectModal   = new bootstrap.Modal(document.getElementById('modal-reject'));

    $('#modal-publicar').select2({ dropdownParent: $('#modal-validate'), width: '100%', minimumResultsForSearch: Infinity });

    $('#btn-validate').on('click', function () {
        $('#modal-publicar').val('1').trigger('change');
        validateModal.show();
    });

    $('#btn-validate-confirm').on('click', function () {
        const publicar = parseInt($('#modal-publicar').val(), 10);
        const btn = $(this).prop('disabled', true).text('Procesando...');
        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: { action: 'approve', publicar: publicar, _token: $('meta[name="csrf-token"]').attr('content') },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                validateModal.hide();
                const erp = res.erp_result;
                if (erp && erp.success) {
                    toastr.success('Validado y sincronizado con ERP correctamente');
                } else if (erp) {
                    toastr.warning('Contenido validado pero falló la sincronización con ERP: ' + (erp.message || 'Error desconocido'));
                } else {
                    toastr.success(res.message ?? 'Contenido aprobado');
                }
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al aprobar');
                btn.prop('disabled', false).text('Validar y sincronizar');
            }
        });
    });

    $('#btn-reject').on('click', function () {
        $('#reject-reason-input').val($('#action-description').val()).removeClass('is-invalid');
        rejectModal.show();
    });

    $('#reject-confirm-btn').on('click', function () {
        const reason = $('#reject-reason-input').val().trim();
        if (!reason) { $('#reject-reason-input').addClass('is-invalid'); return; }
        const btn = $(this).prop('disabled', true).text('Rechazando...');
        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: JSON.stringify({ action: 'reject', reason: reason, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                rejectModal.hide();
                toastr.success(res.message ?? 'Contenido rechazado');
                setTimeout(() => location.reload(), 700);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al rechazar');
                btn.prop('disabled', false).text('Rechazar');
            }
        });
    });

    document.getElementById('modal-reject').addEventListener('hidden.bs.modal', function () {
        $('#reject-reason-input').val('').removeClass('is-invalid');
        $('#reject-confirm-btn').prop('disabled', false).text('Rechazar');
    });
    @endif
});
</script>
@endpush
