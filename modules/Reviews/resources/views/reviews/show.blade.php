@extends('layouts.theme')

@section('title', 'Detalle de reseña')

@section('content')

    @include('core::components.card', ['title' => 'Detalle de reseña'])

    <div class="row">
        <div class="col-md-8">
            <!-- Review Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Reseña de {{ $review->reviewer_name }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <img src="{{ $review->reviewer_photo_url ?? asset('images/default-avatar.png') }}"
                             class="rounded-circle me-3"
                             width="60"
                             height="60"
                             alt="{{ $review->reviewer_name }}">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $review->reviewer_name }}</h6>
                            <div class="mb-2">
                                <x-reviews::rating-stars :rating="$review->star_rating" />
                            </div>
                            <small class="text-muted">
                                <i class="far fa-clock"></i> {{ $review->review_time->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>

                    @if($review->comment)
                        <div class="mb-3" id="review-comment-section">
                            <p class="mb-0" id="original-comment">{{ $review->comment }}</p>
                            <div id="translated-comment-block" class="mt-2 d-none">
                                <span id="detected-lang-badge" class="badge bg-secondary-subtle text-secondary mb-1 d-none"></span>
                                <p class="mb-0 fst-italic text-muted" id="translated-comment-text"></p>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="translate-comment-btn">
                                    <i class="fas fa-language"></i> Traducir
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Esta reseña no incluye comentario escrito.
                        </div>
                    @endif

                    <div class="border-top pt-3 mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Ubicación:</strong><br>
                                {{ $review->location->name }}
                            </div>
                            <div class="col-md-6">
                                <strong>Dirección:</strong><br>
                                {{ $review->location->address }}
                            </div>
                        </div>
                    </div>

                    @if($review->location->phone)
                        <div class="mt-2">
                            <strong>Teléfono:</strong> {{ $review->location->phone }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Reply Form -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Responder reseña</h6>
                </div>
                <div class="card-body">
                    @if($review->replies->count() > 0)
                        @php
                            $lastReply = $review->replies->first();
                        @endphp
                        <div class="alert alert-info" data-reply-id="{{ $lastReply->id }}">
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
                                            data-reply-id="{{ $lastReply->id }}"
                                            title="Editar respuesta">
                                        <i class="fas fa-pencil-alt"></i> Editar
                                    </button>
                                @endif
                            </div>
                            @if($lastReply->reply_text)
                                <div class="mt-2 p-2 bg-light rounded reply-text-display">{{ $lastReply->reply_text }}</div>
                            @endif
                        </div>
                    @endif

                    <form id="reply-form">
                        <div class="mb-3">
                            <label for="template-select" class="form-label">Plantilla (opcional)</label>
                            <select class="form-select select2" id="template-select">
                                <option value="">Seleccionar plantilla...</option>
                                @foreach($templates ?? [] as $tpl)
                                    <option value="{{ $tpl->id }}"
                                            data-body="{{ $tpl->body }}">
                                        {{ $tpl->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reply_text" class="form-label">Respuesta</label>
                            <textarea class="form-control"
                                      id="reply_text"
                                      name="reply_text"
                                      rows="5"
                                      placeholder="Escribe tu respuesta aquí...">{{ old('reply_text', $review->replies->first()->reply_text ?? '') }}</textarea>
                            <small class="form-text text-muted">
                                Variables disponibles: {reviewer_name}, {location_name}, {star_rating}
                            </small>
                        </div>

                        @if($aiEnabled ?? false)
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Tono de la respuesta</label>
                            <div class="btn-group btn-group-sm" role="group" id="toneSelector">
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
                        @endif

                        <div class="mb-2 d-flex align-items-center gap-2 flex-wrap">
                            @if($aiEnabled ?? false)
                            <button type="button" class="btn btn-outline-primary btn-sm" id="generate-ai-reply">
                                <i class="fas fa-robot"></i> Generar con IA
                            </button>
                            <span id="ai-loading" class="text-muted d-none">
                                <i class="fas fa-spinner fa-spin"></i> Generando...
                            </span>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" id="save-draft">
                                <i class="fas fa-save"></i> Guardar borrador
                            </button>
                            @can('reviews.replies.approve')
                                <button type="button" class="btn btn-warning" id="approve-reply">
                                    <i class="fas fa-check"></i> Aprobar
                                </button>
                            @endcan
                            @can('reviews.replies.publish')
                                <button type="button" class="btn btn-success" id="publish-reply">
                                    <i class="fab fa-google"></i> Publicar en Google
                                </button>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Moderation Panel -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Moderación</h6>
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
                                <label class="form-check-label" for="is_visible">
                                    Visible en plataforma pública
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="is_featured"
                                       name="is_featured"
                                       value="1"
                                       {{ $review->moderation?->is_featured ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">
                                    Destacada
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Etiquetas</label>
                            <select class="form-select select2"
                                    id="tags"
                                    name="tags[]"
                                    multiple="multiple">
                                @foreach($review->moderation?->tags ?? [] as $tag)
                                    <option value="{{ $tag }}" selected>{{ $tag }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Escriba para agregar nuevas etiquetas o seleccione existentes</small>
                        </div>

                        <div class="mb-3">
                            <label for="internal_notes" class="form-label">Notas internas</label>
                            <textarea class="form-control"
                                      id="internal_notes"
                                      name="internal_notes"
                                      rows="3"
                                      placeholder="Notas solo visibles para el equipo...">{{ $review->moderation?->internal_notes }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Guardar moderación
                        </button>
                    </form>
                </div>
            </div>

            <!-- Review Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Sincronizada:</small><br>
                        <strong>{{ $review->created_at->diffForHumans() }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Última actualización:</small><br>
                        <strong>{{ $review->updated_at->diffForHumans() }}</strong>
                    </div>
                    @if($review->google_review_id)
                        <div class="mb-2">
                            <small class="text-muted">ID Google:</small><br>
                            <code class="small">{{ Str::limit($review->google_review_id, 20) }}</code>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('reviews.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
        <button type="button"
                class="btn btn-outline-danger btn-sm report-review-btn"
                data-review-id="{{ $review->id }}"
                title="Reportar reseña a Google">
            <i class="fas fa-flag"></i> Reportar
        </button>
    </div>

    <!-- Report Review Modal -->
    <div class="modal fade" id="reportReviewModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reportar reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Reportar como inapropiada a Google</p>
                    @foreach(['SPAM' => 'Spam', 'FAKE_REVIEW' => 'Reseña falsa', 'HATE_SPEECH' => 'Discurso de odio', 'HARASSMENT' => 'Acoso', 'OTHER' => 'Otro'] as $value => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="report_reason" value="{{ $value }}" id="reason_{{ $value }}">
                        <label class="form-check-label" for="reason_{{ $value }}">{{ $label }}</label>
                    </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm" id="submitReportBtn">Reportar</button>
                </div>
            </div>
        </div>
    </div>

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
    // Initialize Select2 for tags
    $('#tags').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Agregar etiquetas...',
        ajax: {
            url: '{{ route("reviews.tags.list") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.tags.map(function(tag) {
                        return { id: tag, text: tag };
                    })
                };
            },
            cache: true
        }
    });
    const reviewId = {{ $review->id }};
    const reviewerName = '{{ $review->reviewer_name }}';
    const locationName = '{{ $review->location->name }}';
    const starRating = '{{ $review->star_rating }}';

    // Cargar plantilla
    $('#template-select').on('change', function() {
        const body = $(this).find(':selected').data('body');
        if (body) {
            let text = body
                .replace('{reviewer_name}', reviewerName)
                .replace('{location_name}', locationName)
                .replace('{star_rating}', starRating);
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
        if (confirm('¿Publicar esta respuesta en Google? Esta acción no se puede deshacer.')) {
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

    // Traducir comentario con DeepL
    @if($review->comment)
    let translationVisible = false;

    $('#translate-comment-btn').on('click', function() {
        const btn = $(this);

        if (translationVisible) {
            // Toggle back to original
            $('#translated-comment-block').addClass('d-none');
            btn.html('<i class="fas fa-language"></i> Traducir');
            translationVisible = false;
            return;
        }

        // Show cached translation if available
        const cachedText = $('#translated-comment-text').text();
        if (cachedText) {
            $('#translated-comment-block').removeClass('d-none');
            btn.html('<i class="fas fa-language"></i> Ver original');
            translationVisible = true;
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traduciendo...');

        $.ajax({
            url: `/reviews/${reviewId}/translate`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                const langNames = {
                    'EN': 'inglés', 'ES': 'español', 'PT': 'portugués', 'FR': 'francés',
                    'DE': 'alemán', 'IT': 'italiano', 'NL': 'neerlandés', 'PL': 'polaco',
                    'RU': 'ruso', 'JA': 'japonés', 'ZH': 'chino',
                };
                const detectedLang = response.detected_lang;
                const langLabel = detectedLang ? (langNames[detectedLang] || detectedLang.toLowerCase()) : null;

                if (langLabel) {
                    $('#detected-lang-badge').text(`Traducido del ${langLabel}`).removeClass('d-none');
                }

                $('#translated-comment-text').text(response.translated);
                $('#translated-comment-block').removeClass('d-none');
                btn.html('<i class="fas fa-language"></i> Ver original');
                translationVisible = true;
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al traducir');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
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
