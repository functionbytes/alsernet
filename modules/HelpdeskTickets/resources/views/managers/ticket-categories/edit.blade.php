@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formCategory" method="POST" action="{{ route('manager.helpdesk.settings.ticket-categories.update', $category->id) }}">

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar categoría</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica la configuración de esta categoría. Los cambios afectarán cómo se clasifican y asignan los tickets.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted mb-3">Define el nombre, identificador y características visuales de la categoría.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required placeholder="Ej: Soporte Técnico">
                            <small class="form-text text-muted">Nombre visible de la categoría</small>
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
                            <input type="text" name="slug" class="form-control bg-light" value="{{ old('slug', $category->slug) }}" readonly>
                            <small class="form-text text-muted">El slug no se puede modificar una vez creado</small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Icono (Tabler Icons)</label>
                            <input type="text" name="icon" class="form-control" value="{{ old('icon', $category->icon) }}" placeholder="Ej: ti-headset">
                            <small class="form-text text-muted">Usa el nombre del icono de <a href="https://tabler.io/icons" target="_blank">Tabler Icons</a></small>
                            @error('icon')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $category->color) }}" id="colorPicker">
                                <input type="text" id="colorHex" class="form-control" value="{{ old('color', $category->color) }}" readonly style="max-width: 120px;">
                                <div id="colorPreview" class="border rounded" style="width: 50px; height: 50px; background-color: {{ old('color', $category->color) }};"></div>
                            </div>
                            <small class="form-text text-muted">Color de identificación de la categoría</small>
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
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Descripción de la categoría">{{ old('description', $category->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre esta categoría</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- SLA and Assignment -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de SLA y asignación</h6>
                        <p class="text-muted mb-3">Define la política de tiempo de respuesta y los grupos asignados a esta categoría.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Política SLA Por defecto</label>
                            <select name="default_sla_policy" class="form-select">
                                <option value="">Sin política SLA</option>
                                @foreach($slaPolicies ?? [] as $policy)
                                    <option value="{{ $policy->id }}" {{ old('default_sla_policy', $category->default_sla_policy) == $policy->id ? 'selected' : '' }}>
                                        {{ $policy->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Política de tiempo de respuesta para esta categoría</small>
                            @error('default_sla_policy')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Grupos asignados</label>
                            <select name="groups[]" class="form-select" multiple size="5">
                                @foreach($groups ?? [] as $group)
                                    <option value="{{ $group->id }}" {{ in_array($group->id, old('groups', $category->groups ?? [])) ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Grupos que pueden gestionar esta categoría (mantén Ctrl/Cmd para seleccionar múltiples)</small>
                            @error('groups')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Custom Form Fields -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Campos personalizados del formulario</h6>
                        <p class="text-muted mb-3">Define campos adicionales específicos para esta categoría (formato JSON).</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Campos personalizados (JSON)</label>
                            <textarea name="custom_form_fields" class="form-control font-monospace" rows="8" placeholder='[{"name": "field_name", "type": "text", "label": "Field Label", "required": true}]'>{{ old('custom_form_fields', is_array($category->custom_form_fields) ? json_encode($category->custom_form_fields, JSON_PRETTY_PRINT) : $category->custom_form_fields) }}</textarea>
                            <small class="form-text text-muted">Formato: Array JSON con objetos que tengan name, type, label y opciones</small>
                            @error('custom_form_fields')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Campos requeridos</label>
                            <select name="required_fields[]" class="form-select" multiple size="6">
                                <option value="subject" {{ in_array('subject', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Asunto</option>
                                <option value="description" {{ in_array('description', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Descripción</option>
                                <option value="priority" {{ in_array('priority', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Prioridad</option>
                                <option value="customer_email" {{ in_array('customer_email', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Email del cliente</option>
                                <option value="customer_phone" {{ in_array('customer_phone', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Teléfono del cliente</option>
                                <option value="attachments" {{ in_array('attachments', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Archivos adjuntos</option>
                            </select>
                            <small class="form-text text-muted">Campos obligatorios al crear un ticket de esta categoría</small>
                            @error('required_fields')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Respuestas predefinidas</label>
                            <select name="canned_replies[]" class="form-select" multiple size="6">
                                @foreach($cannedReplies ?? [] as $reply)
                                    <option value="{{ $reply->id }}" {{ in_array($reply->id, old('canned_replies', $category->canned_replies ?? [])) ? 'selected' : '' }}>
                                        {{ $reply->title }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Respuestas predefinidas disponibles para esta categoría</small>
                            @error('canned_replies')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted mb-3">Configura el comportamiento de la categoría en el sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Categoría activa</strong>
                                    <small class="d-block text-muted">Permite que esta categoría esté disponible para crear tickets.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_default" value="0">
                                <input type="checkbox" name="is_default" class="form-check-input" id="defaultCheck" value="1" {{ old('is_default', $category->is_default) ? 'checked' : '' }}>
                                <label class="form-check-label" for="defaultCheck">
                                    <strong>Categoría Por defecto</strong>
                                    <small class="d-block text-muted">Se selecciona automáticamente al crear nuevos tickets.</small>
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
                <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
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
        toastr.success('{{ session('success') }}', 'Categoría actualizada');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection
