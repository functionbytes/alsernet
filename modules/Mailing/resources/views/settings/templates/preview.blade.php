{{-- Template Preview Component --}}

<div class="modal fade" id="templatePreviewModal" tabindex="-1" aria-labelledby="templatePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            {{-- Header --}}
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title" id="templatePreviewModalLabel">
                        <i class="fas fa-eye me-2 text-info"></i>Vista previa de plantilla
                    </h5>
                    <small class="text-muted">Previsualización del contenido del email</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                {{-- Email Header Information --}}
                <div class="alert alert-light border mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 small"><strong><i class="fas fa-user me-2"></i>De:</strong></p>
                            <p class="mb-0 ps-4"><span id="previewFromName">-</span> &lt;<span id="previewFromEmail">-</span>&gt;</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 small"><strong><i class="fas fa-tag me-2"></i>Asunto:</strong></p>
                            <p class="mb-0 ps-4 text-break" id="previewSubject">-</p>
                        </div>
                        <div class="col-12">
                            <p class="mb-1 small"><strong><i class="fas fa-type me-2"></i>Tipo:</strong></p>
                            <p class="mb-0 ps-4" id="previewType">-</p>
                        </div>
                    </div>
                </div>

                {{-- Email Content Preview --}}
                <div class="border rounded p-4" style="background-color: #f8f9fa; min-height: 400px; max-height: 600px; overflow-y: auto;">
                    <div id="previewContent" style="background-color: white; padding: 2rem;">
                        <p class="text-muted text-center">Sin contenido para previsualizar</p>
                    </div>
                </div>

                {{-- Variables Used --}}
                <div class="mt-4">
                    <p class="small text-muted mb-2"><strong>Variables detectadas en la plantilla:</strong></p>
                    <div id="detectedVariables" class="small">
                        <span class="badge bg-light text-dark">Ninguna variable detectada</span>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
                <button type="button" class="btn btn-info" onclick="downloadPreview()">
                    <i class="fas fa-download me-1"></i>Descargar HTML
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateTemplatePreview() {
        const name = document.querySelector('input[name="name"]')?.value || '-';
        const type = document.querySelector('select[name="type"]')?.value || '-';
        const subject = document.querySelector('input[name="subject"]')?.value || '-';
        const senderName = document.querySelector('input[name="sender_name"]')?.value || '-';
        const senderEmail = document.querySelector('input[name="sender_email"]')?.value || '-';
        const content = document.querySelector('textarea[name="content"]')?.value || '';

        // Update preview fields
        document.getElementById('previewFromName').textContent = senderName;
        document.getElementById('previewFromEmail').textContent = senderEmail;
        document.getElementById('previewSubject').textContent = subject;
        document.getElementById('previewType').textContent = getTypeLabel(type);

        // Update content preview
        const contentPreview = document.getElementById('previewContent');
        if (content.trim()) {
            try {
                contentPreview.innerHTML = content;
            } catch (e) {
                contentPreview.innerHTML = '<p class="text-danger">Error al renderizar el HTML</p>';
            }
        } else {
            contentPreview.innerHTML = '<p class="text-muted text-center">Sin contenido para previsualizar</p>';
        }

        // Detect variables
        detectVariables(content);
    }

    function getTypeLabel(type) {
        const types = {
            'welcome': 'Bienvenida',
            'newsletter': 'Newsletter',
            'transactional': 'Transaccional',
            'custom': 'Personalizado'
        };
        return types[type] || 'Sin definir';
    }

    function detectVariables(content) {
        const regex = /\{\{\s*(\w+\.\w+)\s*\}\}/g;
        const variables = new Set();
        let match;

        while ((match = regex.exec(content)) !== null) {
            variables.add(match[1]);
        }

        const variablesDiv = document.getElementById('detectedVariables');
        if (variables.size > 0) {
            variablesDiv.innerHTML = Array.from(variables)
                .map(v => `<span class="badge bg-info text-white me-2 mb-2"><code>` + v + `</code></span>`)
                .join('');
        } else {
            variablesDiv.innerHTML = '<span class="badge bg-light text-dark">Ninguna variable detectada</span>';
        }
    }

    function downloadPreview() {
        const content = document.getElementById('previewContent').innerHTML;
        const subject = document.getElementById('previewSubject').textContent;

        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>` + subject + `</title>
            </head>
            <body>
                ` + content + `
            </body>
            </html>
        `;

        const blob = new Blob([html], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'preview-' + subject.replace(/\s+/g, '-') + '.html';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    // Update preview on input change
    $(document).ready(function() {
        $('#formTemplate').on('change input', function() {
            updateTemplatePreview();
        });

        // Initial preview update
        updateTemplatePreview();
    });
</script>
@endpush
