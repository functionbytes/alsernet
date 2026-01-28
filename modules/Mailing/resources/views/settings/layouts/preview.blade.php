{{-- Layout Preview Component --}}

<div class="modal fade" id="layoutPreviewModal" tabindex="-1" aria-labelledby="layoutPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            {{-- Header --}}
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title" id="layoutPreviewModalLabel">
                        <i class="fas fa-eye me-2 text-info"></i>Vista previa de layout
                    </h5>
                    <small class="text-muted">Previsualización del layout de email</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                {{-- Layout Configuration Info --}}
                <div class="alert alert-light border mb-4 small">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <p class="mb-1"><strong><i class="fas fa-window-maximize me-2"></i>Tipo:</strong></p>
                            <p class="mb-0 ps-4" id="previewType">-</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong><i class="fas fa-ruler-horizontal me-2"></i>Ancho:</strong></p>
                            <p class="mb-0 ps-4"><span id="previewWidth">-</span>px</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong><i class="fas fa-palette me-2"></i>Color:</strong></p>
                            <p class="mb-0 ps-4">
                                <span id="previewBgColor" style="display: inline-block; width: 20px; height: 20px; border: 1px solid #ccc; border-radius: 3px; vertical-align: middle;"></span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Layout Preview --}}
                <p class="small text-muted mb-2"><strong>Previsualización:</strong></p>
                <div id="previewContainer" style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; min-height: 400px; max-height: 600px; overflow-y: auto;">
                    <div id="previewContent" style="background-color: white; padding: 2rem; border-radius: 4px;">
                        <p class="text-muted text-center">Sin contenido para previsualizar</p>
                    </div>
                </div>

                {{-- HTML Preview --}}
                <div class="mt-4">
                    <p class="small text-muted mb-2"><strong>Código HTML:</strong></p>
                    <div style="background-color: #f4f4f4; border: 1px solid #ddd; border-radius: 4px; padding: 10px; max-height: 200px; overflow-y: auto;">
                        <pre id="previewHTML" class="mb-0" style="font-size: 11px; font-family: 'Courier New', monospace;"></pre>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
                <button type="button" class="btn btn-info" onclick="downloadLayoutHTML()">
                    <i class="fas fa-download me-1"></i>Descargar HTML
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateLayoutPreview() {
        const type = document.querySelector('select[name="type"]')?.value || '-';
        const maxWidth = document.querySelector('input[name="max_width"]')?.value || '600';
        const bgColor = document.querySelector('input[name="background_color"]')?.value || '#ffffff';
        const html = document.querySelector('textarea[name="html"]')?.value || '';

        // Update header info
        document.getElementById('previewType').textContent = getLayoutTypeLabel(type);
        document.getElementById('previewWidth').textContent = maxWidth;
        document.getElementById('previewBgColor').style.backgroundColor = bgColor;

        // Update preview container background
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.style.backgroundColor = bgColor;

        // Update HTML preview
        document.getElementById('previewHTML').textContent = html;

        // Update layout preview
        const contentPreview = document.getElementById('previewContent');
        const previewHTML = html.replace(/\{\{content\}\}/g, '<div style="padding: 20px; background-color: #f9f9f9; border: 2px dashed #ccc; text-align: center; color: #999;">Contenido principal aquí</div>');

        if (html.trim()) {
            try {
                contentPreview.innerHTML = previewHTML;
                contentPreview.style.maxWidth = maxWidth + 'px';
                contentPreview.style.margin = '0 auto';
            } catch (e) {
                contentPreview.innerHTML = '<p class="text-danger">Error al renderizar el HTML</p>';
            }
        } else {
            contentPreview.innerHTML = '<p class="text-muted text-center">Sin contenido para previsualizar</p>';
        }
    }

    function getLayoutTypeLabel(type) {
        const types = {
            'single-column': 'Una columna',
            'two-column': 'Dos columnas',
            'three-column': 'Tres columnas'
        };
        return types[type] || 'Sin definir';
    }

    function downloadLayoutHTML() {
        const name = document.querySelector('input[name="name"]')?.value || 'layout';
        const html = document.querySelector('textarea[name="html"]')?.value || '';
        const bgColor = document.querySelector('input[name="background_color"]')?.value || '#ffffff';
        const maxWidth = document.querySelector('input[name="max_width"]')?.value || '600';

        const fullHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>` + name + `</title>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        font-family: Arial, sans-serif;
                        background-color: ` + bgColor + `;
                    }
                    .email-container {
                        max-width: ` + maxWidth + `px;
                        margin: 0 auto;
                        background-color: white;
                    }
                </style>
            </head>
            <body>
                <div class="email-container">
                    ` + html.replace(/\{\{content\}\}/g, '<div style="padding: 20px;">Contenido principal</div>') + `
                </div>
            </body>
            </html>
        `;

        const blob = new Blob([fullHTML], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = name.replace(/\s+/g, '-') + '.html';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    // Update preview on input change
    $(document).ready(function() {
        $('#formLayout').on('change input', function() {
            updateLayoutPreview();
        });

        // Initial preview update
        updateLayoutPreview();
    });
</script>
@endpush
