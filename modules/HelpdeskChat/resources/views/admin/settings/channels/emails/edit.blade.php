@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h4>Edit Email Channel</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.channels.emails.update', $email) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="email" name="email" value="{{ old('email', $email->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="provider" class="form-label">Provider</label>
                        <select class="form-select @error('provider') is-invalid @enderror" id="provider" name="provider">
                            <option value="custom" {{ old('provider', $email->provider) == 'custom' ? 'selected' : '' }}>Custom IMAP/SMTP</option>
                            <option value="google" {{ old('provider', $email->provider) == 'google' ? 'selected' : '' }}>Google</option>
                            <option value="microsoft" {{ old('provider', $email->provider) == 'microsoft' ? 'selected' : '' }}>Microsoft</option>
                            <option value="zoho" {{ old('provider', $email->provider) == 'zoho' ? 'selected' : '' }}>Zoho</option>
                        </select>
                        @error('provider')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">
                    <h5>IMAP Settings (Incoming Mail)</h5>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="imap_enabled" name="imap_enabled" value="1"
                                {{ old('imap_enabled', $email->imap_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="imap_enabled">
                                Enable IMAP
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="imap_address" class="form-label">IMAP Server</label>
                                <input type="text" class="form-control @error('imap_address') is-invalid @enderror"
                                    id="imap_address" name="imap_address" value="{{ old('imap_address', $email->imap_address) }}">
                                @error('imap_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="imap_port" class="form-label">Port</label>
                                <input type="number" class="form-control @error('imap_port') is-invalid @enderror"
                                    id="imap_port" name="imap_port" value="{{ old('imap_port', $email->imap_port) }}">
                                @error('imap_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="imap_login" class="form-label">IMAP Login/Username</label>
                        <input type="text" class="form-control @error('imap_login') is-invalid @enderror"
                            id="imap_login" name="imap_login" value="{{ old('imap_login', $email->imap_login) }}">
                        @error('imap_login')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="imap_password" class="form-label">IMAP Password</label>
                        <input type="password" class="form-control @error('imap_password') is-invalid @enderror"
                            id="imap_password" name="imap_password" placeholder="Leave blank to keep current password">
                        <small class="form-text text-muted">Only enter if you want to change it</small>
                        @error('imap_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="imap_enable_ssl" name="imap_enable_ssl" value="1"
                                {{ old('imap_enable_ssl', $email->imap_enable_ssl) ? 'checked' : '' }}>
                            <label class="form-check-label" for="imap_enable_ssl">
                                Enable SSL for IMAP
                            </label>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5>SMTP Settings (Outgoing Mail)</h5>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="smtp_enabled" name="smtp_enabled" value="1"
                                {{ old('smtp_enabled', $email->smtp_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="smtp_enabled">
                                Enable SMTP
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="smtp_address" class="form-label">SMTP Server</label>
                                <input type="text" class="form-control @error('smtp_address') is-invalid @enderror"
                                    id="smtp_address" name="smtp_address" value="{{ old('smtp_address', $email->smtp_address) }}">
                                @error('smtp_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="smtp_port" class="form-label">Port</label>
                                <input type="number" class="form-control @error('smtp_port') is-invalid @enderror"
                                    id="smtp_port" name="smtp_port" value="{{ old('smtp_port', $email->smtp_port) }}">
                                @error('smtp_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="smtp_login" class="form-label">SMTP Login/Username</label>
                        <input type="text" class="form-control @error('smtp_login') is-invalid @enderror"
                            id="smtp_login" name="smtp_login" value="{{ old('smtp_login', $email->smtp_login) }}">
                        @error('smtp_login')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="smtp_password" class="form-label">SMTP Password</label>
                        <input type="password" class="form-control @error('smtp_password') is-invalid @enderror"
                            id="smtp_password" name="smtp_password" placeholder="Leave blank to keep current password">
                        <small class="form-text text-muted">Only enter if you want to change it</small>
                        @error('smtp_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="smtp_domain" class="form-label">SMTP Domain</label>
                        <input type="text" class="form-control @error('smtp_domain') is-invalid @enderror"
                            id="smtp_domain" name="smtp_domain" value="{{ old('smtp_domain', $email->smtp_domain) }}">
                        @error('smtp_domain')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="smtp_authentication" class="form-label">SMTP Authentication</label>
                        <select class="form-select @error('smtp_authentication') is-invalid @enderror" id="smtp_authentication" name="smtp_authentication">
                            <option value="plain" {{ old('smtp_authentication', $email->smtp_authentication) == 'plain' ? 'selected' : '' }}>Plain</option>
                            <option value="login" {{ old('smtp_authentication', $email->smtp_authentication) == 'login' ? 'selected' : '' }}>Login</option>
                            <option value="cram_md5" {{ old('smtp_authentication', $email->smtp_authentication) == 'cram_md5' ? 'selected' : '' }}>CRAM-MD5</option>
                        </select>
                        @error('smtp_authentication')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="smtp_enable_starttls_auto" name="smtp_enable_starttls_auto" value="1"
                                {{ old('smtp_enable_starttls_auto', $email->smtp_enable_starttls_auto) ? 'checked' : '' }}>
                            <label class="form-check-label" for="smtp_enable_starttls_auto">
                                Enable STARTTLS
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="smtp_enable_ssl_tls" name="smtp_enable_ssl_tls" value="1"
                                {{ old('smtp_enable_ssl_tls', $email->smtp_enable_ssl_tls) ? 'checked' : '' }}>
                            <label class="form-check-label" for="smtp_enable_ssl_tls">
                                Enable SSL/TLS
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="smtp_openssl_verify_mode" class="form-label">OpenSSL Verify Mode</label>
                        <select class="form-select @error('smtp_openssl_verify_mode') is-invalid @enderror" id="smtp_openssl_verify_mode" name="smtp_openssl_verify_mode">
                            <option value="none" {{ old('smtp_openssl_verify_mode', $email->smtp_openssl_verify_mode) == 'none' ? 'selected' : '' }}>None</option>
                            <option value="peer" {{ old('smtp_openssl_verify_mode', $email->smtp_openssl_verify_mode) == 'peer' ? 'selected' : '' }}>Peer</option>
                            <option value="client_once" {{ old('smtp_openssl_verify_mode', $email->smtp_openssl_verify_mode) == 'client_once' ? 'selected' : '' }}>Client Once</option>
                            <option value="fail_if_no_peer_cert" {{ old('smtp_openssl_verify_mode', $email->smtp_openssl_verify_mode) == 'fail_if_no_peer_cert' ? 'selected' : '' }}>Fail if No Peer Cert</option>
                        </select>
                        @error('smtp_openssl_verify_mode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Update Email Channel</button>
                        <a href="{{ route('admin.channels.emails.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
