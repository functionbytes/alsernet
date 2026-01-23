@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-envelope"></i> Create Email Channel</h2>
                <a href="{{ route('admin.channels.emails.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.channels.emails.store') }}" method="POST">
                        @csrf

                        <!-- Basic Settings -->
                        <h5 class="mb-3">Basic Settings</h5>

                        <div class="mb-3">
                            <label for="inbox_name" class="form-label">Inbox Name</label>
                            <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                                   id="inbox_name" name="inbox_name" value="{{ old('inbox_name') }}" required>
                            @error('inbox_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="provider" class="form-label">Provider</label>
                            <select class="form-select" id="provider" name="provider">
                                <option value="custom">Custom (Generic IMAP/SMTP)</option>
                                <option value="google">Google / Gmail</option>
                                <option value="microsoft">Microsoft / Outlook</option>
                                <option value="zoho">Zoho Mail</option>
                            </select>
                        </div>

                        <hr class="my-4">

                        <!-- IMAP Settings (Receiving) -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>IMAP Settings (Receiving Emails)</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="imap_enabled" name="imap_enabled" value="1">
                                <label class="form-check-label" for="imap_enabled">Enable IMAP</label>
                            </div>
                        </div>

                        <div id="imap-settings" style="display: none;">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="imap_address" class="form-label">IMAP Server</label>
                                    <input type="text" class="form-control" id="imap_address" name="imap_address"
                                           placeholder="imap.example.com">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="imap_port" class="form-label">Port</label>
                                    <input type="number" class="form-control" id="imap_port" name="imap_port"
                                           value="993" placeholder="993">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="imap_login" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="imap_login" name="imap_login">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="imap_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="imap_password" name="imap_password">
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="imap_enable_ssl" name="imap_enable_ssl" value="1" checked>
                                <label class="form-check-label" for="imap_enable_ssl">Enable SSL/TLS</label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- SMTP Settings (Sending) -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>SMTP Settings (Sending Emails)</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1">
                                <label class="form-check-label" for="smtp_enabled">Enable SMTP</label>
                            </div>
                        </div>

                        <div id="smtp-settings" style="display: none;">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="smtp_address" class="form-label">SMTP Server</label>
                                    <input type="text" class="form-control" id="smtp_address" name="smtp_address"
                                           placeholder="smtp.example.com">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="smtp_port" class="form-label">Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                           value="587" placeholder="587">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_login" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="smtp_login" name="smtp_login">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_domain" class="form-label">Domain (optional)</label>
                                    <input type="text" class="form-control" id="smtp_domain" name="smtp_domain">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_authentication" class="form-label">Authentication</label>
                                    <select class="form-select" id="smtp_authentication" name="smtp_authentication">
                                        <option value="login" selected>Login</option>
                                        <option value="plain">Plain</option>
                                        <option value="cram_md5">CRAM-MD5</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="smtp_enable_starttls_auto"
                                       name="smtp_enable_starttls_auto" value="1" checked>
                                <label class="form-check-label" for="smtp_enable_starttls_auto">Enable STARTTLS</label>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="smtp_enable_ssl_tls"
                                       name="smtp_enable_ssl_tls" value="1">
                                <label class="form-check-label" for="smtp_enable_ssl_tls">Enable SSL/TLS</label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> Create Email Channel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const $imapToggle = $('#imap_enabled');
    const $imapSettings = $('#imap-settings');
    const $smtpToggle = $('#smtp_enabled');
    const $smtpSettings = $('#smtp-settings');
    const $provider = $('#provider');

    // Toggle IMAP settings
    $imapToggle.on('change', function() {
        $imapSettings.toggle(this.checked);
    });

    // Toggle SMTP settings
    $smtpToggle.on('change', function() {
        $smtpSettings.toggle(this.checked);
    });

    // Provider presets
    $provider.on('change', function() {
        const presets = {
            'google': {
                imap_address: 'imap.gmail.com',
                imap_port: '993',
                smtp_address: 'smtp.gmail.com',
                smtp_port: '587'
            },
            'microsoft': {
                imap_address: 'outlook.office365.com',
                imap_port: '993',
                smtp_address: 'smtp.office365.com',
                smtp_port: '587'
            },
            'zoho': {
                imap_address: 'imap.zoho.com',
                imap_port: '993',
                smtp_address: 'smtp.zoho.com',
                smtp_port: '587'
            }
        };

        const config = presets[$(this).val()];
        if (config) {
            $.each(config, function(key, value) {
                $('#' + key).val(value);
            });
        }
    });
});
</script>
@endpush
@endsection
