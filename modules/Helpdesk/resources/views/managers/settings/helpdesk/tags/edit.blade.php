@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formTag" method="POST" action="{{ route('manager.helpdesk.backups.tickets.tags.update', $tag->id) }}">

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0">Editar tag</h5>
                        <p class="card-subtitle mb-0 mt-1">
                            Actualiza la información del tag
                        </p>
                    </div>
                    @if($tag->usage_count > 0)
                        <div class="badge bg-info-subtle text-info">
                            Usado en {{ $tag->usage_count }} conversación(es)
                        </div>
                    @endif
                </div>

                <div class="row mt-4">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información del tag</h6>
                        <p class="text-muted mb-3">Define el nombre y las características básicas del tag.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $tag->name) }}" required placeholder="Ej: Urgente">
                            <small class="form-text text-muted">Nombre visible del tag</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Slug (Identificador)
                            </label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $tag->slug) }}" placeholder="Se generará automáticamente">
                            <small class="form-text text-muted">Opcional: Solo letras minúsculas, números y guiones.</small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional del tag">{{ old('description', $tag->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este tag</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Color Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de color</h6>
                        <p class="text-muted mb-3">El color ayuda a identificar visualmente el tag en la interfaz.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $tag->color ?? '#90bb13') }}" id="colorPicker">
                                <input type="text" id="colorHex" class="form-control" value="{{ old('color', $tag->color ?? '#90bb13') }}" readonly style="max-width: 120px;">
                                <div id="colorPreview" class="border rounded" style="width: 50px; height: 50px; background-color: {{ old('color', $tag->color ?? '#90bb13') }};"></div>
                            </div>
                            <small class="form-text text-muted">Color de identificación del tag</small>
                            @error('color')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Colores sugeridos</label>
                            <small class="d-block text-muted mb-2">Haz clic en cualquier color para aplicarlo rápidamente</small>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm color-preset" data-color="#90bb13" style="background-color: #90bb13; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#13C672" style="background-color: #13C672; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#FA896B" style="background-color: #FA896B; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#FEC90F" style="background-color: #FEC90F; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#539BFF" style="background-color: #539BFF; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#8E44AD" style="background-color: #8E44AD; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#E74C3C" style="background-color: #E74C3C; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#95A5A6" style="background-color: #95A5A6; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#FF6B9D" style="background-color: #FF6B9D; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#00D4FF" style="background-color: #00D4FF; width: 40px; height: 40px; border-radius: 8px;"></button>
                            </div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted mb-3">Configura la disponibilidad del tag en el sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', $tag->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Tag activo</strong>
                                    <small class="d-block text-muted">Permite que este tag esté disponible para asignar a conversaciones. Si se desactiva, no aparecerá en las opciones de selección.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Actualizar
                </button>
                <a href="{{ route('manager.helpdesk.backups.tickets.tags.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Color picker sync
    $('#colorPicker').on('input', function() {
        const color = $(this).val();
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    // Color presets
    $('.color-preset').on('click', function() {
        const color = $(this).data('color');
        $('#colorPicker').val(color);
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection
