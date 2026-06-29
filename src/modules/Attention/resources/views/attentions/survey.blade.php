@extends('layouts.theme')

@section('title', 'Encuesta de Satisfacción')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white p-4 text-center">
                    <i class="fa-duotone fa-star fa-3x mb-3"></i>
                    <h4 class="mb-0 text-white">Encuesta de Satisfacción</h4>
                    <p class="mb-0 small">Su opinión nos ayuda a mejorar nuestros servicios</p>
                </div>

                <div class="card-body p-4">
                    @if(!$attention->satisfaction_rating)
                        <!-- Información del peticiones -->
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Radicado:</strong> {{ $attention->radicado }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Tipo:</strong> {{ $attention->type->name }}
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de calificación -->
                        <form id="surveyForm" method="POST" action="{{ route('attention.survey.store', ['radicado' => $attention->radicado]) }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ request('token') }}">

                            <div class="text-center mb-4">
                                <h5 class="mb-3">¿Cómo califica la atención recibida?</h5>
                                <div class="rating-stars mb-3">
                                    <input type="radio" name="satisfaction_rating" value="5" id="star5" required>
                                    <label for="star5" title="Excelente">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="satisfaction_rating" value="4" id="star4">
                                    <label for="star4" title="Muy Bueno">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="satisfaction_rating" value="3" id="star3">
                                    <label for="star3" title="Bueno">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="satisfaction_rating" value="2" id="star2">
                                    <label for="star2" title="Regular">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="satisfaction_rating" value="1" id="star1">
                                    <label for="star1" title="Malo">
                                        <i class="fa-solid fa-star"></i>
                                    </label>
                                </div>
                                <div id="ratingText" class="text-muted"></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Comentarios Adicionales (Opcional)</label>
                                <textarea class="form-control" name="comments" rows="4"
                                          placeholder="Cuéntenos sobre su experiencia..."></textarea>
                                <small class="text-muted">
                                    Sus comentarios nos ayudan a identificar áreas de mejora
                                </small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Aspectos a evaluar</label>

                                <div class="mb-2">
                                    <label class="d-block mb-1">Tiempo de respuesta</label>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="response_time" value="excellent" id="time_excellent">
                                        <label class="btn btn-outline-success" for="time_excellent">Excelente</label>

                                        <input type="radio" class="btn-check" name="response_time" value="good" id="time_good">
                                        <label class="btn btn-outline-primary" for="time_good">Bueno</label>

                                        <input type="radio" class="btn-check" name="response_time" value="regular" id="time_regular">
                                        <label class="btn btn-outline-warning" for="time_regular">Regular</label>

                                        <input type="radio" class="btn-check" name="response_time" value="poor" id="time_poor">
                                        <label class="btn btn-outline-danger" for="time_poor">Malo</label>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="d-block mb-1">Calidad de la respuesta</label>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="response_quality" value="excellent" id="quality_excellent">
                                        <label class="btn btn-outline-success" for="quality_excellent">Excelente</label>

                                        <input type="radio" class="btn-check" name="response_quality" value="good" id="quality_good">
                                        <label class="btn btn-outline-primary" for="quality_good">Bueno</label>

                                        <input type="radio" class="btn-check" name="response_quality" value="regular" id="quality_regular">
                                        <label class="btn btn-outline-warning" for="quality_regular">Regular</label>

                                        <input type="radio" class="btn-check" name="response_quality" value="poor" id="quality_poor">
                                        <label class="btn btn-outline-danger" for="quality_poor">Malo</label>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="d-block mb-1">Atención recibida</label>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="attention_quality" value="excellent" id="attention_excellent">
                                        <label class="btn btn-outline-success" for="attention_excellent">Excelente</label>

                                        <input type="radio" class="btn-check" name="attention_quality" value="good" id="attention_good">
                                        <label class="btn btn-outline-primary" for="attention_good">Bueno</label>

                                        <input type="radio" class="btn-check" name="attention_quality" value="regular" id="attention_regular">
                                        <label class="btn btn-outline-warning" for="attention_regular">Regular</label>

                                        <input type="radio" class="btn-check" name="attention_quality" value="poor" id="attention_poor">
                                        <label class="btn btn-outline-danger" for="attention_poor">Malo</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa-duotone fa-paper-plane"></i> Enviar Calificación
                                </button>
                                <a href="{{ route('attention.tracking', ['radicado' => $attention->radicado]) }}"
                                   class="btn btn-outline-secondary">
                                    Volver al Seguimiento
                                </a>
                            </div>
                        </form>
                    @else
                        <!-- Ya fue calificado -->
                        <div class="text-center py-4">
                            <i class="fa-duotone fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="mb-3">Gracias por su calificación</h4>
                            <p class="text-muted mb-4">
                                Usted calificó este servicio con <strong>{{ $attention->satisfaction_rating }} estrellas</strong>
                            </p>

                            <div class="fs-1 text-warning mb-4">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $attention->satisfaction_rating)
                                        <i class="fa-solid fa-star"></i>
                                    @else
                                        <i class="fa-regular fa-star"></i>
                                    @endif
                                @endfor
                            </div>

                            <p class="text-muted">
                                Su opinión es muy valiosa y nos ayuda a mejorar nuestros servicios.
                            </p>

                            <a href="{{ route('attention.tracking', ['radicado' => $attention->radicado]) }}"
                               class="btn btn-primary">
                                <i class="fa-duotone fa-arrow-left"></i> Volver al Seguimiento
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .rating-stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 10px;
        }

        .rating-stars input {
            display: none;
        }

        .rating-stars label {
            cursor: pointer;
            font-size: 3rem;
            color: #ddd;
            transition: color 0.2s;
        }

        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input:checked ~ label {
            color: #ffc107;
        }

        .btn-group {
            width: 100%;
        }

        .btn-group .btn {
            flex: 1;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            const ratingTexts = {
                '5': 'Excelente',
                '4': 'Muy Bueno',
                '3': 'Bueno',
                '2': 'Regular',
                '1': 'Malo'
            };

            $('input[name="satisfaction_rating"]').on('change', function() {
                const value = $(this).val();
                $('#ratingText').text(ratingTexts[value]).addClass('fw-bold fs-5');
            });

            $('#surveyForm').on('submit', function(e) {
                if (!$('input[name="satisfaction_rating"]:checked').length) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Calificación requerida',
                        text: 'Por favor seleccione una calificación con estrellas',
                    });
                    return false;
                }
            });
        });
    </script>
@endpush
