@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5>Add WhatsApp Channel</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.channels.whatsapps.store') }}" method="POST" id="whatsapp-form">
                    @csrf
                    <div class="mb-3">
                        <label>Inbox Name</label>
                        <input type="text" name="inbox_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="+1234567890" required>
                    </div>
                    <div class="mb-3">
                        <label>Provider</label>
                        <select name="provider" id="provider" class="form-select" required>
                            <option value="whatsapp_cloud">WhatsApp Cloud API</option>
                            <option value="360dialog">360Dialog</option>
                            <option value="evolution_api">Evolution API (Baileys)</option>
                        </select>
                    </div>

                    <div id="cloud-fields">
                        <h6>WhatsApp Cloud API Configuration</h6>
                        <div class="mb-3">
                            <label>Phone Number ID</label>
                            <input type="text" name="cloud_phone_number_id" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Business Account ID</label>
                            <input type="text" name="cloud_business_account_id" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Access Token</label>
                            <input type="text" name="cloud_access_token" class="form-control">
                        </div>
                    </div>

                    <div id="dialog360-fields" style="display:none;">
                        <h6>360Dialog Configuration</h6>
                        <div class="mb-3">
                            <label>API Key</label>
                            <input type="text" name="dialog360_api_key" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Client ID (Optional)</label>
                            <input type="text" name="dialog360_client_id" class="form-control">
                        </div>
                    </div>

                    <div id="evolution-fields" style="display:none;">
                        <h6>Evolution API Configuration</h6>
                        <div class="mb-3">
                            <label>Instance Name <span class="text-danger">*</span></label>
                            <input type="text" name="evolution_instance_name" class="form-control" placeholder="my-whatsapp-instance">
                            <small class="text-muted">Nombre único para identificar esta instancia en Evolution API</small>
                        </div>
                        <div class="mb-3">
                            <label>API URL (Optional)</label>
                            <input type="url" name="evolution_api_url" class="form-control" placeholder="http://localhost:8080" value="{{ config('channels.whatsapp.providers.evolution_api.api_url') }}">
                            <small class="text-muted">Dejar en blanco para usar la configuración por defecto</small>
                        </div>
                        <div class="mb-3">
                            <label>API Key (Optional)</label>
                            <input type="text" name="evolution_api_key" class="form-control" placeholder="290829E64351-4416-8B10-618B90314812" value="{{ config('channels.whatsapp.providers.evolution_api.api_key') }}">
                            <small class="text-muted">Dejar en blanco para usar la configuración por defecto</small>
                        </div>
                        <div class="alert alert-info">
                            <strong>📱 Nota:</strong> Después de crear el canal, deberás conectar WhatsApp escaneando el QR code desde el dashboard de la instancia.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Channel</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const $provider = $('#provider');
    const $cloudFields = $('#cloud-fields');
    const $dialog360Fields = $('#dialog360-fields');
    const $evolutionFields = $('#evolution-fields');

    $provider.on('change', function() {
        // Hide all first
        $cloudFields.hide();
        $dialog360Fields.hide();
        $evolutionFields.hide();

        // Show the corresponding one
        const provider = $(this).val();
        if (provider === 'whatsapp_cloud') {
            $cloudFields.show();
        } else if (provider === '360dialog') {
            $dialog360Fields.show();
        } else if (provider === 'evolution_api') {
            $evolutionFields.show();
        }
    });

    // Trigger on page load to set initial state
    $provider.trigger('change');
});
</script>
@endpush
