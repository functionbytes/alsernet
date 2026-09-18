<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label class="form-label">Nombre <span class="text-brand">*</span></label>
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
        <label class="form-label">Visibilidad <span class="text-brand">*</span></label>
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

    {{-- Idioma: filtra/ordena el macro en el picker del inbox segun el idioma
         del contacto (helpdesk_customers.language). "Todos los idiomas" = macro
         generico (acciones, no texto redactado) que siempre aparece arriba. --}}
    <div class="col-12">
        <label class="form-label">Idioma</label>
        <select name="language" class="form-select @error('language') is-invalid @enderror">
            <option value="" @selected(old('language', $macro->language ?? '') === '')>Todos los idiomas</option>
            <option value="es" @selected(old('language', $macro->language ?? '') === 'es')>Español</option>
            <option value="en" @selected(old('language', $macro->language ?? '') === 'en')>Inglés</option>
            <option value="fr" @selected(old('language', $macro->language ?? '') === 'fr')>Francés</option>
            <option value="pt" @selected(old('language', $macro->language ?? '') === 'pt')>Portugués</option>
            <option value="de" @selected(old('language', $macro->language ?? '') === 'de')>Alemán</option>
            <option value="it" @selected(old('language', $macro->language ?? '') === 'it')>Italiano</option>
        </select>
        <div class="form-text">Si el macro redacta texto para el cliente (ej. "Enviar respuesta"), elige el idioma en que esta escrito</div>
        @error('language')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Actions builder --}}
    <div class="col-12">
        <label class="form-label">Acciones del macro <span class="text-brand">*</span></label>
        <div class="form-text mb-2">Define las acciones que se ejecutaran al aplicar este macro</div>

        @error('actions')
            <div class="text-dark small mb-2">{{ $message }}</div>
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
            Agregar accion
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
@php
    $hdMacroFormConfig = [
    'lookupUrls' => [
        'agents' => route('settings.helpdesk.macros.lookup.agents'),
        'groups' => route('settings.helpdesk.macros.lookup.groups'),
        'tags' => route('settings.helpdesk.macros.lookup.tags'),
    ],
    'initialRowIndex' => count($actionsToRender ?? []) > 0 ? count($actionsToRender ?? []) : 1
];
@endphp
window.HdMacroFormConfig = @json($hdMacroFormConfig);
</script>
<script>window.HdSettingsCommonSkipAutoInit = true;</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/macros-form.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/macros-form.js')) }}" defer></script>
@endpush
