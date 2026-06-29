@extends('layouts.theme')

@section('title', 'Editar estado: ' . $status->name)

@push('styles')
<style>
.hd-color-preview { width: 38px; height: 38px; }
.hd-color-preset { width: 32px; height: 32px; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Editar estado'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="statusForm" action="{{ route('settings.helpdesk.statuses.update', $status) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $status->name }}</h5>
                        <small class="text-muted">Modifica las propiedades del estado</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre, slug y descripcion visible del estado</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $status->name) }}"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           value="{{ old('slug', $status->slug) }}">
                                    <small class="form-text text-muted">Se genera automaticamente desde el nombre</small>
                                    @error('slug')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea name="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="3">{{ old('description', $status->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Color</h6>
                        <p class="text-muted small mb-3">Color identificador del estado en listados y badges</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Color <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" name="color" id="colorPicker"
                                               class="form-control form-control-color @error('color') is-invalid @enderror"
                                               value="{{ old('color', $status->color) }}">
                                        <input type="text" id="colorHex" class="form-control"
                                               value="{{ old('color', $status->color) }}" readonly>
                                        <div id="colorPreview" class="hd-color-preview border rounded flex-shrink-0"
                                             data-color="{{ old('color', $status->color) }}"></div>
                                    </div>
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Colores sugeridos</label>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @foreach(['#90bb13','#13C672','#FA896B','#FEC90F','#539BFF','#8E44AD','#E74C3C','#95A5A6'] as $c)
                                            <button type="button" class="btn btn-sm color-preset hd-color-preset rounded-circle border-0"
                                                    data-color="{{ $c }}"
                                                    title="{{ $c }}"></button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Comportamiento</h6>
                        <p class="text-muted small mb-3">Define como se comporta este estado dentro del flujo y SLA</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="is_open" class="form-label">Tipo de estado</label>
                                    <select class="form-select @error('is_open') is-invalid @enderror" id="is_open" name="is_open" required>
                                        <option value="1" {{ old('is_open', $status->is_open ? 1 : 0) == 1 ? 'selected' : '' }}>Abierto — el ticket sigue activo</option>
                                        <option value="0" {{ old('is_open', $status->is_open ? 1 : 0) == 0 ? 'selected' : '' }}>Cerrado — el ticket se considera finalizado</option>
                                    </select>
                                    @error('is_open')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="is_default" class="form-label">Estado por defecto</label>
                                    <select class="form-select @error('is_default') is-invalid @enderror" id="is_default" name="is_default">
                                        <option value="0" {{ old('is_default', $status->is_default ? 1 : 0) == 0 ? 'selected' : '' }}>No — asignacion manual</option>
                                        <option value="1" {{ old('is_default', $status->is_default ? 1 : 0) == 1 ? 'selected' : '' }}>Si — asignado a tickets nuevos</option>
                                    </select>
                                    @error('is_default')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="stops_sla_timer" class="form-label">Temporizador SLA</label>
                                    <select class="form-select @error('stops_sla_timer') is-invalid @enderror" id="stops_sla_timer" name="stops_sla_timer">
                                        <option value="0" {{ old('stops_sla_timer', $status->stops_sla_timer ? 1 : 0) == 0 ? 'selected' : '' }}>No pausa — SLA sigue corriendo</option>
                                        <option value="1" {{ old('stops_sla_timer', $status->stops_sla_timer ? 1 : 0) == 1 ? 'selected' : '' }}>Si pausa — SLA se detiene</option>
                                    </select>
                                    @error('stops_sla_timer')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('settings.helpdesk.statuses.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los estados</h6>
                    <p class="card-text text-muted">
                        Los estados definen el ciclo de vida de un ticket y permiten controlar el flujo de trabajo y el cumplimiento del SLA.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres cortos que reflejen la etapa del ticket</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Asigna colores distintos para facilitar la identificacion visual</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Solo un estado debe ser el predeterminado para tickets nuevos</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa "Pausar SLA" en estados de espera por el cliente</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion del registro</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small">
                            <span class="fw-semibold">Creado:</span> {{ $status->created_at->format('d/m/Y H:i') }}
                        </li>
                        <li class="text-muted small">
                            <span class="fw-semibold">Actualizado:</span> {{ $status->updated_at->format('d/m/Y H:i') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Init color presets and preview
    $('.hd-color-preset[data-color]').each(function () {
        $(this).css('background-color', $(this).data('color'));
    });
    $('#colorPreview[data-color]').each(function () {
        $(this).css('background-color', $(this).data('color'));
    });

    // Color picker sync
    $('#colorPicker').on('input', function () {
        const color = $(this).val();
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    // Color preset buttons
    $(document).on('click', '.color-preset', function () {
        const color = $(this).data('color');
        $('#colorPicker').val(color);
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });
});
</script>
@endpush
