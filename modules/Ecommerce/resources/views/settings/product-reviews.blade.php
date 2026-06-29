@extends('layouts.theme')

@section('title', 'Reseñas de productos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Reseñas de productos'])
@endsection

@section('content')
    @include('core::components.alerts')

    @php $enabled = old('reviews_enabled', $settings['reviews_enabled'] ?? '') == '1'; @endphp

    <form action="{{ route('settings.ecommerce.product-reviews.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Reseñas de productos</h6>
                    </div>
                    <div class="card-body">

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="reviews_enabled" value="1" id="reviews_enabled"
                                {{ $enabled ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="reviews_enabled">Habilitar revisión</label>
                        </div>

                        <div id="reviews-extra-block" style="{{ $enabled ? '' : 'display:none' }}">
                            <div class="mb-3">
                                <label for="review_max_file_size" class="form-label">Revisar el tamaño máximo de archivo (MB)</label>
                                <input type="number" name="review_max_file_size" id="review_max_file_size" min="0"
                                    class="form-control @error('review_max_file_size') is-invalid @enderror"
                                    value="{{ old('review_max_file_size', $settings['review_max_file_size'] ?? '') }}">
                                @error('review_max_file_size')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="review_max_files" class="form-label">Revisar el número máximo de archivos</label>
                                <input type="number" name="review_max_files" id="review_max_files" min="1"
                                    class="form-control @error('review_max_files') is-invalid @enderror"
                                    value="{{ old('review_max_files', $settings['review_max_files'] ?? '') }}">
                                @error('review_max_files')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="review_buyers_only" value="1" id="review_buyers_only"
                                    {{ old('review_buyers_only', $settings['review_buyers_only'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="review_buyers_only">Solo los clientes que hayan comprado el producto pueden opinar sobre el producto.</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="review_needs_approval" value="1" id="review_needs_approval"
                                    {{ old('review_needs_approval', $settings['review_needs_approval'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="review_needs_approval">La revisión debe aprobarse antes de mostrarse en la página del producto.</label>
                            </div>

                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="review_show_full_name" value="1" id="review_show_full_name"
                                    {{ old('review_show_full_name', $settings['review_show_full_name'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="review_show_full_name">Mostrar el nombre completo del cliente</label>
                                <div class="form-text">Si no está marcado, el nombre del cliente que revisa se ocultará y se reemplazará con asteriscos (***)</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">
                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Guardar</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">Guardar cambios</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#reviews_enabled').on('change', function () {
        $('#reviews-extra-block').slideToggle(150);
    });
});
</script>
@endpush
