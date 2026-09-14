{{--
    Shared FTP/SFTP fields partial
    @param string $driver  'ftp' or 'sftp'
    @param array  $disk    Existing disk data for edit form (optional, defaults to [])
--}}
@php $disk ??= []; @endphp

<div class="col-12 col-md-6">
    <div class="mb-3">
        <label class="control-label col-form-label">
            Host <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control @error('host') is-invalid @enderror"
               name="host" value="{{ old('host', $disk['host'] ?? '') }}"
               placeholder="{{ $driver }}.ejemplo.com">
        @error('host')
            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
        @enderror
    </div>
</div>

<div class="col-12 col-md-6">
    <div class="mb-3">
        <label class="control-label col-form-label">Puerto</label>
        <input type="number" class="form-control @error('port') is-invalid @enderror"
               name="port" value="{{ old('port') ?? $disk['port'] ?? ($driver === 'ftp' ? '21' : '22') }}"
               placeholder="{{ $driver === 'ftp' ? '21' : '22' }}" min="1" max="65535">
        @error('port')
            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
        @enderror
    </div>
</div>

<div class="col-12 col-md-6">
    <div class="mb-3">
        <label class="control-label col-form-label">
            Usuario <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control @error('username') is-invalid @enderror"
               name="username" value="{{ old('username', $disk['username'] ?? '') }}">
        @error('username')
            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
        @enderror
    </div>
</div>

<div class="col-12 col-md-6">
    <div class="mb-3">
        <label class="control-label col-form-label">Contraseña</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               name="password" value="{{ old('password') }}"
               placeholder="{{ isset($disk['name']) ? 'Dejar en blanco para mantener la actual' : 'Contraseña ' . strtoupper($driver) }}"
               @if(isset($disk['name'])) aria-describedby="password-help-{{ $driver }}" @endif>
        @if(isset($disk['name']))
            <small id="password-help-{{ $driver }}" class="form-text text-muted">
                Dejar vacío para mantener la contraseña actual
            </small>
        @endif
        @error('password')
            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
        @enderror
    </div>
</div>
