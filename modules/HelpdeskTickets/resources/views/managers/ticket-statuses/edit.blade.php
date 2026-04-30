@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formStatus" method="POST" action="{{ route('manager.helpdesk.settings.ticket-statuses.update', $status->id) }}">

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar estado de ticket</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica la configuración de este estado. Los cambios afectarán cómo se muestra y comporta en el sistema.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información del estado</h6>
                        <p class="text-muted mb-3">Define el nombre y las características básicas del estado. El nombre será visible para los usuarios, mientras que el slug se usa internamente en el sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $status->name) }}" required placeholder="Ej: En Progreso">
                            <small class="form-text text-muted">Nombre visible del estado</small>
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
                            <input type="text" name="slug" class="form-control bg-light" value="{{ old('slug', $status->slug) }}" readonly>
                            <small class="form-text text-muted">El slug no se puede modificar una vez creado</small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional del estado">{{ old('description', $status->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este estado</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Color Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de color</h6>
                        <p class="text-muted mb-3">El color ayuda a identificar visualmente el estado en la interfaz. Selecciona un color que represente claramente el tipo de estado (ej: verde para completado, rojo para cancelado).</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $status->color) }}" id="colorPicker">
                                <input type="text" id="colorHex" class="form-control" value="{{ old('color', $status->color) }}" readonly style="max-width: 120px;">
                                <div id="colorPreview" class="border rounded" style="width: 50px; height: 50px; background-color: {{ old('color', $status->color) }};"></div>
                            </div>
                            <small class="form-text text-muted">Color de identificación del estado</small>
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

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración del estado</h6>
                        <p class="text-muted mb-3">Configura el comportamiento del estado en el sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_open" value="0">
                                <input type="checkbox" name="is_open" class="form-check-input" id="isOpenCheck" value="1" {{ old('is_open', $status->is_open) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isOpenCheck">
                                    <strong>Estado abierto</strong>
                                    <small class="d-block text-muted">Los tickets en este estado se consideran activos y en proceso. Los estados cerrados representan tickets finalizados.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_default" value="0">
                                <input type="checkbox" name="is_default" class="form-check-input" id="defaultCheck" value="1" {{ old('is_default', $status->is_default) ? 'checked' : '' }}>
                                <label class="form-check-label" for="defaultCheck">
                                    <strong>Estado Por defecto</strong>
                                    <small class="d-block text-muted">Se asigna automáticamente a todos los tickets nuevos que se creen en el sistema. Solo puede haber un estado Por defecto activo.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="stops_sla_timer" value="0">
                                <input type="checkbox" name="stops_sla_timer" class="form-check-input" id="slaPauseCheck" value="1" {{ old('stops_sla_timer', $status->stops_sla_timer) ? 'checked' : '' }}>
                                <label class="form-check-label" for="slaPauseCheck">
                                    <strong>Pausar temporizador SLA</strong>
                                    <small class="d-block text-muted">Cuando un ticket tiene este estado, el tiempo SLA se pausa. Útil para estados como "Esperando al Cliente".</small>
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
                <a href="{{ route('manager.helpdesk.settings.ticket-statuses.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
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

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Estado actualizado');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection
