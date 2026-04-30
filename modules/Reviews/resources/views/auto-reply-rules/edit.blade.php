@extends('layouts.theme')

@section('title', 'Editar regla de auto-respuesta')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar regla de auto-respuesta'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('reviews.autoreply.update', $autoReplyRule) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar regla de auto-respuesta</h5>
                        <small class="text-muted">Modifique los criterios de la regla.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('reviews::auto-reply-rules._form', ['rule' => $autoReplyRule])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('reviews.autoreply.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es una regla de auto-respuesta?</h6>
                    <p class="card-text text-muted">
                        Una regla permite responder automáticamente a las reseñas que cumplan las condiciones configuradas, ahorrando tiempo en la gestión de tu reputación.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tipos de condicion</h6>
                    <p class="card-text text-muted mb-0">
                        Puedes filtrar por calificacion de estrellas, si la reseña ya tiene respuesta, o si contiene una palabra clave específica.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Plantilla de respuesta</h6>
                    <p class="card-text text-muted mb-0">
                        Selecciona una plantilla predefinida o deja en blanco para escribir un texto personalizado más adelante.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    function updateConditionFields() {
        var type = $('#condition-type').val();
        $('#rating-fields').toggleClass('d-none', type !== 'star_rating');
        $('#keyword-field').toggleClass('d-none', type !== 'keyword_match');
    }

    $('#condition-type').on('change', updateConditionFields);
    updateConditionFields();
});
</script>
@endpush
