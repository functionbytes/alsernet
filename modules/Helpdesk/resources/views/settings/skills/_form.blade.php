<div class="row g-3">

    {{-- Nombre --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" id="skillName"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $skill->name ?? '') }}"
            placeholder="Ej: Soporte tecnico">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Slug (generado automaticamente) --}}
    <div class="col-12">
        <label class="form-label text-muted">Slug generado</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-muted">
                <i class="fas fa-tag"></i>
            </span>
            <span id="skillSlugPreview" class="form-control bg-light text-muted font-monospace">
                {{ old('slug', $skill->slug ?? '') }}
            </span>
        </div>
        <div class="form-text">El slug se genera automaticamente a partir del nombre</div>
    </div>

    {{-- Descripcion --}}
    <div class="col-12">
        <label class="form-label">Descripcion</label>
        <textarea name="description" rows="3"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Describe brevemente en que consiste este skill...">{{ old('description', $skill->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Maximo 500 caracteres</div>
    </div>

</div>

@push('scripts')
<script>
$(document).ready(function () {
    function generateSlug(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/[\s-]+/g, '-');
    }

    $('#skillName').on('input', function () {
        const slug = generateSlug($(this).val());
        $('#skillSlugPreview').text(slug || '—');
    });
});
</script>
@endpush
