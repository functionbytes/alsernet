@extends('layouts.theme')

@section('title', 'New Import')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('mailrelay.imports.index') }}">Imports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">New Import</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Upload Import File</h1>
            <p class="text-muted">Upload a CSV or Excel file to import data</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>There were some errors with your upload:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('mailrelay.imports.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        <div class="mb-4">
                            <label for="import_type" class="form-label">Import Type</label>
                            <select class="form-select" id="import_type" name="import_type" required>
                                <option value="">Select import type...</option>
                                <option value="contacts" {{ old('import_type') === 'contacts' ? 'selected' : '' }}>Contacts</option>
                                <option value="subscribers" {{ old('import_type') === 'subscribers' ? 'selected' : '' }}>Subscribers</option>
                                <option value="campaigns" {{ old('import_type') === 'campaigns' ? 'selected' : '' }}>Campaigns</option>
                                <option value="other" {{ old('import_type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Upload File</label>
                            <div class="drop-zone" id="dropZone">
                                <div class="drop-zone-content">
                                    <i class="bi bi-cloud-upload display-1 text-primary"></i>
                                    <h5 class="mt-3">Drag and drop your file here</h5>
                                    <p class="text-muted">or click to browse</p>
                                    <input type="file"
                                           class="form-control d-none"
                                           id="fileInput"
                                           name="file"
                                           accept=".csv,.xls,.xlsx"
                                           required>
                                    <button type="button" class="btn btn-outline-primary mt-2" id="browseBtn">
                                        <i class="bi bi-folder2-open me-2"></i>Browse Files
                                    </button>
                                    <div class="mt-3">
                                        <small class="text-muted">Supported formats: CSV, XLS, XLSX (Max: 10MB)</small>
                                    </div>
                                </div>
                                <div class="drop-zone-preview d-none" id="filePreview">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-spreadsheet display-4 text-success me-3"></i>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1" id="fileName"></h6>
                                            <small class="text-muted" id="fileSize"></small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="has_headers"
                                       name="has_headers"
                                       {{ old('has_headers', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_headers">
                                    First row contains column headers
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Add notes about this import...">{{ old('description') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('mailrelay.imports.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-upload me-2"></i>Upload and Process
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-info-circle text-primary me-2"></i>File Requirements
                    </h6>
                    <ul class="small mb-0">
                        <li>Maximum file size: 10MB</li>
                        <li>Supported formats: CSV, XLS, XLSX</li>
                        <li>UTF-8 encoding recommended</li>
                        <li>First row should contain headers</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-lightbulb text-warning me-2"></i>Tips
                    </h6>
                    <ul class="small mb-0">
                        <li>Ensure data is properly formatted</li>
                        <li>Remove duplicate entries beforehand</li>
                        <li>Use standard date formats (YYYY-MM-DD)</li>
                        <li>Check for special characters</li>
                        <li>Review data before uploading</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.drop-zone {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 3rem 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background-color: #f8f9fa;
}

.drop-zone:hover {
    border-color: #0d6efd;
    background-color: #e7f1ff;
}

.drop-zone.drag-over {
    border-color: #0d6efd;
    background-color: #e7f1ff;
    transform: scale(1.02);
}

.drop-zone-preview {
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 2px solid #198754;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const browseBtn = document.getElementById('browseBtn');
    const filePreview = document.getElementById('filePreview');
    const dropZoneContent = document.querySelector('.drop-zone-content');
    const removeFileBtn = document.getElementById('removeFile');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');

    // Browse button click
    browseBtn.addEventListener('click', function() {
        fileInput.click();
    });

    // Drop zone click
    dropZone.addEventListener('click', function(e) {
        if (e.target !== removeFileBtn && !removeFileBtn.contains(e.target)) {
            fileInput.click();
        }
    });

    // File input change
    fileInput.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    // Drag and drop events
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        fileInput.files = files;
        handleFiles(files);
    });

    // Remove file
    removeFileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        fileInput.value = '';
        filePreview.classList.add('d-none');
        dropZoneContent.classList.remove('d-none');
    });

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            const validExtensions = ['.csv', '.xls', '.xlsx'];
            const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

            if (!validExtensions.includes(fileExtension)) {
                alert('Invalid file type. Please upload a CSV, XLS, or XLSX file.');
                fileInput.value = '';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert('File size exceeds 10MB limit.');
                fileInput.value = '';
                return;
            }

            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            dropZoneContent.classList.add('d-none');
            filePreview.classList.remove('d-none');
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
});
</script>
@endsection
