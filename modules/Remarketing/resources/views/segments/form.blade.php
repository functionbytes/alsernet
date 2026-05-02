@extends('layouts.theme')

@section('title', isset($segment) ? 'Editar segmento' : 'Crear segmento')

@section('page_header')
    @include('core::components.card', ['title' => isset($segment) ? 'Editar segmento' : 'Crear segmento'])
@endsection

@section('content')

    @include('core::components.alerts')

    <form action="{{ isset($segment) ? route('remarketing.segments.update', $segment) : route('remarketing.segments.store') }}"
          method="POST" id="segmentForm">
        @csrf
        @if(isset($segment)) @method('PUT') @endif
        <input type="hidden" name="conditions" id="conditionsInput">

        <div class="row g-3">

            {{-- Main column --}}
            <div class="col-12 col-lg-8">

                {{-- Basic data --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Datos básicos</h5>
                        <small class="text-muted">Nombre e información general del segmento</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Nombre del segmento <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $segment->name ?? '') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="description" rows="2"
                                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $segment->description ?? '') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select name="type" id="segmentType" class="form-select @error('type') is-invalid @enderror">
                                    <option value="dynamic" {{ old('type', $segment->type ?? 'dynamic') === 'dynamic' ? 'selected' : '' }}>Dinámico (condiciones)</option>
                                    <option value="static"  {{ old('type', $segment->type ?? '') === 'static'  ? 'selected' : '' }}>Estático (manual)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tienda <span class="text-danger">*</span></label>
                                <select name="store_id" class="form-select @error('store_id') is-invalid @enderror">
                                    <option value="">Selecciona una tienda</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ old('store_id', $segment->store_id ?? '') == $store->id ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('store_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Condition builder (only for dynamic) --}}
                <div class="card mb-3" id="conditionBuilderCard">
                    <div class="card-header border-bottom p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">Constructor de condiciones</h5>
                            <small class="text-muted">Define las reglas que determinan quién entra en este segmento</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Operador global:</span>
                            <select id="globalOperator" class="form-select form-select-sm" style="width:80px">
                                <option value="and">AND</option>
                                <option value="or">OR</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="builder-root">
                            {{-- Groups and conditions rendered here --}}
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" id="btn-add-condition" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> Añadir condición
                            </button>
                            <button type="button" id="btn-add-group" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-layer-group me-1"></i> Añadir grupo
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Side help --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tipos de condición</h6>
                        <dl class="small mb-0">
                            <dt class="text-muted">Propiedad</dt>
                            <dd>Filtra por atributos del perfil del cliente (email, país, estado, gasto total).</dd>
                            <dt class="text-muted">Evento</dt>
                            <dd>Filtra por acciones que el cliente ha realizado o no (vistas, compras).</dd>
                            <dt class="text-muted">RFM</dt>
                            <dd>Filtra por segmento o puntuación RFM (recencia, frecuencia, monetario).</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Acciones</h6>
                        <button type="submit" class="btn btn-primary w-100 mb-2" id="btn-save-segment">
                            <i class="fas fa-save me-1"></i>
                            {{ isset($segment) ? 'Guardar cambios' : 'Crear segmento' }}
                        </button>
                        <a href="{{ route('remarketing.segments.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </div>
            </div>

        </div>

    </form>

    {{-- Hidden templates for cloning --}}
    <div id="tmpl-condition-row" class="d-none">
        @include('remarketing::partials.condition-row')
    </div>
    <div id="tmpl-condition-group" class="d-none">
        @include('remarketing::partials.condition-group')
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    var builderRoot    = $('#builder-root');
    var rootConditions = []; // flat conditions at root level
    var groups         = []; // sub-groups

    // Show/hide builder based on segment type
    function toggleBuilder() {
        if ($('#segmentType').val() === 'dynamic') {
            $('#conditionBuilderCard').removeClass('d-none');
        } else {
            $('#conditionBuilderCard').addClass('d-none');
        }
    }
    $('#segmentType').on('change', toggleBuilder);
    toggleBuilder();

    // Add flat condition at root
    $('#btn-add-condition').on('click', function () {
        addConditionRow(builderRoot, null);
    });

    // Add a group
    $('#btn-add-group').on('click', function () {
        addGroup();
    });

    function addConditionRow(container, groupIndex) {
        var $row = $('#tmpl-condition-row .condition-row').clone();
        var idx = Date.now() + Math.random();
        $row.attr('data-index', idx);
        if (groupIndex !== null) {
            $row.attr('data-group', groupIndex);
        }
        container.append($row);
    }

    function addGroup() {
        var $group = $('#tmpl-condition-group .condition-group').clone();
        var gIdx = Date.now() + Math.random();
        $group.attr('data-group-index', gIdx);
        builderRoot.append($group);
        // Add one starter condition inside the group
        addConditionRow($group.find('.conditions-container'), gIdx);
    }

    // Remove a flat condition
    $(document).on('click', '.btn-remove-condition', function () {
        $(this).closest('.condition-row').remove();
    });

    // Remove a group
    $(document).on('click', '.btn-remove-group', function () {
        $(this).closest('.condition-group').remove();
    });

    // Add condition inside a group
    $(document).on('click', '.btn-add-condition-in-group', function () {
        var $group = $(this).closest('.condition-group');
        var gIdx = $group.attr('data-group-index');
        addConditionRow($group.find('.conditions-container'), gIdx);
    });

    // Serialize builder to JSON before submit
    $('#segmentForm').on('submit', function () {
        var tree = {
            operator: $('#globalOperator').val(),
            conditions: [],
            groups: []
        };

        // Root-level conditions (not inside a group)
        builderRoot.find('> .condition-row').each(function () {
            tree.conditions.push(extractCondition($(this)));
        });

        // Groups
        builderRoot.find('.condition-group').each(function () {
            var $g = $(this);
            var group = {
                operator: $g.find('.group-operator').val(),
                conditions: []
            };
            $g.find('.condition-row').each(function () {
                group.conditions.push(extractCondition($(this)));
            });
            tree.groups.push(group);
        });

        $('#conditionsInput').val(JSON.stringify(tree));
    });

    function extractCondition($row) {
        return {
            type:     $row.find('.condition-type').val(),
            field:    $row.find('.condition-field').val(),
            operator: $row.find('.condition-operator').val(),
            value:    $row.find('.condition-value').val()
        };
    }

    // Load existing conditions on edit
    @if(isset($segment) && $segment->conditions)
        loadConditions(@json($segment->conditions));
    @else
        // Start with one empty condition
        addConditionRow(builderRoot, null);
    @endif

    function loadConditions(tree) {
        if (!tree) return;
        $('#globalOperator').val(tree.operator || 'and');

        (tree.conditions || []).forEach(function (c) {
            addConditionRow(builderRoot, null);
            var $row = builderRoot.find('> .condition-row').last();
            $row.find('.condition-type').val(c.type);
            $row.find('.condition-field').val(c.field);
            $row.find('.condition-operator').val(c.operator);
            $row.find('.condition-value').val(c.value);
        });

        (tree.groups || []).forEach(function (g) {
            addGroup();
            var $group = builderRoot.find('.condition-group').last();
            $group.find('.group-operator').val(g.operator || 'and');
            // Remove the starter condition, then load group's conditions
            $group.find('.conditions-container .condition-row').remove();
            (g.conditions || []).forEach(function (c) {
                var gIdx = $group.attr('data-group-index');
                addConditionRow($group.find('.conditions-container'), gIdx);
                var $row = $group.find('.condition-row').last();
                $row.find('.condition-type').val(c.type);
                $row.find('.condition-field').val(c.field);
                $row.find('.condition-operator').val(c.operator);
                $row.find('.condition-value').val(c.value);
            });
        });
    }

});
</script>
@endpush
