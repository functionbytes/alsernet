@extends('layouts.theme')

@section('title', 'Nueva categoría de ticket')

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('manager.helpdesk.backups.tickets.categories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
        <div>
            <h4 class="mb-0 fw-bold">Nueva categoría de ticket</h4>
            <p class="text-muted mb-0">Crea una categoría para clasificar los tickets</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.categories.store') }}">
            @csrf

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12">
                        <h6 class="fw-semibold text-uppercase text-muted">Informacion basica</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Ej: Soporte Tecnico" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Slug <small class="text-muted fw-normal">(opcional)</small></label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug') }}" placeholder="soporte-tecnico">
                        <div class="form-text">Se genera automáticamente si se deja vacío.</div>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="2" placeholder="Describe el alcance de esta categoría">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-12">
                        <h6 class="fw-semibold text-uppercase text-muted">Apariencia</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Icono <small class="text-muted fw-normal">(clase FontAwesome)</small></label>
                        <div class="input-group">
                            <span class="input-group-text" id="iconPreview">
                                <i class="{{ old('icon', 'fas fa-tag') }}"></i>
                            </span>
                            <input type="text" name="icon" id="iconInput"
                                   class="form-control @error('icon') is-invalid @enderror"
                                   value="{{ old('icon', 'fas fa-tag') }}" placeholder="fas fa-tag">
                        </div>
                        <div class="form-text">Ej: fas fa-headset, fas fa-bug, fas fa-laptop</div>
                        @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Color</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="color" id="colorPicker"
                                   class="form-control form-control-color @error('color') is-invalid @enderror"
                                   value="{{ old('color', '#90bb13') }}">
                            <input type="text" id="colorHex" class="form-control" style="max-width:110px;"
                                   value="{{ old('color', '#90bb13') }}" readonly>
                            <div id="colorPreview" class="border rounded"
                                 style="width:40px;height:40px;background-color:{{ old('color', '#90bb13') }};"></div>
                        </div>
                        @error('color')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Colores sugeridos</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(['#90bb13','#13C672','#FA896B','#FEC90F','#539BFF','#8E44AD','#E74C3C','#95A5A6'] as $c)
                                <button type="button" class="btn btn-sm color-preset rounded"
                                        data-color="{{ $c }}"
                                        style="background-color:{{ $c }};width:36px;height:36px;"></button>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" class="form-check-input" id="active"
                                   value="1" {{ old('active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                <strong>Activa</strong>
                                <small class="d-block text-muted">Disponible para nuevos tickets</small>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
                <a href="{{ route('manager.helpdesk.backups.tickets.categories.index') }}" class="btn btn-secondary px-4">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('input[name="name"]').on('input', function () {
        if (!$('input[name="slug"]').val()) {
            $('input[name="slug"]').val(
                $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
            );
        }
    });

    $('#iconInput').on('input', function () {
        $('#iconPreview i').attr('class', $(this).val() || 'fas fa-tag');
    });

    $('#colorPicker').on('input', function () {
        const color = $(this).val();
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    $('.color-preset').on('click', function () {
        const color = $(this).data('color');
        $('#colorPicker').val(color);
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });
});
</script>
@endpush
