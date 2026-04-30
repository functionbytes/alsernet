@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formView" method="POST" action="{{ route('manager.helpdesk.settings.ticket-views.store') }}">

            {{ csrf_field() }}

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear nueva vista</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Define una vista personalizada para organizar y filtrar tickets. Las vistas permiten crear filtros predefinidos para acceder rápidamente a grupos específicos de tickets.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted mb-3">Define el nombre y las características básicas de la vista.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: Tickets Urgentes">
                            <small class="form-text text-muted">Nombre visible de la vista</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Slug (Identificador)
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" required placeholder="Ej: urgent_tickets">
                            <small class="form-text text-muted">Solo letras minúsculas, números y guiones</small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional de la vista">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre esta vista</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Visual Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración visual</h6>
                        <p class="text-muted mb-3">Define el icono y color para identificar visualmente la vista en la interfaz.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Icono (Tabler Icons)</label>
                            <input type="text" name="icon" class="form-control" value="{{ old('icon', 'ti-eye') }}" placeholder="Ej: ti-ticket, ti-eye, ti-filter">
                            <small class="form-text text-muted">
                                Clase de icono Tabler Icons. Ver: <a href="https://tabler-icons.io/" target="_blank">tabler-icons.io</a>
                            </small>
                            @error('icon')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', '#b10100') }}" id="colorPicker">
                                <input type="text" id="colorHex" class="form-control" value="{{ old('color', '#b10100') }}" readonly style="max-width: 120px;">
                                <div id="colorPreview" class="border rounded" style="width: 50px; height: 50px; background-color: {{ old('color', '#b10100') }};"></div>
                            </div>
                            <small class="form-text text-muted">Color de identificación de la vista</small>
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
                                <button type="button" class="btn btn-sm color-preset" data-color="#b10100" style="background-color: #b10100; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#13C672" style="background-color: #13C672; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#FA896B" style="background-color: #FA896B; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#FEC90F" style="background-color: #FEC90F; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#539BFF" style="background-color: #539BFF; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#8E44AD" style="background-color: #8E44AD; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#E74C3C" style="background-color: #E74C3C; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#95A5A6" style="background-color: #95A5A6; width: 40px; height: 40px; border-radius: 8px;"></button>
                            </div>
                        </div>
                    </div>

                    <!-- Conditions -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Condiciones de filtrado</h6>
                        <p class="text-muted mb-3">Define los criterios para filtrar los tickets que aparecerán en esta vista.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Condiciones (JSON)</label>
                            <textarea name="conditions" class="form-control font-monospace" rows="8" placeholder='{"status":"open","priority":"high"}'>{{ old('conditions') }}</textarea>
                            <small class="form-text text-muted">
                                Define las condiciones de filtrado en formato JSON. Ejemplo:<br>
                                <code>{"status":"open","priority":"high","assigned_to":null}</code><br>
                                <code>{"created_at":{"from":"2025-01-01","to":"2025-12-31"}}</code>
                            </small>
                            @error('conditions')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Sharing Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones de compartición</h6>
                        <p class="text-muted mb-3">Configura si la vista es visible para todos los usuarios o solo para ti.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_shared" value="0">
                                <input type="checkbox" name="is_shared" class="form-check-input" id="sharedCheck" value="1" {{ old('is_shared') ? 'checked' : '' }}>
                                <label class="form-check-label" for="sharedCheck">
                                    <strong>Vista compartida</strong>
                                    <small class="d-block text-muted">Permite que todos los usuarios vean y usen esta vista</small>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('manager.helpdesk.settings.ticket-views.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-generate slug from name
    $('input[name="name"]').on('input', function() {
        if (!$('input[name="slug"]').val()) {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
            $('input[name="slug"]').val(slug);
        }
    });

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
