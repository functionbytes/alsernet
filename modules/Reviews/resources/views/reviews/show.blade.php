@extends('layouts.theme')

@section('title', 'Detalle de reseña')

@section('content')

    @include('core::components.card', ['title' => 'Detalle de reseña'])

    @include('core::components.alerts')

    <div class="row">

        {{-- Columna izquierda: Moderación y estadísticas --}}
        <div class="col-lg-4">

            {{-- Moderación --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Moderación</h5>
                    <p class="small mb-0 text-muted">Visibilidad, destacado y etiquetas internas</p>
                </div>
                <div class="card-body">
                    <form id="moderation-form">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="is_visible"
                                       name="is_visible"
                                       value="1"
                                       {{ $review->moderation?->is_visible ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_visible">
                                    Visible en plataforma pública
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1 ms-4 ps-2">La reseña aparecerá en el widget y en las páginas públicas del negocio</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="is_featured"
                                       name="is_featured"
                                       value="1"
                                       {{ $review->moderation?->is_featured ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_featured">
                                    Destacada
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1 ms-4 ps-2">Se mostrará de forma prominente como reseña destacada en el widget</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Etiquetas</label>
                            @php
                                $availableTags  = $review->location->available_tags ?? [];
                                $assignedTags   = $review->moderation?->tags ?? [];
                            @endphp
                            <select class="form-select select2"
                                    id="tags"
                                    name="tags[]"
                                    multiple="multiple">
                                @if(count($availableTags) > 0)
                                    @foreach($availableTags as $tag)
                                        <option value="{{ $tag['slug'] }}"
                                            {{ in_array($tag['slug'], $assignedTags) ? 'selected' : '' }}>
                                            {{ $tag['label'] }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach($assignedTags as $tag)
                                        <option value="{{ $tag }}" selected>{{ $tag }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="text-muted">Categoriza la reseña para filtrarla y organizarla internamente</small>
                        </div>

                        <div class="mb-4">
                            <label for="internal_notes" class="form-label fw-semibold">Notas internas</label>
                            <textarea class="form-control"
                                      id="internal_notes"
                                      name="internal_notes"
                                      rows="3"
                                      placeholder="Notas solo visibles para el equipo...">{{ $review->moderation?->internal_notes }}</textarea>
                            <small class="text-muted">Solo visibles para el equipo interno, no se publican en ningún canal</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Guardar moderación
                        </button>
                    </form>
                </div>
            </div>

            {{-- Traducciones --}}
            @if($review->comment)
            @php $existingTranslations = $review->translations->keyBy('locale_code'); @endphp
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Traducciones</h6>
                            <small class="text-muted">{{ $review->translations->count() }} de {{ ($activeLocales ?? collect())->count() }} idiomas</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#translationsModal">
                            <i class="fas fa-language me-1"></i> Gestionar
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Estadísticas --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Estadísticas</h5>
                    <p class="small mb-0 text-muted">Información de sincronización</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Sincronizada</label>
                            <input type="text" class="form-control" value="{{ $review->created_at->diffForHumans() }}" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Última actualización</label>
                            <input type="text" class="form-control" value="{{ $review->updated_at->diffForHumans() }}" disabled>
                        </div>
                        @if($review->google_review_id)
                            <div class="col-12">
                                <label class="form-label fw-semibold">ID Google</label>
                                <input type="text" class="form-control font-monospace small" value="{{ Str::limit($review->google_review_id, 30) }}" disabled>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Acciones</h5>
                    <p class="small mb-0 text-muted">Operaciones sobre esta reseña</p>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary">
                        Volver al listado
                    </a>
                    <button type="button"
                            class="btn btn-info report-review-btn"
                            data-review-id="{{ $review->id }}">
                        Reportar reseña
                    </button>
                </div>
            </div>

        </div>

        {{-- Columna derecha: Detalle y respuesta --}}
        <div class="col-lg-8">

            {{-- Detalle de la reseña --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Reseña</h5>
                    <p class="small mb-0 text-muted">{{ $review->reviewer_name }}</p>
                </div>
                <div class="card-body">

                    {{-- Datos del cliente --}}
                    <div class="mb-2">
                        <p class="fw-semibold mb-0">Datos del cliente</p>
                        <small class="text-muted">Información del autor y contenido de la reseña recibida</small>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Cliente</label>
                            <input type="text" class="form-control" value="{{ $review->reviewer_name }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Calificación</label>
                            <input type="text" class="form-control" value="{{ $review->star_rating->value() }}/5" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Comentario</label>
                            <textarea class="form-control" rows="3" disabled>{{ $review->comment ?: 'Sin comentario' }}</textarea>
                        </div>
                    </div>

                    <hr>

                    {{-- Información de ubicación --}}
                    <div class="mb-2">
                        <p class="fw-semibold mb-0">Ubicación</p>
                        <small class="text-muted">Establecimiento donde se originó la reseña</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Fecha</label>
                            <input type="text" class="form-control" value="{{ $review->review_time->format('d/m/Y H:i') }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" class="form-control" value="{{ $review->location->name }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Dirección</label>
                            <input type="text" class="form-control" value="{{ $review->location->address }}" disabled>
                        </div>
                        @if($review->location->phone)
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="text" class="form-control" value="{{ $review->location->phone }}" disabled>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            {{-- Responder reseña --}}
            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Responder reseña</h5>
                    <p class="small mb-0 text-muted">Redacta y publica una respuesta en Google</p>
                </div>
                <div class="card-body">

                    @if($review->replies->count() > 0)
                        @php $lastReply = $review->replies->first(); @endphp
                        <div class="alert alert-info mb-3" data-reply-id="{{ $lastReply->id }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>Última respuesta:</strong>
                                    @if($lastReply->status === \Modules\Reviews\Enums\ReplyStatus::DRAFT)
                                        <span class="badge bg-warning-subtle text-warning">Borrador</span>
                                    @elseif($lastReply->status === \Modules\Reviews\Enums\ReplyStatus::APPROVED)
                                        <span class="badge bg-primary-subtle text-primary">Aprobada</span>
                                    @elseif($lastReply->status === \Modules\Reviews\Enums\ReplyStatus::PUBLISHED)
                                        <span class="badge bg-success-subtle text-success">Publicada</span>
                                    @elseif($lastReply->status === \Modules\Reviews\Enums\ReplyStatus::FAILED)
                                        <span class="badge bg-danger-subtle text-danger">Error</span>
                                    @endif
                                    @if($lastReply->published_at)
                                        <br><small>Publicada el {{ $lastReply->published_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                </div>
                                @if($lastReply->isDraft())
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary edit-reply-inline-btn"
                                            data-reply-id="{{ $lastReply->id }}">
                                        <i class="fas fa-pencil-alt me-1"></i> Editar
                                    </button>
                                @endif
                            </div>
                            @if($lastReply->reply_text)
                                <div class="mt-2 p-2 bg-white rounded reply-text-display">{{ $lastReply->reply_text }}</div>
                            @endif
                        </div>
                    @endif

                    <div class="mb-2">
                        <p class="fw-semibold mb-0">Sugerencias de plantillas</p>
                        <small class="text-muted">Selecciona una plantilla predefinida para usar como base de tu respuesta</small>
                    </div>

                    <form id="reply-form">
                        <div class="row g-3">

                            <div class="col-12">
                                <select class="form-select" id="template-select">
                                    <option value="">Seleccionar plantilla...</option>
                                    @foreach($templates ?? [] as $tpl)
                                        <option value="{{ $tpl->id }}" data-body="{{ $tpl->body }}">
                                            {{ $tpl->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="reply_text" class="form-label fw-semibold">Respuesta</label>
                                <textarea class="form-control"
                                          id="reply_text"
                                          name="reply_text"
                                          rows="5"
                                          placeholder="Escribe tu respuesta aquí...">{{ old('reply_text', $review->replies->first()->reply_text ?? '') }}</textarea>
                                <small class="text-muted">Variables disponibles: {reviewer_name}, {location_name}, {star_rating}</small>
                            </div>

                            @if($aiEnabled ?? false)
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Tono de la respuesta</label>
                                    <div class="btn-group btn-group-sm w-100" role="group" id="toneSelector">
                                        <input type="radio" class="btn-check" name="ai_tone" value="professional" id="tone_professional" checked>
                                        <label class="btn btn-outline-secondary" for="tone_professional">Profesional</label>

                                        <input type="radio" class="btn-check" name="ai_tone" value="friendly" id="tone_friendly">
                                        <label class="btn btn-outline-secondary" for="tone_friendly">Amigable</label>

                                        <input type="radio" class="btn-check" name="ai_tone" value="apologetic" id="tone_apologetic">
                                        <label class="btn btn-outline-secondary" for="tone_apologetic">Disculpa</label>

                                        <input type="radio" class="btn-check" name="ai_tone" value="grateful" id="tone_grateful">
                                        <label class="btn btn-outline-secondary" for="tone_grateful">Agradecimiento</label>

                                        <input type="radio" class="btn-check" name="ai_tone" value="concise" id="tone_concise">
                                        <label class="btn btn-outline-secondary" for="tone_concise">Conciso</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="button" class="btn btn-outline-primary w-100" id="generate-ai-reply">
                                        <i class="fas fa-robot me-1"></i> Generar con IA
                                    </button>
                                    <span id="ai-loading" class="text-muted d-none mt-1 d-block">
                                        <i class="fas fa-spinner fa-spin"></i> Generando...
                                    </span>
                                </div>
                            @endif

                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary w-100" id="save-draft">
                            Guardar borrador
                        </button>
                        @can('reviews.replies.approve')
                            <button type="button" class="btn btn-outline-secondary w-100" id="approve-reply">
                                Aprobar
                            </button>
                        @endcan
                        @can('reviews.replies.publish')
                            <button type="button" class="btn btn-outline-secondary w-100" id="publish-reply">
                                Publicar en Google
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal: Reportar reseña --}}
    <div class="modal fade" id="reportReviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reportar reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Reportar como inapropiada a Google</p>
                    @foreach(['SPAM' => 'Spam', 'FAKE_REVIEW' => 'Reseña falsa', 'HATE_SPEECH' => 'Discurso de odio', 'HARASSMENT' => 'Acoso', 'OTHER' => 'Otro'] as $value => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="report_reason" value="{{ $value }}" id="reason_{{ $value }}">
                            <label class="form-check-label" for="reason_{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer d-block">
                    <button type="button" class="btn btn-danger w-100 mb-2" id="submitReportBtn">Reportar</button>
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

{{-- Modal Traducciones --}}
@if($review->comment)
<div class="modal fade" id="translationsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-language me-2"></i>Traducciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                @forelse($activeLocales ?? [] as $locale)
                    @php $t = $existingTranslations->get(strtoupper($locale->code)); @endphp
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">
                                @if($locale->flag) {{ $locale->flag }} @endif
                                {{ $locale->native_name ?? $locale->name }}
                                <span class="text-muted small">({{ strtoupper($locale->code) }})</span>
                            </span>
                            <div class="d-flex gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary show-translate-btn"
                                        data-locale="{{ strtoupper($locale->code) }}"
                                        data-review-id="{{ $review->id }}">
                                    <i class="fas fa-magic me-1"></i>{{ $t ? 'Re-traducir' : 'Traducir' }}
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary btn-edit-translation"
                                        data-locale="{{ strtoupper($locale->code) }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>
                        <div class="translation-display" data-locale="{{ strtoupper($locale->code) }}">
                            <p class="mb-0 small show-translated-text" data-locale="{{ strtoupper($locale->code) }}">
                                @if($t)
                                    {{ $t->translated_text }}
                                @else
                                    <span class="text-muted fst-italic">Sin traducción</span>
                                @endif
                            </p>
                            @if($t?->translated_at)
                                <small class="text-muted">{{ $t->translated_at->format('d/m/Y H:i') }}</small>
                            @endif
                        </div>
                        <div class="translation-edit d-none" data-locale="{{ strtoupper($locale->code) }}">
                            <textarea class="form-control form-control-sm mb-2 translation-edit-input"
                                      data-locale="{{ strtoupper($locale->code) }}"
                                      rows="3">{{ $t?->translated_text }}</textarea>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary btn-save-translation"
                                        data-locale="{{ strtoupper($locale->code) }}"
                                        data-review-id="{{ $review->id }}">
                                    <i class="fas fa-save me-1"></i>Guardar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel-edit"
                                        data-locale="{{ strtoupper($locale->code) }}">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">No hay idiomas activos configurados.</div>
                @endforelse
            </div>
            <div class="modal-footer d-block">
                @if(($activeLocales ?? collect())->isNotEmpty())
                <button type="button" class="btn btn-primary w-100 mb-2" id="translate-all-btn">
                    <i class="fas fa-magic me-1"></i>Traducir todos los idiomas
                </button>
                @endif
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('css')
<link href="{{ asset('core/select2/css/select2.min.css') }}" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #90bb13;
        border-color: #7a9b10;
        color: #fff;
        padding: 3px 8px;
        border-radius: 4px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff;
        margin-right: 5px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #000;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('core/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('modules/reviews/js/reply-editor.js') }}"></script>
<script>
$(document).ready(function() {
    $('#tags').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Agregar etiquetas...',
        minimumInputLength: 0,
        @if(count($availableTags) === 0)
        ajax: {
            url: '{{ route("reviews.tags.list") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term ?? '' }; },
            processResults: function(data) {
                return { results: data.tags.map(function(tag) { return { id: tag, text: tag }; }) };
            },
            cache: true,
        },
        @endif
    });

    const reviewId = {{ $review->id }};
    const reviewerName = '{{ $review->reviewer_name }}';
    const locationName = '{{ $review->location->name }}';
    const starRating = '{{ $review->star_rating }}';

    // Select2 para plantillas
    $('#template-select').select2({
        placeholder: 'Seleccionar plantilla...',
        allowClear: true,
        width: '100%',
    });

    // Cargar plantilla
    $('#template-select').on('change', function() {
        const body = $(this).find(':selected').data('body');
        if (body) {
            let text = body
                .replace(/{reviewer_name}/g, reviewerName)
                .replace(/{location_name}/g, locationName)
                .replace(/{star_rating}/g, starRating);
            $('#reply_text').val(text);
        }
    });

    // Guardar borrador
    $('#save-draft').on('click', function() {
        saveReply('draft');
    });

    // Aprobar
    $('#approve-reply').on('click', function() {
        saveReply('approved');
    });

    // Publicar
    $('#publish-reply').on('click', function() {
        if (true) {
            saveReply('published');
        }
    });

    function saveReply(status) {
        const replyText = $('#reply_text').val();
        if (!replyText.trim()) {
            toastr.error('Debes escribir una respuesta');
            return;
        }

        $.ajax({
            url: '{{ route("reviews.replies.store") }}',
            method: 'POST',
            data: {
                review_id: reviewId,
                reply_text: replyText,
                status: status,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                toastr.success(response.message || 'Respuesta guardada');
                setTimeout(() => location.reload(), 1500);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar respuesta');
            }
        });
    }

    // Generar respuesta con IA
    @if($aiEnabled ?? false)
    $('#generate-ai-reply').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);
        $('#ai-loading').removeClass('d-none');

        const tone = $('input[name="ai_tone"]:checked').val() || 'professional';

        $.ajax({
            url: `/reviews/${reviewId}/ai-reply`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { tone },
            success: function(response) {
                $('#reply_text').val(response.reply);
                toastr.success('Respuesta generada con IA');
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al generar respuesta');
            },
            complete: function() {
                btn.prop('disabled', false);
                $('#ai-loading').addClass('d-none');
            }
        });
    });
    @endif

    // Traducciones
    @if($review->comment)
    const translateUrl = '{{ url("panel/reviews/" . $review->id . "/translate") }}';
    const totalLocales = {{ ($activeLocales ?? collect())->count() }};

    function updateTranslationCount() {
        const done = $('#translationsModal .show-translated-text').filter(function () {
            return !$(this).find('.fst-italic').length && $(this).text().trim() !== '';
        }).length;
        $('.card .text-muted small, .card small.text-muted').first().text(done + ' de ' + totalLocales + ' idiomas');
    }

    function doTranslate(localeCode, btn) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');
        $.ajax({
            url: translateUrl,
            method: 'POST',
            data: { target_lang: localeCode },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    $('.show-translated-text[data-locale="' + localeCode + '"]')
                        .text(res.translated || '').removeClass('text-muted fst-italic');
                    btn.html('<i class="fas fa-magic me-1"></i>Re-traducir');
                    toastr.success('Traducido a ' + localeCode);
                    updateTranslationCount();
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al traducir');
                btn.html('<i class="fas fa-magic me-1"></i>Traducir');
            },
            complete: function () { btn.prop('disabled', false); },
        });
    }

    $(document).on('click', '.show-translate-btn', function () {
        doTranslate($(this).data('locale'), $(this));
    });

    $(document).on('click', '#translate-all-btn', function () {
        const allBtn = $(this);
        allBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Traduciendo...');
        $('#translationsModal .show-translate-btn').each(function () {
            doTranslate($(this).data('locale'), $(this));
        });
        const observer = setInterval(function () {
            if ($('#translationsModal .show-translate-btn[disabled]').length === 0) {
                allBtn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Traducir todos los idiomas');
                clearInterval(observer);
            }
        }, 500);
    });

    $(document).on('click', '.btn-edit-translation', function () {
        const locale = $(this).data('locale');
        const currentText = $('.show-translated-text[data-locale="' + locale + '"]').text().trim();
        $('.translation-edit-input[data-locale="' + locale + '"]').val(currentText);
        $('.translation-display[data-locale="' + locale + '"]').addClass('d-none');
        $('.translation-edit[data-locale="' + locale + '"]').removeClass('d-none');
    });

    $(document).on('click', '.btn-cancel-edit', function () {
        const locale = $(this).data('locale');
        $('.translation-display[data-locale="' + locale + '"]').removeClass('d-none');
        $('.translation-edit[data-locale="' + locale + '"]').addClass('d-none');
    });

    $(document).on('click', '.btn-save-translation', function () {
        const locale = $(this).data('locale');
        const text = $('.translation-edit-input[data-locale="' + locale + '"]').val().trim();
        const btn = $(this);
        if (!text) { toastr.warning('El texto no puede estar vacío.'); return; }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '{{ url("panel/reviews/" . $review->id . "/translations") }}/' + locale,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { translated_text: text },
            success: function (res) {
                $('.show-translated-text[data-locale="' + locale + '"]')
                    .text(res.translated_text).removeClass('text-muted fst-italic');
                $('.translation-display[data-locale="' + locale + '"]').removeClass('d-none');
                $('.translation-edit[data-locale="' + locale + '"]').addClass('d-none');
                toastr.success('Traducción guardada.');
                updateTranslationCount();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar.');
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar');
            },
        });
    });
    @endif

    // Inline reply editing
    $(document).on('click', '.edit-reply-inline-btn', function() {
        const replyId = $(this).data('reply-id');
        const replyContainer = $(this).closest('[data-reply-id="' + replyId + '"]');
        const currentText = replyContainer.find('.reply-text-display').text().trim();

        replyContainer.find('.reply-text-display').hide();

        if (!replyContainer.find('.reply-inline-editor').length) {
            const editor = $(`
                <div class="reply-inline-editor mt-2">
                    <textarea class="form-control reply-inline-textarea" rows="4" maxlength="4096">${currentText}</textarea>
                    <small class="char-counter text-muted d-block mt-1">${currentText.length} / 4096 caracteres</small>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-primary save-reply-inline-btn" data-reply-id="${replyId}">Guardar</button>
                        <button class="btn btn-sm btn-secondary ms-1 cancel-reply-inline-btn">Cancelar</button>
                    </div>
                </div>
            `);
            replyContainer.append(editor);
        } else {
            replyContainer.find('.reply-inline-editor').show();
        }
    });

    $(document).on('input', '.reply-inline-textarea', function() {
        const len = $(this).val().length;
        $(this).next('.char-counter').text(`${len} / 4096 caracteres`).toggleClass('text-danger', len > 4000);
    });

    $(document).on('click', '.cancel-reply-inline-btn', function() {
        const editor = $(this).closest('.reply-inline-editor');
        editor.hide();
        editor.closest('[data-reply-id]').find('.reply-text-display').show();
    });

    $(document).on('click', '.save-reply-inline-btn', function() {
        const replyId = $(this).data('reply-id');
        const text = $(this).closest('.reply-inline-editor').find('.reply-inline-textarea').val();
        const btn = $(this);

        btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: `/reviews/replies/${replyId}/inline`,
            method: 'PATCH',
            data: { text, _token: $('meta[name="csrf-token"]').attr('content') },
            success(data) {
                const container = btn.closest('[data-reply-id="' + replyId + '"]');
                container.find('.reply-text-display').text(data.text).show();
                container.find('.reply-inline-editor').hide();
                toastr.success('Respuesta actualizada');
            },
            error(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar');
                btn.prop('disabled', false).text('Guardar');
            }
        });
    });

    // Report review to Google
    $(document).on('click', '.report-review-btn', function() {
        const reviewId = $(this).data('review-id');
        $('input[name="report_reason"]').prop('checked', false);
        $('#reportReviewModal').data('review-id', reviewId).modal('show');
    });

    $(document).on('click', '#submitReportBtn', function() {
        const reviewId = $('#reportReviewModal').data('review-id');
        const reason = $('input[name="report_reason"]:checked').val();

        if (!reason) {
            toastr.warning('Selecciona un motivo');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).text('Reportando...');

        $.ajax({
            url: `/reviews/reviews/${reviewId}/report`,
            method: 'POST',
            data: { reason, _token: $('meta[name="csrf-token"]').attr('content') },
            success() {
                $('#reportReviewModal').modal('hide');
                toastr.success('Reseña reportada a Google');
            },
            error() {
                toastr.error('Error al reportar. Intenta de nuevo.');
            },
            complete() {
                btn.prop('disabled', false).text('Reportar');
            }
        });
    });

    // Guardar moderación
    $('#moderation-form').on('submit', function(e) {
        e.preventDefault();

        const tags = $('#tags').val() || [];

        $.ajax({
            url: `/reviews/${reviewId}/moderate`,
            method: 'PATCH',
            data: {
                is_visible: $('#is_visible').is(':checked') ? 1 : 0,
                is_featured: $('#is_featured').is(':checked') ? 1 : 0,
                tags: tags,
                internal_notes: $('#internal_notes').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                toastr.success(response.message || 'Moderación guardada');
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar moderación');
            }
        });
    });

    // Character counter for reply textarea
    $(document).on('input', 'textarea[name="reply_text"], #replyTextarea', function() {
        const maxChars = 4096;
        const currentLength = $(this).val().length;
        const remaining = maxChars - currentLength;

        let counter = $(this).next('.char-counter');
        if (!counter.length) {
            counter = $('<small class="char-counter text-muted d-block mt-1"></small>');
            $(this).after(counter);
        }

        counter.text(`${currentLength} / ${maxChars} caracteres`);
        counter.toggleClass('text-danger', remaining < 100);
        counter.toggleClass('text-muted', remaining >= 100);

        $(this).closest('form').find('[type="submit"]').prop('disabled', currentLength > maxChars);
    });
});
</script>
@endpush
