@php $t = fn (string $key) => __('helpdeskaiprompts::ai-prompts.actions.'.$key); @endphp
<h6 class="fw-semibold mb-3">{{ $t('section_http') }}</h6>
@if($allowedHosts === [])
    <div class="alert alert-warning small"><i class="fas fa-triangle-exclamation me-1"></i>{{ $t('hosts_none') }}</div>
@else
    <div class="alert alert-info small">
        <i class="fas fa-circle-info me-1"></i>{{ $t('hosts_allowed') }}
        @foreach($allowedHosts as $host)<code class="ms-1">{{ $host }}</code>@endforeach
    </div>
@endif
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <label class="form-label" for="a-method">{{ $t('field_method') }}</label>
        <select name="config[method]" id="a-method" class="form-select">
            @foreach(['GET', 'POST'] as $method)
                <option value="{{ $method }}" @selected(strtoupper($form['method']) === $method)>{{ $method }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error="config.method"></div>
    </div>
    <div class="col-md-9">
        <label class="form-label" for="a-url">{{ $t('field_url') }} <span class="text-danger">*</span></label>
        <input type="text" name="config[url]" id="a-url" class="form-control font-monospace small" maxlength="2048"
               placeholder="https://api.example.com/search?q=@{{args.q}}" value="{{ $form['url'] }}">
        <div class="invalid-feedback" data-error="config.url"></div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="a-headers">{{ $t('field_headers') }}</label>
        <textarea name="config[headers]" id="a-headers" class="form-control font-monospace small" rows="4" spellcheck="false"
                  placeholder='{"X-Source": "helpdesk"}'>{{ $form['headers'] }}</textarea>
        <small class="text-muted">{{ $t('field_headers_help') }}</small>
        <div class="invalid-feedback" data-error="config.headers"></div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="a-body">{{ $t('field_body') }}</label>
        <textarea name="config[body]" id="a-body" class="form-control font-monospace small" rows="4" spellcheck="false"
                  placeholder='{"term": "@{{args.q}}"}'>{{ $form['body'] }}</textarea>
        <small class="text-muted">{{ $t('field_body_help') }}</small>
        <div class="invalid-feedback" data-error="config.body"></div>
    </div>
    <div class="col-12"><div class="invalid-feedback" data-error="config"></div></div>
    <div class="col-md-4">
        <label class="form-label" for="a-auth-type">{{ $t('field_auth') }}</label>
        <select name="auth[type]" id="a-auth-type" class="form-select">
            @foreach(['none', 'bearer', 'header'] as $auth)
                <option value="{{ $auth }}" @selected($form['auth_type'] === $auth)>{{ $t('auth_'.$auth) }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error="auth.type"></div>
    </div>
    <div class="col-md-4 d-none" id="auth-header-group">
        <label class="form-label" for="a-auth-header">{{ $t('field_auth_header') }}</label>
        <input type="text" name="auth[header]" id="a-auth-header" class="form-control" maxlength="64" value="{{ $form['auth_header'] }}">
        <div class="invalid-feedback" data-error="auth.header"></div>
    </div>
    <div class="col-md-4 d-none" id="auth-secret-group">
        <label class="form-label" for="a-auth-value">{{ $t('field_auth_secret') }}</label>
        <input type="password" name="auth[value]" id="a-auth-value" class="form-control" maxlength="2000" autocomplete="new-password"
               placeholder="{{ $form['has_secret'] ? $t('secret_keep') : '' }}">
        <small class="text-muted">{{ $form['has_secret'] ? $t('secret_saved') : $t('secret_help') }}</small>
        <div class="invalid-feedback" data-error="auth.value"></div>
        <div class="invalid-feedback" data-error="config.auth"></div>
    </div>
</div>
