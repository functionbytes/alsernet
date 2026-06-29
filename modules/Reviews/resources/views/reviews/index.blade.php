@extends('layouts.theme')

@section('title', 'Gestión de reseñas')

@section('page_header')
    @include('core::components.card', ['title' => 'Gestión de reseñas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reseñas de Google My Business</h5>
                        <p class="small mb-0 text-muted">Gestiona y responde las reseñas de tus ubicaciones de Google</p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <a href="#" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Acciones
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('import.show', 'reviews') }}">Importar</a></li>
                                <li><a class="dropdown-item" id="export-csv-link" href="#">Exportar CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total reseñas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h4>
                                <small class="text-muted">En el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Rating promedio</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['avg_rating'] ?? 0, 1) }}</h4>
                                <small class="text-muted">de 5.0</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sin responder</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['unanswered'] ?? 0) }}</h4>
                                <small class="text-muted">Pendientes de respuesta</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Respondidas hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['replied_today'] ?? 0) }}</h4>
                                <small class="text-muted">Respondidas hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('reviews.index') }}" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" id="search-input" class="form-control -0 ps-0"
                                       placeholder="Buscar por cliente o comentario..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 190px;">
                            <select name="reply_status" class="form-select select2 h-100" id="filter-reply-status">
                                <option value="">Todos los estados</option>
                                <option value="unanswered" {{ request('reply_status') === 'unanswered' ? 'selected' : '' }}>Sin responder</option>
                                <option value="draft"      {{ request('reply_status') === 'draft'      ? 'selected' : '' }}>Borrador</option>
                                <option value="published"  {{ request('reply_status') === 'published'  ? 'selected' : '' }}>Publicada</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 190px;">
                            <select name="location_id" class="form-select select2 h-100" id="filter-location">
                                <option value="">Todas las ubicaciones</option>
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" title="Filtros avanzados"
                                    data-bs-toggle="collapse" data-bs-target="#advanced-filters">
                                <i class="fas fa-sliders-h"></i>
                            </button>
                            @if(request()->hasAny(['search', 'reply_status', 'location_id', 'ratings', 'is_visible']))
                                <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Advanced filters --}}
                    <div class="collapse mt-3 {{ request()->hasAny(['ratings', 'is_visible']) ? 'show' : '' }}" id="advanced-filters">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Rating</label>
                                <div class="btn-group w-100" role="group">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input type="checkbox" class="btn-check" name="ratings[]" id="rating-{{ $i }}"
                                               value="{{ $i }}" {{ in_array((string)$i, (array) request('ratings', [])) ? 'checked' : '' }}>
                                        <label class="btn btn-outline-warning" for="rating-{{ $i }}">{{ $i }}★</label>
                                    @endfor
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Visibilidad</label>
                                <select name="is_visible" class="form-select" id="filter-visibility">
                                    <option value="">Todas</option>
                                    <option value="1" {{ request('is_visible') === '1' ? 'selected' : '' }}>Visibles</option>
                                    <option value="0" {{ request('is_visible') === '0' ? 'selected' : '' }}>Ocultas</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>@include('reviews::saved-filters._dropdown')</div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($reviews->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-star fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request()->hasAny(['search', 'reply_status', 'location_id', 'ratings', 'is_visible']))
                                    No se encontraron reseñas
                                @else
                                    No hay reseñas disponibles
                                @endif
                            </h6>
                            <p class="text-muted mb-0">
                                @if(request()->hasAny(['search', 'reply_status', 'location_id', 'ratings', 'is_visible']))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Las reseñas sincronizadas desde Google aparecerán aquí
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="reviews-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="select-all"></th>
                                    <th>Ubicación</th>
                                    <th>Cliente</th>
                                    <th>Rating</th>
                                    <th>Respuesta</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reviews as $review)
                                    @php
                                        $tags = $review->moderation?->tags ?? [];
                                        $replyStatus = $review->reply_status;
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $review->id }}"></td>
                                        <td><strong>{{ $review->location?->name ?? '—' }}</strong></td>
                                        <td>{{ $review->reviewer_name }}</td>
                                        <td>{{ $review->star_rating->value() }}</td>
                                        <td>
                                            @if($replyStatus === 'published')
                                                <span class="badge bg-success-subtle text-success">Publicada</span>
                                            @elseif($replyStatus === 'draft')
                                                <span class="badge bg-warning-subtle text-warning">Borrador</span>
                                            @elseif($replyStatus === 'failed')
                                                <span class="badge bg-secondary">Error</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Sin responder</span>
                                            @endif
                                        </td>
                                        <td><span>{{ $review->review_time?->format('d/m/Y') ?? '—' }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if(!$review->google_reply_text)
                                                        <li>
                                                            <a class="dropdown-item btn-reply" href="javascript:void(0)"
                                                               data-review-id="{{ $review->id }}"
                                                               data-reviewer-name="{{ $review->reviewer_name }}"
                                                               data-star-rating="{{ $review->star_rating->name }}"
                                                               data-comment="{{ $review->comment }}"
                                                               data-location-name="{{ $review->location?->name }}">
                                                                Responder
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if($review->comment)
                                                        <li>
                                                            <a class="dropdown-item btn-translate" href="javascript:void(0)"
                                                               data-review-id="{{ $review->id }}"
                                                               data-comment="{{ $review->comment }}"
                                                               data-translations="{{ json_encode($review->translations->keyBy('locale_code')->map(fn($t) => ['text' => $t->translated_text, 'lang' => $t->detected_language, 'date' => $t->translated_at?->format('d/m/Y')])) }}">
                                                                Traducir
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('reviews.show', $review->id) }}">
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item report-review-btn" href="javascript:void(0)"
                                                           data-review-id="{{ $review->id }}">
                                                            Reportar a Google
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($reviews->hasPages())
                <div class="card-footer">
                    {{ $reviews->links() }}
                </div>
            @endif

        </div>
    </div>

    @include('reviews::saved-filters._save-modal')

    {{-- Translate modal --}}
    <div class="modal fade" id="translateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Traducir reseña</h5>
                        <small class="text-muted" id="translate-detected-lang-label">Traducción automática vía DeepL</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-semibold mb-1">Texto original</p>
                    <div class="p-3 rounded border bg-light mb-4">
                        <p class="mb-0" id="translate-original-text"></p>
                    </div>
                    <p class="fw-semibold mb-2">Traducciones por idioma</p>
                    @if(($activeLocales ?? collect())->isEmpty())
                        <div class="alert alert-warning">No hay idiomas activos configurados.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="translate-locales-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Idioma</th>
                                        <th>Traducción</th>
                                        <th>Fecha</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeLocales ?? [] as $locale)
                                        <tr data-locale-code="{{ $locale->code }}">
                                            <td class="text-nowrap">
                                                @if($locale->flag) {{ $locale->flag }} @endif
                                                {{ $locale->native_name ?? $locale->name }}
                                                <small class="text-muted">({{ strtoupper($locale->code) }})</small>
                                            </td>
                                            <td>
                                                <span class="translate-result-cell text-muted fst-italic">Sin traducción</span>
                                                <div class="translate-edit-inline d-none mt-1">
                                                    <textarea class="form-control form-control-sm translate-manual-input" rows="2"></textarea>
                                                    <div class="d-flex gap-1 mt-1">
                                                        <button type="button" class="btn btn-sm btn-primary btn-save-manual-translation">
                                                            <i class="fas fa-save me-1"></i>Guardar
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel-manual">
                                                            Cancelar
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="translate-date-cell text-muted small text-nowrap"></td>
                                            <td class="text-end text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-translate-locale" title="Traducir con DeepL">
                                                    <i class="fas fa-magic"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-manual-translate" title="Editar manualmente">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="btn-translate-all">
                        Traducir todos los idiomas
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Floating bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> reseña(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="make_visible">Hacer visibles</option>
                            <option value="hide">Ocultar</option>
                            <option value="feature">Marcar como destacadas</option>
                            <option value="unfeature">Quitar destacado</option>
                            <option value="add_tags">Agregar etiquetas...</option>
                            <option value="remove_tags">Quitar etiquetas...</option>
                            <option value="translate">Traducir a todos los idiomas activos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk tags modal --}}
    <div class="modal fade" id="bulkTagsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkTagsModalTitle">Gestionar etiquetas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="bulk-selected-info"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Etiquetas</label>
                        <select class="form-select select2" id="bulk-tags-select" multiple="multiple"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="bulk-tags-apply-btn">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reply modal --}}
    <div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Responder reseña</h5>
                        <small class="text-muted">La respuesta se publicará en Google My Business</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Información de la reseña --}}
                    <div class="mb-1">
                        <p class="fw-semibold mb-0">Reseña</p>
                        <small class="text-muted">Información del cliente y contenido de la reseña recibida</small>
                    </div>
                    <div class="row g-3 mb-3 mt-0">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Cliente</label>
                            <input type="text" class="form-control" id="modal-reviewer-name" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Calificación</label>
                            <input type="text" class="form-control" id="modal-star-rating" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Comentario</label>
                            <textarea class="form-control" id="modal-comment" rows="3" disabled></textarea>
                        </div>
                    </div>

                    <hr>

                    {{-- Plantillas --}}
                    <div class="mb-1">
                        <p class="fw-semibold mb-0">Sugerencias de plantillas</p>
                        <small class="text-muted">Selecciona una plantilla predefinida para usar como base de tu respuesta</small>
                    </div>
                    <div class="mb-3 mt-2">
                        <select class="form-select" id="modal-template-select">
                            <option value="">Seleccionar plantilla...</option>
                            @foreach($templates ?? [] as $tpl)
                                <option value="{{ $tpl->id }}" data-body="{{ $tpl->body }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Textarea --}}
                    <div>
                        <label for="reply-text" class="form-label fw-semibold">Tu respuesta</label>
                        <textarea class="form-control" id="reply-text" rows="5" placeholder="Escribe tu respuesta aquí..."></textarea>
                        <small class="form-text text-muted mt-1 d-block">
                            Variables disponibles: <code>{reviewer_name}</code>, <code>{location_name}</code>, <code>{star_rating}</code>
                        </small>
                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="publish-reply">Guardar y publicar</button>
                    <button type="button" class="btn btn-light border w-100 mb-1" id="save-reply-draft">Guardar borrador</button>
                    <button type="button" class="btn btn-light border w-100 mb-1" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Report modal --}}
    <div class="modal fade" id="reportReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Reportar reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Reportar como inapropiada a Google</p>
                    <label for="report-reason-select" class="form-label fw-semibold">Motivo del reporte</label>
                    <select id="report-reason-select" class="form-select">
                        <option value="">Seleccionar motivo...</option>
                        <option value="SPAM">Spam</option>
                        <option value="FAKE_REVIEW">Reseña falsa</option>
                        <option value="HATE_SPEECH">Discurso de odio</option>
                        <option value="HARASSMENT">Acoso</option>
                        <option value="OTHER">Otro</option>
                    </select>
                </div>
                <div class="modal-footer d-grid gap-2">
                    <button type="button" class="btn btn-primary w-100 py-2" id="submitReportBtn">Reportar</button>
                    <button type="button" class="btn btn-light border w-100 py-2" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<style>
    .tag-badge {
        display: inline-block;
        padding: 4px 8px;
        margin: 2px;
        background-color: #90bb13;
        color: #fff;
        border-radius: 4px;
        font-size: 0.75rem;
        white-space: nowrap;
    }

    .reply-review-card {
        background: #f8f9fa;
        border-left: 4px solid #90bb13;
    }
    .bg-light-success {
        background-color: #f0f9e8;
        border-color: #c8e6a0 !important;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });
    let bulkPendingTagAction = null;

    // Select all
    $('#select-all').on('change', function () {
        $('.bulk-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
    });

    // -----------------------------------------------------------------------
    // Tags select2
    // -----------------------------------------------------------------------

    $('#filter-tags, #bulk-tags-select').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Seleccionar o escribir etiquetas...',
        ajax: {
            url: '{{ route("reviews.tags.list") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                return { results: data.tags.map(tag => ({ id: tag, text: tag })) };
            },
            cache: true,
        },
    });

    // -----------------------------------------------------------------------
    // Bulk modal
    // -----------------------------------------------------------------------

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una reseña.'); return; }

        if (action === 'add_tags' || action === 'remove_tags') {
            bulkPendingTagAction = action;
            $('#bulkTagsModalTitle').text(action === 'add_tags' ? 'Agregar etiquetas' : 'Quitar etiquetas');
            $('#bulk-selected-info').text(ids.length + ' reseña(s) seleccionada(s)');
            $('#bulk-tags-select').val(null).trigger('change');
            $('#bulk-modal').modal('hide');
            $('#bulkTagsModal').modal('show');
            return;
        }


        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("reviews.bulk-moderate") }}',
            method: 'POST',
            data: { review_ids: ids, action: action },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || 'Acción aplicada correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // -----------------------------------------------------------------------
    // Bulk tags modal
    // -----------------------------------------------------------------------

    $('#bulk-tags-select').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Seleccionar o escribir etiquetas...',
        dropdownParent: $('#bulkTagsModal'),
        ajax: {
            url: '{{ route("reviews.tags.list") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                return { results: data.tags.map(tag => ({ id: tag, text: tag })) };
            },
            cache: true,
        },
    });

    $('#bulk-tags-apply-btn').on('click', function () {
        const tags = $('#bulk-tags-select').val();
        const ids  = bulk.getIds();

        if (!tags || tags.length === 0) { toastr.error('Selecciona al menos una etiqueta.'); return; }

        const btn = $(this);
        btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("reviews.bulk-moderate") }}',
            method: 'POST',
            data: { review_ids: ids, action: bulkPendingTagAction, tags: tags },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulkTagsModal').modal('hide');
                toastr.success(res.message || 'Etiquetas actualizadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
            },
            complete: function () {
                btn.prop('disabled', false).text('Aplicar');
                bulkPendingTagAction = null;
            },
        });
    });

    // -----------------------------------------------------------------------
    // Export
    // -----------------------------------------------------------------------

    $('#export-csv-link').on('click', function (e) {
        e.preventDefault();
        const params = $.param({
            location_id:  $('#filter-location').val(),
            reply_status: $('#filter-reply-status').val(),
        });
        window.location.href = '{{ route("reviews.export") }}?' + params;
    });

    // -----------------------------------------------------------------------
    // Toggle visibility (inline switch)
    // -----------------------------------------------------------------------

    $(document).on('change', '.toggle-visibility', function () {
        const reviewId = $(this).data('review-id');
        const isVisible = $(this).is(':checked');

        $.ajax({
            url: `/reviews/${reviewId}/moderate`,
            method: 'PATCH',
            data: { is_visible: isVisible ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () { toastr.success('Visibilidad actualizada'); },
            error: function () { toastr.error('Error al actualizar visibilidad'); },
        });
    });

    // -----------------------------------------------------------------------
    // Reply
    // -----------------------------------------------------------------------

    $(document).on('click', '.btn-reply', function () {
        const btn = $(this);
        window.currentReviewId   = btn.data('review-id');
        window.currentReviewData = {
            reviewer_name: btn.data('reviewer-name'),
            star_rating:   btn.data('star-rating'),
            comment:       btn.data('comment'),
            location_name: btn.data('location-name'),
        };

        const ratingMap = { ONE: '1/5', TWO: '2/5', THREE: '3/5', FOUR: '4/5', FIVE: '5/5' };
        $('#modal-reviewer-name').val(window.currentReviewData.reviewer_name);
        $('#modal-star-rating').val(ratingMap[window.currentReviewData.star_rating] || window.currentReviewData.star_rating);
        $('#modal-comment').val(window.currentReviewData.comment || 'Sin comentario');
        $('#reply-text').val('');
        $('#modal-template-select').val('').trigger('change');

        $('#replyModal').modal('show');
    });

    function escapeHtml(text) {
        return text.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    $('#modal-template-select').on('change', function () {
        const body = $(this).find(':selected').data('body');
        if (!body) { return; }
        const text = body
            .replace(/{reviewer_name}/g, window.currentReviewData.reviewer_name)
            .replace(/{location_name}/g, window.currentReviewData.location_name)
            .replace(/{star_rating}/g, window.currentReviewData.star_rating);
        $('#reply-text').val(text).focus();
    });

    $('#save-reply-draft').on('click', function () { saveReply('draft'); });
    $('#publish-reply').on('click', function () {
        if (true) {
            saveReply('published');
        }
    });

    function saveReply(status) {
        const replyText = $('#reply-text').val().trim();
        if (!replyText) { toastr.error('Debes escribir una respuesta'); return; }

        $.ajax({
            url: '{{ route("reviews.replies.store") }}',
            method: 'POST',
            data: { review_id: window.currentReviewId, reply_text: replyText, status: status },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () { $('#save-reply-draft, #publish-reply').prop('disabled', true); },
            success: function (response) {
                toastr.success(response.message || 'Respuesta guardada correctamente');
                $('#replyModal').modal('hide');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                const message = xhr.status === 422
                    ? Object.values(xhr.responseJSON?.errors ?? {}).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error al guardar respuesta');
                toastr.error(message);
            },
            complete: function () { $('#save-reply-draft, #publish-reply').prop('disabled', false); },
        });
    }

    // -----------------------------------------------------------------------
    // Report
    // -----------------------------------------------------------------------

    $('#modal-template-select').select2({
        dropdownParent: $('#replyModal'),
        width: '100%',
        placeholder: 'Seleccionar plantilla...',
        allowClear: true,
    });

    $('#report-reason-select').select2({
        dropdownParent: $('#reportReviewModal'),
        width: '100%',
        placeholder: 'Seleccionar motivo...',
    });

    $(document).on('click', '.report-review-btn', function () {
        $('#report-reason-select').val('').trigger('change');
        $('#reportReviewModal').data('review-id', $(this).data('review-id')).modal('show');
    });

    $('#submitReportBtn').on('click', function () {
        const reviewId = $('#reportReviewModal').data('review-id');
        const reason   = $('#report-reason-select').val();

        if (!reason) { toastr.warning('Selecciona un motivo'); return; }

        const btn = $(this);
        btn.prop('disabled', true).text('Reportando...');

        $.ajax({
            url: `/reviews/reviews/${reviewId}/report`,
            method: 'POST',
            data: { reason, _token: $('meta[name="csrf-token"]').attr('content') },
            success() { $('#reportReviewModal').modal('hide'); toastr.success('Reseña reportada a Google'); },
            error()   { toastr.error('Error al reportar. Intenta de nuevo.'); },
            complete() { btn.prop('disabled', false).text('Reportar'); },
        });
    });

    // -----------------------------------------------------------------------
    // Char counter
    // -----------------------------------------------------------------------

    $(document).on('input', '#reply-text', function () {
        const max = 4096;
        const len = $(this).val().length;
        let counter = $(this).next('.char-counter');
        if (!counter.length) {
            counter = $('<small class="char-counter d-block mt-1"></small>');
            $(this).after(counter);
        }
        counter.text(`${len} / ${max} caracteres`)
               .toggleClass('text-danger', len > max - 100)
               .toggleClass('text-muted', len <= max - 100);
        $('#save-reply-draft, #publish-reply').prop('disabled', len > max);
    });

    // -----------------------------------------------------------------------
    // Translate
    // -----------------------------------------------------------------------

    let currentTranslateReviewId = null;
    const translateBaseUrl = '{{ url("panel/reviews") }}';

    $(document).on('click', '.btn-translate', function () {
        const btn = $(this);
        currentTranslateReviewId = btn.data('review-id');

        $('#translate-original-text').text(btn.data('comment') || '');
        $('#translate-detected-lang-label').text('Traducción automática vía DeepL');

        // Reset all rows
        $('#translate-locales-table tbody tr').each(function () {
            $(this).find('.translate-result-cell').text('Sin traducción').addClass('text-muted fst-italic');
            $(this).find('.translate-date-cell').text('');
            $(this).find('.btn-translate-locale').prop('disabled', false).text('Traducir');
        });

        // Populate already-translated rows from data attribute
        const existing = btn.data('translations') || {};
        $.each(existing, function (localeCode, data) {
            const row = $('#translate-locales-table').find('tr[data-locale-code="' + localeCode + '"]');
            if (data.text) {
                row.find('.translate-result-cell').text(data.text).removeClass('text-muted fst-italic');
                row.find('.translate-date-cell').text(data.date || '');
                row.find('.btn-translate-locale').text('Volver a traducir');
                if (data.lang) {
                    $('#translate-detected-lang-label').text('Idioma detectado: ' + data.lang.toUpperCase());
                }
            }
        });

        $('#translateModal').modal('show');
    });

    function translateLocale(localeCode, btn) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');
        const row = btn.closest('tr');

        $.ajax({
            url: translateBaseUrl + '/' + currentTranslateReviewId + '/translate',
            method: 'POST',
            data: { target_lang: localeCode },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    row.find('.translate-result-cell').text(res.translated || '').removeClass('text-muted fst-italic');
                    row.find('.translate-date-cell').text(new Date().toLocaleDateString('es-ES'));
                    if (res.detected_lang) {
                        $('#translate-detected-lang-label').text('Idioma detectado: ' + res.detected_lang.toUpperCase());
                    }
                    btn.text('Volver a traducir');
                    toastr.success('Traducido a ' + localeCode.toUpperCase());
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al traducir. Verifica que DeepL esté configurado.');
                btn.text('Traducir');
            },
            complete: function () { btn.prop('disabled', false); },
        });
    }

    $(document).on('click', '.btn-translate-locale', function () {
        if (!currentTranslateReviewId) { return; }
        translateLocale($(this).closest('tr').data('locale-code'), $(this));
    });

    $('#btn-translate-all').on('click', function () {
        if (!currentTranslateReviewId) { return; }
        $('#translate-locales-table tbody tr').each(function () {
            translateLocale($(this).data('locale-code'), $(this).find('.btn-translate-locale'));
        });
    });

    // Edición manual en modal de traducción
    $(document).on('click', '.btn-manual-translate', function () {
        const row = $(this).closest('tr');
        const currentText = row.find('.translate-result-cell').text().trim();
        row.find('.translate-manual-input').val(currentText === 'Sin traducción' ? '' : currentText);
        row.find('.translate-edit-inline').removeClass('d-none');
        row.find('.translate-result-cell').addClass('d-none');
    });

    $(document).on('click', '.btn-cancel-manual', function () {
        const row = $(this).closest('tr');
        row.find('.translate-edit-inline').addClass('d-none');
        row.find('.translate-result-cell').removeClass('d-none');
    });

    $(document).on('click', '.btn-save-manual-translation', function () {
        const row = $(this).closest('tr');
        const locale = row.data('locale-code');
        const text = row.find('.translate-manual-input').val().trim();
        const btn = $(this);
        if (!text || !currentTranslateReviewId) { return; }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: translateBaseUrl + '/' + currentTranslateReviewId + '/translations/' + locale,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { translated_text: text },
            success: function (res) {
                row.find('.translate-result-cell').text(res.translated_text).removeClass('text-muted fst-italic d-none');
                row.find('.translate-date-cell').text(res.translated_at || '');
                row.find('.translate-edit-inline').addClass('d-none');
                toastr.success('Traducción guardada.');
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar.');
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar');
            },
        });
    });

    // Bulk translate (todos los idiomas activos)
    $('#bulk-apply-btn').on('click.translate', function () {
        const action = $('#bulk-action-select').val();
        if (action !== 'translate') { return; }

        const ids = bulk.getIds();
        if (!ids.length) { toastr.warning('Selecciona al menos una reseña.'); return; }

        const localeCodes = [];
        $('#translate-locales-table tbody tr').each(function () {
            localeCodes.push($(this).data('locale-code'));
        });

        if (!localeCodes.length) { toastr.warning('No hay idiomas activos configurados.'); return; }
        

        const btn = $(this);
        btn.prop('disabled', true).text('Traduciendo...');

        const tasks = [];
        ids.forEach(function (reviewId) {
            localeCodes.forEach(function (code) {
                tasks.push({ reviewId: reviewId, code: code });
            });
        });

        let done = 0;
        let errors = 0;

        function runNext(index) {
            if (index >= tasks.length) {
                $('#bulk-modal').modal('hide');
                errors > 0
                    ? toastr.warning('Completado con ' + errors + ' error(es). Traducciones guardadas: ' + done)
                    : toastr.success('Se tradujeron ' + done + ' combinaciones correctamente.');
                setTimeout(() => location.reload(), 1000);
                return;
            }
            const task = tasks[index];
            $.ajax({
                url: translateBaseUrl + '/' + task.reviewId + '/translate',
                method: 'POST',
                data: { target_lang: task.code },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function () { done++; },
                error: function () { errors++; },
                complete: function () { runNext(index + 1); },
            });
        }

        runNext(0);
    });

    // Flash
    @if(session('success'))
        toastr.success(@json(session('success')));
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')));
    @endif
});
</script>
<script src="{{ asset('modules/Reviews/resources/js/reviews-index.js') }}"></script>
@endpush
