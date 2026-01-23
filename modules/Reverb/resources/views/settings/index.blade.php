@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fas fa-circle me-2"></i>Configuración de Reverb
            </h6>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estado del servidor</h6>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Host de broadcasting</label>
                            <p class="text-muted">{{ $settings['host'] }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Puerto</label>
                            <p class="text-muted">{{ $settings['port'] }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Esquema</label>
                            <p class="text-muted">{{ $settings['scheme'] }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Host del servidor</label>
                            <p class="text-muted">{{ $settings['server_host'] }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" id="testConnection">
                                <i class="fas fa-plug me-2"></i>Probar conexión
                            </button>
                            <a href="{{ route('manager.backups.reverb.edit') }}" class="btn btn-secondary">
                                <i class="fas fa-edit me-2"></i>Editar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Características</h6>

                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    {{ $settings['features']['presence_tracking'] ? 'checked' : '' }} disabled>
                                <label class="form-check-label">
                                    Presencia en tiempo real
                                </label>
                            </div>
                        </div>
                        <div class="list-group-item px-0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    {{ $settings['features']['event_broadcasting'] ? 'checked' : '' }} disabled>
                                <label class="form-check-label">
                                    Transmisión de eventos
                                </label>
                            </div>
                        </div>
                        <div class="list-group-item px-0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    {{ $settings['features']['channel_authentication'] ? 'checked' : '' }} disabled>
                                <label class="form-check-label">
                                    Autenticación de canales
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Acciones rápidas</h6>

                    <div class="btn-group" role="group">
                        <a href="{{ route('manager.backups.reverb.monitoring.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line me-2"></i>Monitoreo
                        </a>
                        <a href="{{ route('manager.backups.reverb.edit') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('testConnection')?.addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Probando...';

    fetch('{{ route("manager.backups.reverb.test-connection") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Conexión exitosa: ' + data.message);
        } else {
            alert('✗ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug me-2"></i>Probar conexión';
    });
});
</script>
@endsection
