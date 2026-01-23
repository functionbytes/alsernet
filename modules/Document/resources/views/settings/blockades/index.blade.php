@extends('layouts.theme')

@section('title', 'Bloqueos de Productos')

@section('content')

    @include('core::components.card', ['title' => 'Bloqueos de Productos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Bloqueos de productos</h5>
                        <p class="small mb-0 text-muted">Gestiona los bloqueos de productos que requieren documentación específica desde PrestaShop</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.documents.blockades.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <button type="button" class="btn btn-primary" id="syncBlockadesBtn">
                            Sincronizar
                        </button>
                        <button type="button" class="btn btn-secondary" id="syncBlockadesFreshBtn">
                            Limpiar y sincronizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold" id="totalBlockades">{{ $totalBlockades }}</h4>
                                        <small class="text-muted">Bloqueos sincronizados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Productos únicos</h6>
                                        <h4 class="mb-1 fw-bold" id="uniqueProducts">{{ $uniqueProducts }}</h4>
                                        <small class="text-muted">Productos bloqueados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Sincronizaciones</h6>
                                        <h4 class="mb-1 fw-bold" id="blockadesSyncCount">{{ $syncCount }}</h4>
                                        <small class="text-muted">Veces ejecutadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Última sincronización</h6>
                                        <h6 class="mb-1 fw-bold text-truncate" id="blockagesLastSync" title="{{ $lastSync }}">{{ $lastSync }}</h6>
                                        <small class="text-muted">Fecha</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" id="searchLabels" class="form-control" placeholder="Buscar etiquetas o tipos de documento..." autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#labelsModal">
                            Agregar etiqueta
                        </button>
                    </div>
                </div>
            </div>

            <!-- Blockade Labels List -->
            <div class="card-body">
                @php
                    $labels = array_filter(array_map('trim', explode(',', $currentLabels)));
                @endphp
                @if(count($labels) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="40%">Etiqueta</th>
                                <th width="40%">Tipo de documento asociado</th>
                                <th width="15%" class="text-center">Productos</th>
                                <th width="5%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($labels as $label)
                                @php
                                    // Find matching document type by searching associated_labels
                                    $docType = $documentTypes->first(function($dt) use ($label) {
                                        $associatedLabels = $dt->associated_labels ?? [];
                                        return in_array(strtoupper($label), array_map('strtoupper', $associatedLabels));
                                    });
                                @endphp
                                <tr>
                                    <td>
                                        <code>{{ strtoupper($label) }}</code>
                                    </td>
                                    <td>
                                        @if($docType)
                                            <div>
                                                <strong>{{ $docType->label }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $docType->description ? Str::limit($docType->description, 50) : '-' }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Sin tipo de documento asociado</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $labelCount = isset($blockadesByType[strtolower($label)]) ? $blockadesByType[strtolower($label)]->count : 0;
                                        @endphp
                                        @if($labelCount > 0)
                                            <span class="badge bg-light text-dark">{{ number_format($labelCount) }}</span>
                                        @else
                                            <span class="badge bg-light text-dark">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-secondary text-white delete-label-btn" data-label="{{ $label }}" title="Eliminar etiqueta">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-tags fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay etiquetas configuradas</h6>
                            <p class="text-muted mb-3">
                                Agrega etiquetas para comenzar a sincronizar productos bloqueados
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Delete Label -->
    <div class="modal fade" id="confirmDeleteLabelModal" tabindex="-1" aria-labelledby="confirmDeleteLabelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="round-48 rounded-circle  mx-auto mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-trash fs-9"></i>
                        </div>
                    </div>
                    <h5 class="mb-2 fw-bold">¿Eliminar etiqueta?</h5>
                    <p class="text-muted mb-0" id="deleteLabelMessage"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="confirmDeleteLabelBtn">Sí, eliminar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Fresh Sync -->
    <div class="modal fade" id="confirmFreshSyncModal" tabindex="-1" aria-labelledby="confirmFreshSyncModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="round-48 rounded-circle bg-warning-subtle text-warning mx-auto mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-exclamation-triangle fs-9"></i>
                        </div>
                    </div>
                    <h5 class="mb-2 fw-bold">¿Estás seguro?</h5>
                    <p class="text-muted mb-0">Esto eliminará todos los bloqueos actuales y los sincronizará de nuevo.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="confirmFreshSyncBtn">Sí, sincronizar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Label Modal -->
    <div class="modal fade" id="labelsModal" tabindex="-1" aria-labelledby="labelsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="labelsModalLabel">
                        Agregar nueva etiqueta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newLabel" class="form-label">Nueva etiqueta</label>
                        <input type="text" class="form-control text-uppercase" id="newLabel" placeholder="Ej: PISTOLA, REVOLVER" autocomplete="off">
                        <small class="text-muted">Ingresa una etiqueta que existe en PrestaShop. La etiqueta se convertirá a mayúsculas automáticamente.</small>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Después de agregar etiquetas, debes ejecutar la sincronización para importar los productos asociados.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary mb-1 w-100" id="addLabelBtn">
                        Agregar etiqueta
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="round-48 rounded-circle bg-black text-white mx-auto mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-check fs-4"></i>
                        </div>
                    </div>
                    <h5 class="mb-2 fw-bold">Éxito</h5>
                    <p class="text-muted mb-0" id="successMessage"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary w-100" id="reloadPageBtn">Recargar página</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Sync blockades
    $('#syncBlockadesBtn').on('click', function() {
        syncBlockades(false);
    });

    $('#syncBlockadesFreshBtn').on('click', function() {
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmFreshSyncModal'));
        confirmModal.show();
    });

    // Confirm fresh sync button in modal
    $('#confirmFreshSyncBtn').on('click', function() {
        $('#confirmFreshSyncModal').modal('hide');
        syncBlockades(true);
    });

    function syncBlockades(fresh) {
        const btn = fresh ? $('#syncBlockadesFreshBtn') : $('#syncBlockadesBtn');
        const originalText = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>' + (fresh ? 'Limpiando y sincronizando...' : 'Sincronizando...'));

        $.ajax({
            url: '{{ route('settings.documents.blockades.sync') }}',
            method: 'POST',
            data: {
                fresh: fresh,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#successMessage').text(response.message);
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                } else {
                    Swal.fire('Advertencia', response.message, 'warning');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Error al sincronizar bloqueos', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Reload page button in success modal
    $('#reloadPageBtn').on('click', function() {
        window.location.reload();
    });

    // Add new label button
    $('#addLabelBtn').on('click', function() {
        const newLabel = $('#newLabel').val().trim().toUpperCase();

        if (!newLabel) {
            Swal.fire('Error', 'Debes ingresar una etiqueta', 'error');
            return;
        }

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Agregando...');

        $.ajax({
            url: '{{ route('settings.documents.blockades.labels.add') }}',
            method: 'POST',
            data: {
                label: newLabel,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Close add label modal and reload
                    $('#labelsModal').modal('hide');
                    window.location.reload();
                } else {
                    Swal.fire('Advertencia', response.message, 'warning');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Error al agregar la etiqueta', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Delete label button
    let labelToDelete = null;
    $(document).on('click', '.delete-label-btn', function(e) {
        e.preventDefault();
        labelToDelete = String($(this).data('label'));

        console.log('Delete button clicked, label:', labelToDelete);

        // Set message in modal
        $('#deleteLabelMessage').text(`Se eliminará la etiqueta "${labelToDelete.toUpperCase()}" de la configuración`);

        // Show modal
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteLabelModal'));
        confirmModal.show();
    });

    // Confirm delete label button in modal
    $('#confirmDeleteLabelBtn').on('click', function() {
        console.log('Confirm delete clicked, deleting label:', labelToDelete);
        $('#confirmDeleteLabelModal').modal('hide');
        if (labelToDelete) {
            deleteLabel(labelToDelete);
        }
    });

    function deleteLabel(label) {
        console.log('Deleting label via AJAX:', label);
        $.ajax({
            url: '{{ route('settings.documents.blockades.labels.delete') }}',
            method: 'POST',
            data: {
                label: label,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    // Reload page directly
                    window.location.reload();
                } else {
                    Swal.fire('Advertencia', response.message, 'warning');
                }
            },
            error: function(xhr) {
                console.error('Delete error:', xhr);
                Swal.fire('Error', xhr.responseJSON?.message || 'Error al eliminar la etiqueta', 'error');
            }
        });
    }

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Search labels in real-time
    $('#searchLabels').on('keyup', function() {
        const searchText = $(this).val().toLowerCase().trim();
        let visibleCount = 0;

        $('.table tbody tr').each(function() {
            const labelText = $(this).find('td:eq(0)').text().toLowerCase();
            const docTypeText = $(this).find('td:eq(1)').text().toLowerCase();

            if (labelText.includes(searchText) || docTypeText.includes(searchText)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        // Show message if no results
        if (visibleCount === 0 && searchText !== '') {
            if ($('.no-results-message').length === 0) {
                $('.table tbody').append('<tr class="no-results-message"><td colspan="4" class="text-center text-muted py-4">No se encontraron etiquetas que coincidan con "' + searchText + '"</td></tr>');
            }
        } else {
            $('.no-results-message').remove();
        }
    });
});
</script>
@endpush
