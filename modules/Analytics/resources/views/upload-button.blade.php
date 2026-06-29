<div class="upload-credentials-section">
    <div class="d-flex gap-2 align-items-center mb-3">
        <button type="button" class="btn btn-outline-primary" id="uploadCredentialsBtn">
            <i class="fas fa-upload me-2"></i>Subir archivo JSON
        </button>
        <button type="button" class="btn btn-outline-secondary" id="formatJsonBtn">
            <i class="fas fa-code me-2"></i>Formatear JSON
        </button>
        <button type="button" class="btn btn-outline-info" id="downloadTemplateBtn">
            <i class="fas fa-download me-2"></i>Descargar plantilla
        </button>
    </div>

    <input type="file" id="credentialsFileInput" accept=".json" class="d-none">

    <div class="alert alert-info border-0 bg-info-subtle d-none" id="credentialsInfo">
        <div class="d-flex align-items-start">
            <i class="fas fa-info-circle me-2 mt-1"></i>
            <div class="flex-grow-1">
                <strong>Información del archivo:</strong>
                <ul class="mb-0 mt-2 small">
                    <li>Tipo: <span id="infoType">-</span></li>
                    <li>Proyecto: <span id="infoProjectId">-</span></li>
                    <li>Email del cliente: <span id="infoClientEmail">-</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const uploadBtn = document.getElementById('uploadCredentialsBtn');
    const formatBtn = document.getElementById('formatJsonBtn');
    const downloadBtn = document.getElementById('downloadTemplateBtn');
    const fileInput = document.getElementById('credentialsFileInput');
    const credentialsTextarea = document.getElementById('credentials');
    const infoBox = document.getElementById('credentialsInfo');

    // Upload credentials file
    uploadBtn.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('credentials_file', file);

        try {
            const response = await fetch('{{ route("settings.analytics.upload-json") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const data = await response.json();

            if (data.status) {
                credentialsTextarea.value = data.data.credentials;
                showCredentialsInfo(data.data);
                showToast('success', 'Archivo cargado correctamente');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            showToast('error', error.message);
        }

        fileInput.value = '';
    });

    // Format JSON
    formatBtn.addEventListener('click', async () => {
        const credentials = credentialsTextarea.value.trim();
        if (!credentials) {
            showToast('warning', 'Por favor ingresa las credenciales primero');
            return;
        }

        try {
            const response = await fetch('{{ route("settings.analytics.format-json") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ credentials })
            });

            const data = await response.json();

            if (data.status) {
                credentialsTextarea.value = data.data.formatted;
                showToast('success', 'JSON formateado correctamente');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            showToast('error', error.message);
        }
    });

    // Download template
    downloadBtn.addEventListener('click', async () => {
        try {
            const response = await fetch('{{ route("settings.analytics.download-template") }}');
            const data = await response.json();

            if (data.status) {
                const blob = new Blob([data.data.content], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.data.filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showToast('success', 'Plantilla descargada');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            showToast('error', error.message);
        }
    });

    // Show credentials info
    function showCredentialsInfo(info) {
        document.getElementById('infoType').textContent = info.type || '-';
        document.getElementById('infoProjectId').textContent = info.project_id || '-';
        document.getElementById('infoClientEmail').textContent = info.client_email || '-';
        infoBox.classList.remove('d-none');
    }

    // Extract info when textarea changes
    credentialsTextarea.addEventListener('blur', async () => {
        const credentials = credentialsTextarea.value.trim();
        if (!credentials) {
            infoBox.classList.add('d-none');
            return;
        }

        try {
            const response = await fetch('{{ route("settings.analytics.extract-info") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ credentials })
            });

            const data = await response.json();
            if (data.status) {
                showCredentialsInfo(data.data);
            }
        } catch (error) {
            // Silently fail
        }
    });

    // Toast helper
    function showToast(type, message) {
        // Use your existing toast notification system
        // For now, just use alert
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }
})();
</script>
@endpush
