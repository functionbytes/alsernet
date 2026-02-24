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
                        <div class="mb-3">
                            <p class="mb-0">{{ $review->comment }}</p>
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
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>Última respuesta:</strong>
                                    @if($lastReply->status === 'unanswered')
                                        <span class="badge bg-danger-subtle text-danger">Sin responder</span>
                                    @elseif($lastReply->status === 'draft')
                                        <span class="badge bg-warning-subtle text-warning">Borrador</span>
                                    @elseif($lastReply->status === 'approved')
                                        <span class="badge bg-primary-subtle text-primary">Aprobada</span>
                                    @elseif($lastReply->status === 'published')
                                        <span class="badge bg-success-subtle text-success">Publicada</span>
                                    @elseif($lastReply->status === 'failed')
                                        <span class="badge bg-secondary">Error</span>
                                    @endif
                                    @if($lastReply->published_at)
                                        <br><small>Publicada el {{ $lastReply->published_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                </div>
                            </div>
                            @if($lastReply->reply_text)
                                <div class="mt-2 p-2 bg-light rounded">
                                    {{ $lastReply->reply_text }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <form id="reply-form">
                        <div class="mb-3">
                            <label for="template-select" class="form-label">Plantilla (opcional)</label>
                            <select class="form-select" id="template-select">
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
                            <input type="text"
                                   class="form-control"
                                   id="tags"
                                   name="tags"
                                   value="{{ implode(',', $review->moderation?->tags ?? []) }}"
                                   placeholder="servicio, logistica, calidad">
                            <small class="form-text text-muted">Separar con comas</small>
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

    <div class="mt-3">
        <a href="{{ route('reviews.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('modules/reviews/js/reply-editor.js') }}"></script>
<script>
$(document).ready(function() {
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

    // Guardar moderación
    $('#moderation-form').on('submit', function(e) {
        e.preventDefault();

        const tags = $('#tags').val().split(',').map(t => t.trim()).filter(t => t);

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
});
</script>
@endpush
