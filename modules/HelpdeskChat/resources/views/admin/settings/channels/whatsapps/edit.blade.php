@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Edit WhatsApp Channel</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.channels.whatsapps.update', $whatsapp) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Inbox Name</label>
                        <input type="text" name="inbox_name" class="form-control @error('inbox_name') is-invalid @enderror"
                               value="{{ old('inbox_name', $whatsapp->inbox->name) }}" required>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" value="{{ $whatsapp->phone_number }}" disabled>
                        <small class="text-muted">Phone number cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Provider</label>
                        <input type="text" class="form-control"
                               value="{{ $whatsapp->provider === 'whatsapp_cloud' ? 'WhatsApp Cloud API' : '360Dialog' }}"
                               disabled>
                        <small class="text-muted">Provider cannot be changed</small>
                    </div>

                    <hr class="my-4">

                    @if($whatsapp->is360Dialog())
                    <h6>360Dialog Configuration</h6>

                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="password" name="dialog360_api_key"
                               class="form-control @error('dialog360_api_key') is-invalid @enderror"
                               placeholder="Leave empty to keep current API key">
                        @error('dialog360_api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Only enter if you want to update the API key</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client ID (Optional)</label>
                        <input type="text" name="dialog360_client_id"
                               class="form-control @error('dialog360_client_id') is-invalid @enderror"
                               value="{{ old('dialog360_client_id', $whatsapp->getClientId()) }}">
                        @error('dialog360_client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror>
                    </div>
                    @else
                    <h6>WhatsApp Cloud API Configuration</h6>

                    <div class="mb-3">
                        <label class="form-label">Phone Number ID</label>
                        <input type="text" name="cloud_phone_number_id"
                               class="form-control @error('cloud_phone_number_id') is-invalid @enderror"
                               value="{{ old('cloud_phone_number_id', $whatsapp->getPhoneNumberId()) }}">
                        @error('cloud_phone_number_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Business Account ID</label>
                        <input type="text" name="cloud_business_account_id"
                               class="form-control @error('cloud_business_account_id') is-invalid @enderror"
                               value="{{ old('cloud_business_account_id', $whatsapp->getBusinessAccountId()) }}">
                        @error('cloud_business_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Access Token</label>
                        <input type="password" name="cloud_access_token"
                               class="form-control @error('cloud_access_token') is-invalid @enderror"
                               placeholder="Leave empty to keep current access token">
                        @error('cloud_access_token')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Only enter if you want to update the access token</small>
                    </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-circle"></i> Update WhatsApp Channel
                        </button>
                        <a href="{{ route('admin.channels.whatsapps.show', $whatsapp) }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Credentials</h5>
            </div>
            <div class="card-body">
                @if($whatsapp->is360Dialog())
                    <p class="small text-muted mb-3">
                        Update your 360Dialog API credentials if you need to change your integration settings.
                    </p>
                @else
                    <p class="small text-muted mb-3">
                        Update your WhatsApp Cloud API credentials if you need to change your integration settings.
                    </p>
                @endif

                <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle"></i>
                    <strong>Tip:</strong> Keep your credentials secure and never share them publicly.
                </div>
            </div>
        </div>

        <div class="card bg-light mt-3">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-shield-check"></i> Security</h6>
                <ul class="small mb-0">
                    <li>Credentials are encrypted at rest</li>
                    <li>Never share API keys or tokens</li>
                    <li>Rotate credentials regularly</li>
                    <li>Monitor account access logs</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
