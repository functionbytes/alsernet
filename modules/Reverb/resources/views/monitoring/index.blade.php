@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fas fa-chart-line me-2"></i>Monitoreo de Reverb
            </h6>
        </div>
    </div>

    <div class="row" id="statsContainer">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Estado del servidor</h6>
                    <h3>
                        <i id="serverStatus" class="fas fa-circle text-danger"></i>
                        <span id="serverStatusText">Desconectado</span>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Conexiones activas</h6>
                    <h3 id="activeConnections">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Canales abiertos</h6>
                    <h3 id="openChannels">0</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Memoria utilizada</h6>
                    <h3 id="memoryUsage">0 MB</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Canales activos</h6>
                </div>
                <div class="card-body">
                    <div id="channelsList" class="list-group">
                        <p class="text-muted">Cargando canales...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Conexiones por canal</h6>
                </div>
                <div class="card-body">
                    <div id="connectionsList" class="list-group">
                        <p class="text-muted">Cargando conexiones...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Opciones de monitoreo</h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary" id="refreshNow">
                            <i class="fas fa-sync me-1"></i>Actualizar ahora
                        </button>
                        <div class="form-check form-check-inline ms-3">
                            <input type="checkbox" id="autoRefresh" class="form-check-input" checked>
                            <label for="autoRefresh" class="form-check-label">Auto-actualización</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const refreshInterval = 5000; // 5 segundos
let autoRefreshInterval = null;

async function updateStats() {
    try {
        const [stats, channels, connections] = await Promise.all([
            fetch('{{ route("manager.backups.reverb.monitoring.stats") }}').then(r => r.json()),
            fetch('{{ route("manager.backups.reverb.monitoring.channels") }}').then(r => r.json()),
            fetch('{{ route("manager.backups.reverb.monitoring.connections") }}').then(r => r.json()),
        ]);

        updateServerStatus(stats);
        updateChannels(channels);
        updateConnections(connections);
    } catch (error) {
        console.error('Error updating stats:', error);
    }
}

function updateServerStatus(stats) {
    const statusEl = document.getElementById('serverStatus');
    const statusText = document.getElementById('serverStatusText');

    if (stats.server_status) {
        statusEl.classList.remove('text-danger');
        statusEl.classList.add('text-success');
        statusText.textContent = 'Conectado';
    } else {
        statusEl.classList.remove('text-success');
        statusEl.classList.add('text-danger');
        statusText.textContent = 'Desconectado';
    }

    document.getElementById('activeConnections').textContent = stats.connections;
    document.getElementById('memoryUsage').textContent = formatBytes(stats.memory_usage.current);
}

function updateChannels(data) {
    const list = document.getElementById('channelsList');
    if (!data.total || data.total === 0) {
        list.innerHTML = '<p class="text-muted">No hay canales activos</p>';
        return;
    }

    list.innerHTML = data.public_channels.map(channel => `
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <span>${channel.name}</span>
                <span class="badge bg-primary">${channel.subscribers} suscriptores</span>
            </div>
        </div>
    `).join('');
}

function updateConnections(data) {
    const list = document.getElementById('connectionsList');
    if (!data.total || data.total === 0) {
        list.innerHTML = '<p class="text-muted">No hay conexiones activas</p>';
        return;
    }

    list.innerHTML = Object.entries(data.by_channel).map(([channel, count]) => `
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <span>${channel}</span>
                <span class="badge bg-info">${count}</span>
            </div>
        </div>
    `).join('');
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    autoRefreshInterval = setInterval(updateStats, refreshInterval);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Event listeners
document.getElementById('refreshNow')?.addEventListener('click', updateStats);
document.getElementById('autoRefresh')?.addEventListener('change', (e) => {
    if (e.target.checked) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
});

// Initial load
updateStats();
startAutoRefresh();
</script>
@endsection
