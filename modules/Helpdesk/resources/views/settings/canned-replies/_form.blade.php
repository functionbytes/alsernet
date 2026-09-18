<div class="row g-3">

    {{-- Titulo --}}
    <div class="col-12">
        <label class="form-label">
            Titulo <span class="text-danger">*</span>
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

    {{-- Cuerpo --}}
    <div class="col-12">
        <label class="form-label">
            Cuerpo <span class="text-danger">*</span>
        </label>
        <textarea name="body" rows="8"
            class="form-control @error('body') is-invalid @enderror"
            placeholder="Escribe aqui el contenido de la respuesta predefinida...">{{ old('body', $cannedReply->body ?? '') }}</textarea>
        @error('body')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Global --}}
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_global" value="0">
            <input type="checkbox" class="form-check-input" name="is_global" value="1"
                id="is_global"
                @checked(old('is_global', $cannedReply->is_global ?? false))>
            <label class="form-check-label" for="is_global">
                Disponible para todos los agentes
            </label>
            <div class="form-text">Si no se activa, solo tu podras ver y usar esta respuesta</div>
        </div>
    </div>

</div>
