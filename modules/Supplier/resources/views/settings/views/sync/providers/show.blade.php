@extends('layouts.theme')

@section('content')



<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $provider->name }}</h5>
                        <p class="card-subtitle mb-0 mt-2">
                            Código: <strong>{{ $provider->code }}</strong> |
                            CIF: <strong>{{ $provider->tax_id ?? 'N/A' }}</strong>
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('settings.suppliers.providers.index') }}" class="btn btn-light me-2">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <button type="button"
                                class="btn btn-primary btn-sync-provider"
                                data-provider-id="{{ $provider->erp_provider_id }}"
                                data-provider-name="{{ $provider->name }}">
                            <i class="fas fa-sync-alt me-1"></i> Sincronizar productos
                        </button>
                    </div>
                </div>

                <!-- Provider Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Información de contacto</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted" width="40%">Email:</td>
                                        <td>{{ $provider->email ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Teléfono:</td>
                                        <td>{{ $provider->phone ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Persona de contacto:</td>
                                        <td>{{ $provider->contact_person ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Sitio web:</td>
                                        <td>
                                            @if($provider->website)
                                                <a href="{{ $provider->website }}" target="_blank">{{ $provider->website }}</a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Dirección</h6>
                                <p class="mb-1">{{ $provider->full_address ?: 'No disponible' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="card border">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            Productos ({{ $provider->provider_products_count }})
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($products->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código proveedor</th>
                                            <th>Nombre</th>
                                            <th class="text-end">Costo</th>
                                            <th class="text-end">Dto 1</th>
                                            <th class="text-end">Dto 2</th>
                                            <th class="text-end">Costo final</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Por defecto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($products as $product)
                                            <tr>
                                                <td>
                                                    <code>{{ $product->provider_code }}</code>
                                                </td>
                                                <td>{{ $product->provider_name }}</td>
                                                <td class="text-end">€{{ number_format($product->current_cost, 2) }}</td>
                                                <td class="text-end">
                                                    @if($product->discount1)
                                                        <span class="badge bg-info">{{ $product->discount1 }}%</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($product->discount2)
                                                        <span class="badge bg-info">{{ $product->discount2 }}%</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-bold">€{{ number_format($product->final_cost, 2) }}</td>
                                                <td class="text-center">
                                                    @if($product->is_active)
                                                        <span class="badge bg-success">Activo</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($product->is_default)
                                                        <i class="fas fa-star text-warning"></i>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($products->hasPages())
                                <div class="mt-3">
                                    {{ $products->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No hay productos sincronizados para este proveedor.</p>
                                <button type="button"
                                        class="btn btn-primary mt-3 btn-sync-provider"
                                        data-provider-id="{{ $provider->erp_provider_id }}"
                                        data-provider-name="{{ $provider->name }}">
                                    <i class="fas fa-sync-alt me-1"></i> Sincronizar productos ahora
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reuse same sync modal from index -->
<div class="modal fade" id="syncProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sincronizando...</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span id="sync-status-text">Iniciando sincronización...</span>
                        <span id="sync-progress-percent">0%</span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar"
                             id="sync-progress-bar"
                             style="width: 0%"></div>
                    </div>
                </div>
                <div id="sync-details" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" disabled id="btn-close-modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const syncProgressModal = new bootstrap.Modal(document.getElementById('syncProgressModal'));
    let progressInterval = null;

    $('.btn-sync-provider').click(function() {
        const providerId = $(this).data('provider-id');
        const providerName = $(this).data('provider-name');

        if (confirm(`¿Deseas sincronizar los productos del proveedor "${providerName}"?`)) {
            startProviderSync(providerId, providerName);
        }
    });

    function startProviderSync(providerId, providerName) {
        $.ajax({
            url: '{{ route("settings.suppliers.sync.erp.products") }}',
            method: 'POST',
            data: { erp_provider_id: providerId },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            beforeSend: function() {
                $('#sync-status-text').text(`Sincronizando ${providerName}...`);
                $('#sync-progress-bar').css('width', '0%');
                $('#sync-progress-percent').text('0%');
                $('#sync-details').html('');
                $('#btn-close-modal').prop('disabled', true);
                syncProgressModal.show();
            },
            success: function(response) {
                if (response.success) {
                    monitorProgress(response.batch_id);
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.message || 'Error al iniciar sincronización');
            }
        });
    }

    function monitorProgress(batchId) {
        progressInterval = setInterval(function() {
            $.ajax({
                url: `/setting/suppliers/sync/progress/${batchId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const batch = response.batch;
                        const progress = batch.progress_percentage || 0;

                        $('#sync-progress-bar').css('width', progress + '%');
                        $('#sync-progress-percent').text(Math.round(progress) + '%');
                        $('#sync-details').html(`Procesados: ${batch.processed_items}/${batch.total_items}`);

                        if (batch.status === 'completed') {
                            clearInterval(progressInterval);
                            $('#sync-status-text').text('¡Completado!');
                            $('#sync-progress-bar').removeClass('progress-bar-animated');
                            $('#btn-close-modal').prop('disabled', false);
                            setTimeout(() => location.reload(), 2000);
                        }
                    }
                }
            });
        }, 2000);
    }

    function showError(message) {
        clearInterval(progressInterval);
        $('#sync-status-text').text('Error');
        $('#sync-progress-bar').removeClass('progress-bar-animated').addClass('bg-danger');
        $('#sync-details').html(`<span class="text-danger">${message}</span>`);
        $('#btn-close-modal').prop('disabled', false);
    }
});
</script>
@endpush
@endsection
