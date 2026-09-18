{{--
    Formulario compartido por create/edit del canal de correo.
    $channel: array de la conexión al editar, null al crear.
--}}
@php
    $channel = $channel ?? null;
    $isEdit = $channel !== null;
    $field = fn (string $key, $default = null) => old($key, $channel[$key] ?? $default);

    // Al editar, los botones de "Probar conexion" viven en la tarjeta lateral
    // "Estado del canal" junto a Sincronizar/Eliminar, que es donde se agrupan
    // las acciones sobre el canal. Al crear no existe esa tarjeta todavia, asi
    // que se quedan aqui, debajo de los campos que prueban.
    $showTestButtons = $showTestButtons ?? true;
@endphp

<h6 class="fw-semibold mb-1">Conexion entrante (IMAP)</h6>
<p class="text-muted small mb-3">Buzon del que se leen los correos que se convierten en tickets</p>

<div class="row g-3 mb-4">

    <div class="col-12">
        <div class="mb-3">
            <label for="channelName" class="form-label">Nombre del canal <span class="text-brand">*</span></label>
            <input type="text" name="name" id="channelName"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ $field('name') }}"
                   placeholder="Ej: Soporte principal"
                   required>
            <small class="form-text text-muted">Solo se usa para identificar el canal en esta pantalla</small>
            @error('name')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-8">
        <div class="mb-3">
            <label for="channelHost" class="form-label">Servidor IMAP <span class="text-brand">*</span></label>
            <input type="text" name="host" id="channelHost"
                   class="form-control @error('host') is-invalid @enderror"
                   value="{{ $field('host') }}"
                   placeholder="imap.gmail.com"
                   required>
            @error('host')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="mb-3">
            <label for="channelPort" class="form-label">Puerto <span class="text-brand">*</span></label>
            <input type="number" name="port" id="channelPort"
                   class="form-control @error('port') is-invalid @enderror"
                   value="{{ $field('port', 993) }}"
                   required>
            @error('port')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="channelUsername" class="form-label">Usuario <span class="text-brand">*</span></label>
            <input type="text" name="username" id="channelUsername"
                   class="form-control @error('username') is-invalid @enderror"
                   value="{{ $field('username') }}"
                   placeholder="soporte@ejemplo.com"
                   required>
            @error('username')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="channelPassword" class="form-label">
                Contrasena @unless($isEdit)<span class="text-brand">*</span>@endunless
            </label>
            <input type="password" name="password" id="channelPassword"
                   class="form-control @error('password') is-invalid @enderror"
                   autocomplete="new-password"
                   {{ $isEdit ? '' : 'required' }}>
            @if($isEdit)
                <small class="form-text text-muted">Deja en blanco para conservar la contrasena actual</small>
            @endif
            @error('password')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="channelFolder" class="form-label">Carpeta</label>
            <input type="text" name="folder" id="channelFolder"
                   class="form-control @error('folder') is-invalid @enderror"
                   value="{{ $field('folder', 'INBOX') }}">
            <small class="form-text text-muted">Carpeta del buzon que se revisa. Por defecto INBOX</small>
            @error('folder')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="channelEncryption" class="form-label">Encriptacion</label>
            <select name="encryption" id="channelEncryption"
                    class="form-select select2 @error('encryption') is-invalid @enderror">
                <option value="ssl" {{ $field('encryption', 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                <option value="tls" {{ $field('encryption', 'ssl') === 'tls' ? 'selected' : '' }}>TLS</option>
            </select>
            @error('encryption')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label for="channelSyncSince" class="form-label">Sincronizar desde</label>
            <input type="date" name="sync_since" id="channelSyncSince"
                   class="form-control @error('sync_since') is-invalid @enderror"
                   value="{{ $field('sync_since') }}">
            <small class="form-text text-muted">Ignora los correos anteriores a esta fecha al sincronizar. Dejalo vacio para no filtrar</small>
            @error('sync_since')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    @if($showTestButtons)
        <div class="col-12">
            <button type="button" class="btn btn-light" id="btn-test-channel">Probar conexion IMAP</button>
            <div id="channelTestResult" class="d-none mt-3"></div>
        </div>
    @endif

</div>

<h6 class="fw-semibold mb-1">SMTP saliente</h6>
<p class="text-muted small mb-3">
    Para que las respuestas del agente salgan desde este mismo buzon, en vez del correo generico del sistema.
    Deja el servidor en blanco para responder con el correo por defecto.
</p>

<div class="row g-3 mb-4">

    <div class="col-12 col-md-8">
        <div class="mb-3">
            <label for="channelSmtpHost" class="form-label">Servidor SMTP</label>
            <input type="text" name="smtp_host" id="channelSmtpHost"
                   class="form-control @error('smtp_host') is-invalid @enderror"
                   value="{{ $field('smtp_host') }}"
                   placeholder="smtp.gmail.com">
            @error('smtp_host')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="mb-3">
            <label for="channelSmtpPort" class="form-label">Puerto</label>
            <input type="number" name="smtp_port" id="channelSmtpPort"
                   class="form-control @error('smtp_port') is-invalid @enderror"
                   value="{{ $field('smtp_port', 465) }}">
            @error('smtp_port')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label for="channelSmtpEncryption" class="form-label">Encriptacion</label>
            <select name="smtp_encryption" id="channelSmtpEncryption"
                    class="form-select select2 @error('smtp_encryption') is-invalid @enderror">
                <option value="ssl" {{ $field('smtp_encryption', 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                <option value="tls" {{ $field('smtp_encryption', 'ssl') === 'tls' ? 'selected' : '' }}>TLS</option>
            </select>
            <small class="form-text text-muted">Usa el mismo usuario y contrasena que el IMAP</small>
            @error('smtp_encryption')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    @if($showTestButtons)
        <div class="col-12">
            <button type="button" class="btn btn-light" id="btn-test-smtp-channel">Probar conexion SMTP</button>
            <div id="channelSmtpTestResult" class="d-none mt-3"></div>
        </div>
    @endif

</div>

<h6 class="fw-semibold mb-1">Comportamiento</h6>
<p class="text-muted small mb-3">Que hace el sistema con los correos que llegan a este buzon</p>

<div class="row g-3">

    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="channelCreateTickets" class="form-label">Crear tickets desde correos nuevos</label>
            <select name="create_tickets" id="channelCreateTickets" class="form-select select2">
                <option value="1" {{ (int) $field('create_tickets', 1) === 1 ? 'selected' : '' }}>Si</option>
                <option value="0" {{ (int) $field('create_tickets', 1) === 0 ? 'selected' : '' }}>No</option>
            </select>
            <small class="form-text text-muted">Cada correo sin ticket asociado abre uno nuevo</small>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="channelCreateReplies" class="form-label">Crear respuestas desde correos de seguimiento</label>
            <select name="create_replies" id="channelCreateReplies" class="form-select select2">
                <option value="1" {{ (int) $field('create_replies', 1) === 1 ? 'selected' : '' }}>Si</option>
                <option value="0" {{ (int) $field('create_replies', 1) === 0 ? 'selected' : '' }}>No</option>
            </select>
            <small class="form-text text-muted">Los correos que responden a un ticket se anaden como comentario</small>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="channelIsDefault" class="form-label">Canal por defecto para tickets sin correo entrante</label>
            <select name="is_default" id="channelIsDefault" class="form-select select2">
                <option value="1" {{ (int) $field('is_default', 0) === 1 ? 'selected' : '' }}>Si</option>
                <option value="0" {{ (int) $field('is_default', 0) === 0 ? 'selected' : '' }}>No</option>
            </select>
            <small class="form-text text-muted">
                Se usa para responder tickets que no nacieron de un correo (formularios, widget, alta manual) — solo puede haber un canal por defecto
            </small>
        </div>
    </div>

</div>

@push('scripts')
{{-- Solo datos: las URLs de servidor (fijas, no dependen de $channel). La
     lógica entera vive en email-channel-form.js. --}}
<script>
window.hdtEmailChannelFormConfig = {
    testImapUrl: @json(route('manager.helpdesk.settings.email-channels.test')),
    testSmtpUrl: @json(route('manager.helpdesk.settings.email-channels.test-smtp')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/email-channel-form.js') }}"></script>
@endpush
