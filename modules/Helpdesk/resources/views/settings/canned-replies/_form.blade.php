<div class="row g-3">

    {{-- Titulo --}}
    <div class="col-12">
        <label class="form-label">
            Titulo <span class="text-brand">*</span>
        </label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
            value="{{ old('title', $cannedReply->title ?? '') }}"
            placeholder="Ej: Saludo inicial al cliente">
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Atajo --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Atajo</label>
        <div class="input-group">
            <span class="input-group-text">/</span>
            <input type="text" name="shortcut" class="form-control @error('shortcut') is-invalid @enderror"
                value="{{ old('shortcut', $cannedReply->shortcut ?? '') }}"
                placeholder="saludo-inicial">
        </div>
        <div class="form-text">Escribe <code>/atajo</code> en la conversacion para autocompletar rapidamente</div>
        @error('shortcut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Categoria --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Categoria</label>
        <input type="text" name="category" class="form-control @error('category') is-invalid @enderror"
            value="{{ old('category', $cannedReply->category ?? '') }}"
            placeholder="Ej: Soporte tecnico">
        <div class="form-text">Agrupa respuestas relacionadas bajo la misma categoria</div>
        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Disponible: select y no checkbox, para que acompane a Categoria con el
         mismo control que el resto de campos del formulario. --}}
    <div class="col-12">
        @php($cnIsGlobal = (int) old('is_global', ($cannedReply->is_global ?? false) ? 1 : 0))
        <label class="form-label" for="is_global">Disponible</label>
        <select name="is_global" id="is_global" class="form-select select2 @error('is_global') is-invalid @enderror">
            <option value="1" @selected($cnIsGlobal === 1)>Todos los agentes</option>
            <option value="0" @selected($cnIsGlobal === 0)>Solo yo</option>
        </select>
        <div class="form-text">Con "Solo yo" nadie mas podra ver ni usar esta respuesta</div>
        @error('is_global')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Cuerpo --}}
    <div class="col-12">
        <label class="form-label">
            Cuerpo <span class="text-brand">*</span>
        </label>
        <textarea name="body" rows="8"
            class="form-control @error('body') is-invalid @enderror"
            placeholder="Escribe aqui el contenido de la respuesta predefinida...">{{ old('body', $cannedReply->body ?? '') }}</textarea>
        @error('body')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

@push('scripts')
<script src="{{ asset('vendor/helpdesk/settings/canned-replies-form.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/canned-replies-form.js')) }}" defer></script>
@endpush
