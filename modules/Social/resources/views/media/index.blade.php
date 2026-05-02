@extends('layouts.admin')

@section('title', 'Biblioteca de Medios')

@section('content')

    <div class="widget-content">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-image me-2"></i>Biblioteca de Medios
                        </h5>
                        <p class="small mb-0 text-muted">Gestiona imágenes y videos para tus publicaciones</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload me-1"></i> Subir Archivos
                    </button>
                </div>
            </div>

            <div class="card-body">
                @if(isset($files) && $files->count() > 0)
                    <div class="row g-3">
                        @foreach($files as $file)
                            <div class="col-md-3 col-sm-6">
                                <div class="card border h-100">
                                    <div class="position-relative" style="height: 200px; overflow: hidden;">
                                        @if($file['type'] === 'image')
                                            <img src="{{ $file['url'] }}"
                                                 class="card-img-top w-100 h-100"
                                                 style="object-fit: cover;"
                                                 alt="{{ $file['name'] }}">
                                        @elseif($file['type'] === 'video')
                                            <video class="w-100 h-100" style="object-fit: cover;">
                                                <source src="{{ $file['url'] }}" type="video/mp4">
                                            </video>
                                            <div class="position-absolute top-50 start-50 translate-middle">
                                                <i class="fas fa-play fs-1 text-white"></i>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                <i class="fas fa-file fs-1 text-muted"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title text-truncate" title="{{ $file['name'] }}">
                                            {{ $file['name'] }}
                                        </h6>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-file-circle-info me-1"></i>
                                            {{ number_format($file['size'] / 1024, 2) }} KB
                                        </p>
                                        <div class="d-flex gap-2">
                                            <button type="button"
                                                    class="btn btn-sm btn-light flex-fill"
                                                    onclick="copyToClipboard('{{ $file['url'] }}')">
                                                <i class="fas fa-copy"></i> Copiar URL
                                            </button>
                                            <form action="{{ route('admin.social.media.destroy') }}"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Eliminar este archivo?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $file['path'] }}">
                                                <button type="submit" class="btn btn-sm btn-light-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-image fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted mb-2">No hay archivos</h5>
                        <p class="text-muted mb-4">Sube tus primeros archivos multimedia</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-1"></i> Subir Archivos
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i>Subir Archivos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.social.media.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="files" class="form-label">
                                Seleccionar archivos
                            </label>
                            <input type="file"
                                   class="form-control"
                                   id="files"
                                   name="files[]"
                                   accept="image/*,video/*"
                                   multiple
                                   required>
                            <small class="text-muted">
                                Imágenes (JPG, PNG, GIF) o Videos (MP4, MOV, AVI). Máximo 20MB por archivo.
                            </small>
                        </div>
                        <div id="previewContainer" class="row g-2 mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Subir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Preview files before upload
    document.getElementById('files').addEventListener('change', function(e) {
        const preview = document.getElementById('previewContainer');
        preview.innerHTML = '';

        Array.from(this.files).forEach((file, index) => {
            if (index < 10) { // Limit preview to 10 files
                const reader = new FileReader();
                reader.onload = function(event) {
                    const col = document.createElement('div');
                    col.className = 'col-4';

                    if (file.type.startsWith('image/')) {
                        col.innerHTML = `<img src="${event.target.result}" class="img-fluid rounded" style="height: 100px; width: 100%; object-fit: cover;">`;
                    } else if (file.type.startsWith('video/')) {
                        col.innerHTML = `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 100px;">
                            <i class="fas fa-video fs-1 text-muted"></i>
                        </div>`;
                    }

                    preview.appendChild(col);
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Copy URL to clipboard
    function copyToClipboard(url) {
        navigator.clipboard.writeText(url).then(() => {
            // Show temporary success message
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            btn.classList.add('btn-success');

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
            }, 2000);
        });
    }
</script>
@endpush
