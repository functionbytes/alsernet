@php
    $isEdit = !is_null($shortcodeCategory ?? null);
    $action = $isEdit
        ? route('settings.shortcode-categories.update', $shortcodeCategory->id)
        : route('settings.shortcode-categories.store');
@endphp

@include('core::components.card', ['title' => $isEdit ? 'Editar categoria: ' . $shortcodeCategory->label : 'Nueva categoria'])

@include('core::components.alerts')

<div class="widget-content searchable-container list">
    <div class="row g-4 align-items-start">

        {{-- Main column --}}
        <div class="col-lg-8">
            <form action="{{ $action }}" method="POST" id="categoryForm">
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Informacion basica</h6>
                        <p class="text-muted mb-3">Define la etiqueta y clave tecnica de la categoria.</p>

                        <div class="row g-3">
                            {{-- Label --}}
                            <div class="col-md-12">
                                <label for="label" class="form-label fw-semibold">Etiqueta <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('label') is-invalid @enderror"
                                       id="label" name="label"
                                       value="{{ old('label', $shortcodeCategory?->label) }}"
                                       required maxlength="100" autofocus>
                                @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Slug --}}
                            <div class="col-md-12">
                                <label for="slug" class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                       id="slug" name="slug"
                                       value="{{ old('slug', $shortcodeCategory?->slug) }}"
                                       required maxlength="50"
                                       placeholder="mi-categoria" pattern="[a-z0-9\-]+">
                                <small class="text-muted d-block mt-1">Solo letras minusculas, numeros y guiones.</small>
                                @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Sort order --}}
                            <div class="col-md-12">
                                <label for="sort_order" class="form-label fw-semibold">Orden</label>
                                <input type="number" class="form-control"
                                       id="sort_order" name="sort_order"
                                       value="{{ old('sort_order', $shortcodeCategory?->sort_order ?? 0) }}" min="0">
                            </div>

                            {{-- Status --}}
                            <div class="col-md-12">
                                <label for="is_active" class="form-label fw-semibold">Estado</label>
                                <select class="form-select select2" name="is_active" id="is_active">
                                    <option value="1" {{ old('is_active', $shortcodeCategory?->is_active ?? true) ? 'selected' : '' }}>Activa</option>
                                    <option value="0" {{ !old('is_active', $shortcodeCategory?->is_active ?? true) ? 'selected' : '' }}>Inactiva</option>
                                </select>
                                <small class="text-muted d-block mt-1">Las categorias inactivas no aparecen en el panel del editor.</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top">
                        <button type="submit" class="btn btn-primary w-100">
                            {{ $isEdit ? 'Guardar cambios' : 'Crear categoria' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-1">Acciones</h6>
                    <p class="text-muted mb-3">Gestiona esta categoria.</p>
                    <a href="{{ route('settings.shortcode-categories.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                        Volver al listado
                    </a>
                    @if($isEdit)
                        <button type="button" class="btn btn-outline-danger w-100" id="deleteBtnSidebar"
                                data-url="{{ route('settings.shortcode-categories.destroy', $shortcodeCategory->id) }}"
                                data-label="{{ $shortcodeCategory->label }}">
                            Eliminar categoria
                        </button>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
$(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');

    // Auto-generate slug from label (only when slug hasn't been manually edited)
    $('#label').on('input', function () {
        if (!$('#slug').data('manual')) {
            $('#slug').val($(this).val()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
            );
        }
    });
    $('#slug').on('input', function () { $(this).data('manual', true); });

    // Initialize select2
    $('.select2').select2({ width: '100%' });

    // Delete button (sidebar)
    $('#deleteBtnSidebar').on('click', function () {
        var $btn = $(this);
        if (!confirm('¿Eliminar la categoria «' + $btn.data('label') + '»?')) { return; }

        $.ajax({
            url:     $btn.data('url'),
            method:  'POST',
            data:    { _method: 'DELETE' },
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            success: function () {
                toastr.success('Categoria eliminada.');
                setTimeout(function () {
                    window.location.href = '{{ route('settings.shortcode-categories.index') }}';
                }, 800);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'No se pudo eliminar la categoria.';
                if (xhr.status === 422) { toastr.warning(msg); } else { toastr.error(msg); }
            },
        });
    });

    @if(session('success'))
    toastr.success(@json(session('success')), 'Exito');
    @endif
    @if(session('error'))
    toastr.error(@json(session('error')), 'Error');
    @endif
});
</script>
@endpush
