@extends('layouts.theme')

@section('title', 'Nuevo provider de email')

@section('content')
<div class="widget-content">

    @include('core::components.alerts')

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('managers.mailrelay.providers.index') }}">Providers</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nuevo provider</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('managers.mailrelay.providers.store') }}" method="POST">
        @csrf

        <div class="row">
            {{-- Main Form --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-4 border-bottom">
                        <h5 class="mb-0 fw-bold">Información del provider</h5>
                    </div>
                    <div class="card-body p-4">

                        {{-- Nombre --}}
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">Nombre del provider <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="Ej: Mailrelay Producción" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Nombre descriptivo para identificar este provider</small>
                        </div>

                        {{-- Driver --}}
                        <div class="mb-4">
                            <label for="driver" class="form-label fw-semibold">Tipo de provider <span class="text-danger">*</span></label>
                            <select class="form-select @error('driver') is-invalid @enderror select2"
                                    id="driver" name="driver" required>
                                <option value="">Selecciona un proveedor...</option>
                                @foreach($availableDrivers as $driver)
                                    <option value="{{ $driver['value'] }}" {{ old('driver') == $driver['value'] ? 'selected' : '' }}>
                                        {{ $driver['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('driver')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Driver Info Alert --}}
                        <div id="driver-info" class="alert alert-info border d-none mb-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle me-3 mt-1"></i>
                                <div>
                                    <h6 id="driver-info-title" class="mb-1"></h6>
                                    <p id="driver-info-description" class="mb-0 small"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Credentials Section --}}
                        <div id="credentials-section" class="d-none">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">
                                <i class="fas fa-key me-2"></i>Credenciales
                            </h6>

                            {{-- Mailrelay Credentials --}}
                            <div id="credentials-mailrelay" class="driver-credentials d-none">
                                <div class="mb-3">
                                    <label class="form-label">API URL</label>
                                    <input type="text" class="form-control" name="credentials[api_url]"
                                           placeholder="https://app.mailrelay.com/api/v1"
                                           value="{{ old('credentials.api_url', 'https://app.mailrelay.com/api/v1') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">API Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="credentials[api_key]"
                                           placeholder="Tu API key de Mailrelay"
                                           value="{{ old('credentials.api_key') }}">
                                </div>
                            </div>

                            {{-- Mailtrap Credentials --}}
                            <div id="credentials-mailtrap" class="driver-credentials d-none">
                                <div class="mb-3">
                                    <label class="form-label">API Token <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="credentials[api_token]"
                                           placeholder="Tu API token de Mailtrap"
                                           value="{{ old('credentials.api_token') }}">
                                </div>
                            </div>

                            {{-- SendGrid Credentials --}}
                            <div id="credentials-sendgrid" class="driver-credentials d-none">
                                <div class="mb-3">
                                    <label class="form-label">API Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="credentials[api_key]"
                                           placeholder="SG.xxxxxxxxxxxxxxxx"
                                           value="{{ old('credentials.api_key') }}">
                                </div>
                            </div>

                            {{-- Common Email Fields --}}
                            <div class="mt-4">
                                <h6 class="fw-semibold mb-3">Configuración de remitente</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email remitente <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="credentials[from_email]"
                                               placeholder="noreply@tusitio.com"
                                               value="{{ old('credentials.from_email') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre remitente <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="credentials[from_name]"
                                               placeholder="Tu Empresa"
                                               value="{{ old('credentials.from_name') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email de respuesta</label>
                                        <input type="email" class="form-control" name="credentials[reply_to]"
                                               placeholder="soporte@tusitio.com"
                                               value="{{ old('credentials.reply_to') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Configuración --}}
                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-semibold">Configuración</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label">Prioridad</label>
                            <input type="number" class="form-control" name="priority"
                                   min="0" max="100" value="{{ old('priority', 0) }}">
                            <small class="text-muted">Mayor prioridad = mayor probabilidad de uso (0-100)</small>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Provider activo
                            </label>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default"
                                   id="is_default" value="1" {{ old('is_default', false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">
                                Provider predeterminado
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body p-3">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            <i class="fas fa-save me-2"></i>Guardar provider
                        </button>
                        <a href="{{ route('managers.mailrelay.providers.index') }}" class="btn btn-light w-100">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>

                {{-- Info --}}
                <div class="alert alert-light border mt-3">
                    <small class="text-muted">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Las credenciales se almacenan de forma encriptada en la base de datos.
                    </small>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const driverInfoData = @json($availableDrivers);

document.getElementById('driver').addEventListener('change', function() {
    const selectedDriver = this.value;
    const driverInfo = driverInfoData.find(d => d.value === selectedDriver);

    // Hide all credential sections
    document.querySelectorAll('.driver-credentials').forEach(el => {
        el.classList.add('d-none');
    });

    // Hide driver info
    document.getElementById('driver-info').classList.add('d-none');
    document.getElementById('credentials-section').classList.add('d-none');

    if (selectedDriver && driverInfo) {
        // Show driver info
        document.getElementById('driver-info-title').textContent = driverInfo.label;
        document.getElementById('driver-info-description').textContent = driverInfo.info.description;
        document.getElementById('driver-info').classList.remove('d-none');

        // Show credentials section
        document.getElementById('credentials-section').classList.remove('d-none');

        // Show specific credential fields
        const credentialsEl = document.getElementById('credentials-' + selectedDriver);
        if (credentialsEl) {
            credentialsEl.classList.remove('d-none');
        }
    }
});

// Trigger change on page load if there's an old value
if (document.getElementById('driver').value) {
    document.getElementById('driver').dispatchEvent(new Event('change'));
}
</script>
@endpush
@endsection
