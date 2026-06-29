<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label class="form-label">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $macro->name ?? '') }}"
            placeholder="Ej: Resolver y asignar al equipo tecnico">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description --}}
    <div class="col-12">
        <label class="form-label">Descripcion <span class="text-muted">(opcional)</span></label>
        <textarea name="description" rows="2"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Describe brevemente que hace este macro...">{{ old('description', $macro->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Visibility + active --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Visibilidad <span class="text-danger">*</span></label>
        <select name="visibility" class="form-select @error('visibility') is-invalid @enderror">
            <option value="global" @selected(old('visibility', ($macro->is_shared ?? true) ? 'global' : 'personal') === 'global')>Global</option>
            <option value="personal" @selected(old('visibility', ($macro->is_shared ?? true) ? 'global' : 'personal') === 'personal')>Personal</option>
        </select>
        <div class="form-text">Los macros globales estan disponibles para todos los agentes</div>
        @error('visibility')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Estado</label>
        <div class="form-check mt-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                class="form-check-input"
                @checked(old('is_active', $macro->is_active ?? true))>
            <label class="form-check-label" for="is_active">Macro activo</label>
        </div>
        <div class="form-text">Los macros inactivos no aparecen en la lista de ejecucion</div>
    </div>

    {{-- Actions builder --}}
    <div class="col-12">
        <label class="form-label">Acciones del macro <span class="text-danger">*</span></label>
        <div class="form-text mb-2">Define las acciones que se ejecutaran al aplicar este macro</div>

        @error('actions')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror

        <div id="actionsContainer">
            @php
                $actionsToRender = old('actions', $existingActions ?? []);
            @endphp
            @forelse($actionsToRender as $idx => $action)
                @include('helpdesk::settings.macros._action_row', [
                    'index' => $idx,
                    'actionType' => $action['type'] ?? '',
                    'actionValue' => $action['value'] ?? '',
                ])
            @empty
                @include('helpdesk::settings.macros._action_row', [
                    'index' => 0,
                    'actionType' => '',
                    'actionValue' => '',
                ])
            @endforelse
        </div>

        <button type="button" id="btnAddAction" class="btn btn-sm btn-outline-secondary mt-2">
            <i class="fas fa-plus me-1"></i>Agregar accion
        </button>
    </div>

</div>

{{-- Template for new rows --}}
<template id="action-row-template">
    @include('helpdesk::settings.macros._action_row', [
        'index' => '__INDEX__',
        'actionType' => '',
        'actionValue' => '',
    ])
</template>

@push('scripts')
<script>
$(function () {
    var lookupCache = { agents: null, groups: null, tags: null };

    var lookupUrls = {
        agents: '{{ route("settings.helpdesk.macros.lookup.agents") }}',
        groups: '{{ route("settings.helpdesk.macros.lookup.groups") }}',
        tags:   '{{ route("settings.helpdesk.macros.lookup.tags") }}',
    };

    function fetchLookup(key) {
        if (lookupCache[key]) {
            return $.Deferred().resolve(lookupCache[key]).promise();
        }
        return $.getJSON(lookupUrls[key]).then(function (res) {
            lookupCache[key] = res.data || res;
            return lookupCache[key];
        });
    }

    function renderInput(actionType, currentValue, $placeholder, idx) {
        var name = 'actions[' + idx + '][value]';

        switch (actionType) {
            case 'assign_agent':
                fetchLookup('agents').then(function (agents) {
                    var opts = '<option value="">— Sin asignar —</option>';
                    $.each(agents, function (i, a) {
                        opts += '<option value="' + a.id + '"' + (String(a.id) === String(currentValue) ? ' selected' : '') + '>'
                            + $('<div>').text(a.name).html() + ' (' + $('<div>').text(a.email).html() + ')</option>';
                    });
                    $placeholder.html('<select name="' + name + '" class="form-select form-select-sm">' + opts + '</select>');
                });
                return;

            case 'assign_group':
                fetchLookup('groups').then(function (groups) {
                    var opts = '<option value="">— Sin grupo —</option>';
                    $.each(groups, function (i, g) {
                        opts += '<option value="' + g.id + '"' + (String(g.id) === String(currentValue) ? ' selected' : '') + '>'
                            + $('<div>').text(g.name).html() + '</option>';
                    });
                    $placeholder.html('<select name="' + name + '" class="form-select form-select-sm">' + opts + '</select>');
                });
                return;

            case 'add_tag':
            case 'remove_tag':
                fetchLookup('tags').then(function (tags) {
                    var opts = '<option value="">— Selecciona etiqueta —</option>';
                    $.each(tags, function (i, t) {
                        opts += '<option value="' + t.id + '"' + (String(t.id) === String(currentValue) ? ' selected' : '') + '>'
                            + $('<div>').text(t.name).html() + '</option>';
                    });
                    $placeholder.html('<select name="' + name + '" class="form-select form-select-sm">' + opts + '</select>');
                });
                return;

            case 'change_status':
                $placeholder.html(
                    '<select name="' + name + '" class="form-select form-select-sm">'
                    + '<option value="open"' + (currentValue === 'open' ? ' selected' : '') + '>Abierta</option>'
                    + '<option value="pending"' + (currentValue === 'pending' ? ' selected' : '') + '>Pendiente</option>'
                    + '<option value="resolved"' + (currentValue === 'resolved' ? ' selected' : '') + '>Resuelta</option>'
                    + '</select>'
                );
                return;

            case 'change_priority':
                $placeholder.html(
                    '<select name="' + name + '" class="form-select form-select-sm">'
                    + '<option value="low"' + (currentValue === 'low' ? ' selected' : '') + '>Baja</option>'
                    + '<option value="medium"' + (currentValue === 'medium' ? ' selected' : '') + '>Media</option>'
                    + '<option value="high"' + (currentValue === 'high' ? ' selected' : '') + '>Alta</option>'
                    + '<option value="urgent"' + (currentValue === 'urgent' ? ' selected' : '') + '>Urgente</option>'
                    + '</select>'
                );
                return;

            case 'send_reply':
                $placeholder.html(
                    '<textarea name="' + name + '" class="form-control form-control-sm" rows="3"'
                    + ' placeholder="Hola @{{ contact.name }}, gracias por contactarnos...">'
                    + $('<div>').text(currentValue).html()
                    + '</textarea>'
                    + '<div class="form-text">Variables disponibles: @{{ contact.name }}, @{{ inbox.name }}, @{{ agent.name }}</div>'
                );
                return;

            case 'add_note':
                $placeholder.html(
                    '<textarea name="' + name + '" class="form-control form-control-sm" rows="2"'
                    + ' placeholder="Nota interna para el equipo...">'
                    + $('<div>').text(currentValue).html()
                    + '</textarea>'
                );
                return;

            case 'resolve_conversation':
            case 'close_conversation':
                $placeholder.html(
                    '<input type="hidden" name="' + name + '" value="">'
                    + '<div class="form-control form-control-sm bg-light text-muted fst-italic">Sin parametros — la accion se ejecuta tal cual</div>'
                );
                return;

            default:
                $placeholder.html('<input type="text" class="form-control form-control-sm" disabled placeholder="Selecciona una accion primero">');
        }
    }

    // When action type changes, replace the input area
    $(document).on('change', '.js-action-type', function () {
        var $row = $(this).closest('.action-row');
        var idx = $row.data('index');
        var $placeholder = $row.find('.js-action-input-placeholder');
        renderInput($(this).val(), '', $placeholder, idx);
    });

    // Init existing rows (edit mode — values already embedded as data attrs)
    $('#actionsContainer .action-row').each(function () {
        var $row = $(this);
        var actionType = $row.find('.js-action-type').val();
        if (actionType) {
            var currentValue = $row.find('.js-initial-value').val() || '';
            renderInput(actionType, currentValue, $row.find('.js-action-input-placeholder'), $row.data('index'));
        }
    });

    // Remove action row
    $(document).on('click', '.js-remove-action', function () {
        $(this).closest('.action-row').remove();
    });

    // Add new action row
    var rowIdx = {{ count($actionsToRender ?? []) > 0 ? count($actionsToRender ?? []) : 1 }};
    $('#btnAddAction').on('click', function () {
        var html = $('#action-row-template').html().replace(/__INDEX__/g, rowIdx++);
        $('#actionsContainer').append(html);
    });
});
</script>
@endpush
