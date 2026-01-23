@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4>SMS Channel Details</h4>
                    <div>
                        <a href="{{ route('admin.channels.sms.edit', $sms) }}" class="btn btn-warning btn-sm">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.channels.sms.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th style="width: 200px;">Inbox Name:</th>
                        <td>{{ $sms->inbox->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Phone Number:</th>
                        <td><code>{{ $sms->formatted_phone_number }}</code></td>
                    </tr>
                    <tr>
                        <th>Provider:</th>
                        <td><span class="badge bg-info">{{ $sms->provider_display_name }}</span></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            @if($sms->active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Application ID:</th>
                        <td>{{ $sms->application_id ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td>{{ $sms->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>

                <div class="mt-4">
                    <h5>Webhook Configuration</h5>
                    <p class="text-muted">Configure this URL in your {{ $sms->provider_display_name }} dashboard:</p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="webhookUrl" value="{{ $webhookUrl }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyWebhookUrl()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    document.execCommand('copy');
    alert('Webhook URL copied to clipboard!');
}
</script>
@endpush
