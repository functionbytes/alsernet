{{-- Campos compartidos por los modales de añadir/editar buzón de rebote --}}
<div class="mb-3">
    <label for="{{ $prefix }}-label" class="form-label fw-semibold">Etiqueta</label>
    <input type="text" class="form-control" id="{{ $prefix }}-label" name="label" maxlength="100" required>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label for="{{ $prefix }}-host" class="form-label fw-semibold">Servidor IMAP</label>
        <input type="text" class="form-control" id="{{ $prefix }}-host" name="host" maxlength="255" required>
    </div>
    <div class="col-md-4">
        <label for="{{ $prefix }}-port" class="form-label fw-semibold">Puerto</label>
        <input type="number" class="form-control" id="{{ $prefix }}-port" name="port" value="993" min="1" max="65535">
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="{{ $prefix }}-username" class="form-label fw-semibold">Usuario</label>
        <input type="text" class="form-control" id="{{ $prefix }}-username" name="username" maxlength="255" required>
    </div>
    <div class="col-md-6">
        <label for="{{ $prefix }}-password" class="form-label fw-semibold">Contraseña</label>
        <input type="password" class="form-control" id="{{ $prefix }}-password" name="password" maxlength="255"
               placeholder="{{ $prefix === 'edit' ? 'Dejar en blanco para conservar la actual' : '' }}"
               autocomplete="new-password">
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="{{ $prefix }}-encryption" class="form-label fw-semibold">Cifrado</label>
        <select class="evx-select form-select" id="{{ $prefix }}-encryption" name="encryption">
            <option value="ssl">SSL</option>
            <option value="tls">TLS</option>
            <option value="">Ninguno</option>
        </select>
    </div>
    <div class="col-md-6">
        <label for="{{ $prefix }}-folder" class="form-label fw-semibold">Carpeta</label>
        <input type="text" class="form-control" id="{{ $prefix }}-folder" name="folder" value="INBOX" maxlength="255">
    </div>
</div>

<div class="mb-3">
    <label for="{{ $prefix }}-module_scope" class="form-label fw-semibold">Módulos vigilados</label>
    <select class="evx-select form-select" id="{{ $prefix }}-module_scope" name="module_scope[]" multiple size="4">
        @foreach($availableModules ?? [] as $mod)
            <option value="{{ $mod }}">{{ $mod }}</option>
        @endforeach
    </select>
    <small class="text-muted">Sin ninguno seleccionado, este buzón cubre a todos los módulos (correlación por destinatario sin acotar) — usar cuando el buzón de rebotes es compartido por todo el sistema.</small>
</div>

<div class="mb-0">
    <label for="{{ $prefix }}-enabled" class="form-label fw-semibold">Estado</label>
    <select class="evx-select form-select" id="{{ $prefix }}-enabled" name="enabled">
        <option value="1">Activo — se revisa en cada corrida</option>
        <option value="0" selected>Inactivo</option>
    </select>
</div>
