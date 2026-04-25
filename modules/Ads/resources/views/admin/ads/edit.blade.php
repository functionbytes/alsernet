@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h5 class="mb-0 fw-bold">{{ $pageTitle }}</h5>
            <p class="small mb-0 text-muted">Editar banner publicitario</p>
        </div>

        <form method="POST" action="{{ route('ads.update', $ad) }}">
            @csrf
            @method('PUT')

            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $ad->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Clave única <span class="text-danger">*</span></label>
                        <input type="text" name="key" class="form-control @error('key') is-invalid @enderror"
                               value="{{ old('key', $ad->key) }}" required>
                        @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select name="ads_type" class="form-select @error('ads_type') is-invalid @enderror">
                            @foreach($types as $type)
                                <option value="{{ $type->value }}" {{ old('ads_type', $ad->ads_type->value) === $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('ads_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" {{ old('status', $ad->status->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Orden</label>
                        <input type="number" name="order" class="form-control @error('order') is-invalid @enderror"
                               value="{{ old('order', $ad->order) }}" min="0">
                        @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ubicación</label>
                        <input type="text" name="location" class="form-control @error('location') is-invalid @enderror"
                               value="{{ old('location', $ad->location) }}">
                        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">URL de destino</label>
                        <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                               value="{{ old('url', $ad->url) }}">
                        @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fecha de expiración</label>
                        <input type="date" name="expired_at" class="form-control @error('expired_at') is-invalid @enderror"
                               value="{{ old('expired_at', $ad->expired_at?->format('Y-m-d')) }}">
                        @error('expired_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="open_in_new_tab" value="1" id="openNewTab" {{ old('open_in_new_tab', $ad->open_in_new_tab) ? 'checked' : '' }}>
                            <label class="form-check-label" for="openNewTab">Abrir en nueva pestaña</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Imagen desktop</label>
                        <input type="text" name="image" class="form-control @error('image') is-invalid @enderror"
                               value="{{ old('image', $ad->image) }}">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Imagen tablet</label>
                        <input type="text" name="tablet_image" class="form-control @error('tablet_image') is-invalid @enderror"
                               value="{{ old('tablet_image', $ad->tablet_image) }}">
                        @error('tablet_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Imagen móvil</label>
                        <input type="text" name="mobile_image" class="form-control @error('mobile_image') is-invalid @enderror"
                               value="{{ old('mobile_image', $ad->mobile_image) }}">
                        @error('mobile_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 {{ old('ads_type', $ad->ads_type->value) !== 'google_adsense' ? 'd-none' : '' }}" id="adsenseSlotField">
                        <label class="form-label fw-semibold">Google AdSense Slot ID</label>
                        <input type="text" name="google_adsense_slot_id" class="form-control @error('google_adsense_slot_id') is-invalid @enderror"
                               value="{{ old('google_adsense_slot_id', $ad->google_adsense_slot_id) }}">
                        @error('google_adsense_slot_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Actualizar anuncio
                </button>
                <a href="{{ route('ads.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function toggleAdsenseField() {
        if ($('select[name="ads_type"]').val() === 'google_adsense') {
            $('#adsenseSlotField').removeClass('d-none');
        } else {
            $('#adsenseSlotField').addClass('d-none');
        }
    }

    $('select[name="ads_type"]').on('change', toggleAdsenseField);
    toggleAdsenseField();
});
</script>
@endpush
