@extends('layouts.theme')

@section('title', 'Editar fuente de datos')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar fuente de datos'])
@endsection

@section('content')

<div class="card w-100">

    <form id="formSource" method="POST"
          action="{{ route('settings.suppliers.sources.update', [$supplier->uid, $source->uid]) }}">

        {{ csrf_field() }}
        @method('PUT')
        <input type="hidden" name="source_type" value="website">

        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Editar fuente de datos</h5>
                <p class="card-subtitle mb-0 mt-2">
                    Modificando <strong>{{ $source->label }}</strong> del proveedor <strong>{{ $supplier->label }}</strong>.
                </p>
            </div>
            <a href="{{ route('settings.suppliers.sources.index', $supplier->uid) }}" class="btn btn-light">
                Volver
            </a>
        </div>

        <div class="card-body">
            {{-- ============================================================ --}}
            {{-- SECCIÓN 1: Información básica --}}
            {{-- ============================================================ --}}
            <h6 class="fw-semibold mb-1">Información básica</h6>
            <p class="text-muted small mb-3">Nombre y notas de uso de la fuente</p>
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Nombre de la fuente <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
                           value="{{ old('label', $source->label) }}" required placeholder="Ej: Web oficial del proveedor">
                    <small class="form-text text-muted">Nombre descriptivo para identificar la fuente</small>
                    @error('label')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                           value="{{ old('description', $source->description) }}" placeholder="Descripción de la fuente y su uso">
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Notas de uso</label>
                    <textarea name="usage_notes" class="form-control @error('usage_notes') is-invalid @enderror"
                              rows="2" placeholder="Ej: Usar solo para inspiración, no copiar directamente">{{ old('usage_notes', $source->usage_notes) }}</textarea>
                    <small class="form-text text-muted">Restricciones o instrucciones especiales</small>
                    @error('usage_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN 2: URLs de referencia para generar contenido --}}
            {{-- ============================================================ --}}
            <h6 class="fw-semibold mb-1 mt-4">URLs de referencia para generar contenido</h6>
            <p class="text-muted small mb-3">
                Páginas donde creemos que hay contenido/fichas del proveedor (catálogos, prensa,
                fichas técnicas). Al redactar la descripción de un producto con IA, se intentará
                encontrar el producto <strong>primero en estas páginas</strong>; solo si no se
                encuentra nada relevante ahí, se amplía la búsqueda a internet en general (Google).
            </p>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">URLs de contenido</label>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addContentUrlBtn" title="Agregar URL">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div id="contentUrlsContainer">
                {{-- filas añadidas por JS --}}
            </div>

        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
            <a href="{{ route('settings.suppliers.sources.index', $supplier->uid) }}" class="btn btn-light w-100">
                Cancelar
            </a>
        </div>

    </form>

</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ============================================================
    // Content reference URLs (para generación de contenido IA)
    // ============================================================
    function buildContentUrlRow(index) {
        return `
            <div class="input-group mb-2 content-url-row" data-index="${index}">
                <input type="url" class="form-control" name="content_urls[${index}][url]"
                       placeholder="https://proveedor.com/catalogo">
                <input type="text" class="form-control" name="content_urls[${index}][note]"
                       placeholder="Nota (opcional): fichas técnicas, catálogo PDF...">
                <button type="button" class="btn btn-outline-danger remove-content-url-btn"
                        style="display: none;" title="Eliminar" onclick="removeContentUrlRow(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    }

    let contentUrlIndex = 0;

    $(document).on('click', '#addContentUrlBtn', function () {
        contentUrlIndex++;
        $('#contentUrlsContainer').append(buildContentUrlRow(contentUrlIndex));
        updateRemoveButtons();
    });

    window.removeContentUrlRow = function (btn) {
        $(btn).closest('.content-url-row').remove();
        updateRemoveButtons();
    };

    function updateRemoveButtons() {
        const $rows = $('.content-url-row');
        $rows.find('.remove-content-url-btn').toggle($rows.length > 1);
    }

    // ============================================================
    // Pre-fill desde la fuente existente
    // ============================================================
    const existingContentUrls = @json($source->contentUrls->map(fn ($u) => ['url' => $u->url, 'note' => $u->note])->values());

    if (existingContentUrls && existingContentUrls.length) {
        existingContentUrls.forEach(function (item, i) {
            const $row = $(buildContentUrlRow(i));
            $row.find('input[type="url"]').val(item.url || '');
            $row.find('input[type="text"]').val(item.note || '');
            $('#contentUrlsContainer').append($row);
        });
        contentUrlIndex = existingContentUrls.length - 1;
        updateRemoveButtons();
    } else {
        $('#contentUrlsContainer').append(buildContentUrlRow(0));
    }

    // ============================================================
    // Form submission
    // ============================================================
    $('#formSource').on('submit', function (e) {
        e.preventDefault();

        const $btn = $(this).find('button[type="submit"]');
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Cambios guardados');
                    setTimeout(function () {
                        window.location.href = '{{ route("settings.suppliers.sources.index", $supplier->uid) }}';
                    }, 1000);
                } else {
                    toastr.error(response.message, 'Error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Error al guardar los cambios';
                toastr.error(message, 'Error');
                $btn.prop('disabled', false).html(originalHtml);

                if (xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, function (key, messages) {
                        $('[name="' + key + '"]').addClass('is-invalid')
                            .after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                }
            }
        });
    });

}());
</script>
@endpush
