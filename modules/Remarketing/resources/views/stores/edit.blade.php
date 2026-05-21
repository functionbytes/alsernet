@extends('layouts.theme')

@section('title', 'Editar tienda')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar tienda'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('remarketing.stores.update', $store) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $store->name }}</h5>
                        <small class="text-muted">{{ ucfirst($store->platform) }} — {{ $store->domain }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Nombre de la tienda <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $store->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Configuración avanzada (JSON)</label>
                                <textarea name="settings_raw" rows="8"
                                          class="form-control font-monospace @error('settings_raw') is-invalid @enderror"
                                          placeholder='{"webhook_secret": "...", "sync_interval_minutes": 30}'>{{ old('settings_raw', json_encode($store->settings, JSON_PRETTY_PRINT)) }}</textarea>
                                @error('settings_raw')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Edita la configuración en formato JSON. Los cambios se aplican en la siguiente sincronización.</div>
                            </div>

                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                        <a href="{{ route('remarketing.stores.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Información de la tienda</h6>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Plataforma</dt>
                        <dd class="col-7">{{ ucfirst($store->platform) }}</dd>
                        <dt class="col-5 text-muted">Dominio</dt>
                        <dd class="col-7">{{ $store->domain }}</dd>
                        <dt class="col-5 text-muted">Estado</dt>
                        <dd class="col-7">
                            @if($store->status === 'active')
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ $store->status }}</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Última sync</dt>
                        <dd class="col-7">{{ $store->last_synced_at?->diffForHumans() ?? 'Nunca' }}</dd>
                        <dt class="col-5 text-muted">Creada</dt>
                        <dd class="col-7">{{ $store->created_at->format('d/m/Y') }}</dd>
                    </dl>
                </div>
            </div>

            {{-- DNS deliverability wizard --}}
            <div class="card">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Entregabilidad DNS</h6>
                    <button type="button" id="btn-dns-check" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-search me-1"></i> Verificar
                    </button>
                </div>
                <div class="card-body" id="dns-wizard">
                    <p class="text-muted small mb-0">
                        Comprueba los registros SPF, DKIM y DMARC del dominio
                        <strong>{{ parse_url($store->domain, PHP_URL_HOST) ?? $store->domain }}</strong>.
                    </p>

                    <div id="dns-results" class="d-none mt-3">
                        {{-- SPF --}}
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span id="spf-icon" class="flex-shrink-0 mt-1"></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">SPF</div>
                                <div id="spf-detail" class="text-muted small"></div>
                                <div id="spf-hint" class="d-none mt-1">
                                    <div class="text-muted small mb-1">Añade este registro TXT en tu DNS:</div>
                                    <code class="d-block bg-light border rounded px-2 py-1 small">v=spf1 include:spf.alsernet.com ~all</code>
                                </div>
                            </div>
                        </div>
                        {{-- DKIM --}}
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span id="dkim-icon" class="flex-shrink-0 mt-1"></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">DKIM</div>
                                <div id="dkim-detail" class="text-muted small"></div>
                                <div id="dkim-hint" class="d-none mt-1">
                                    <div class="text-muted small mb-1">Añade este registro TXT en tu DNS:</div>
                                    <code class="d-block bg-light border rounded px-2 py-1 small">rmk._domainkey.{{ parse_url($store->domain, PHP_URL_HOST) ?? $store->domain }}</code>
                                    <div class="text-muted small mt-1">Valor: contacta con soporte para obtener la clave pública DKIM de tu cuenta.</div>
                                </div>
                            </div>
                        </div>
                        {{-- DMARC --}}
                        <div class="d-flex align-items-start gap-2">
                            <span id="dmarc-icon" class="flex-shrink-0 mt-1"></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">DMARC</div>
                                <div id="dmarc-detail" class="text-muted small"></div>
                                <div id="dmarc-hint" class="d-none mt-1">
                                    <div class="text-muted small mb-1">Añade este registro TXT en <code>_dmarc.{{ parse_url($store->domain, PHP_URL_HOST) ?? $store->domain }}</code>:</div>
                                    <code class="d-block bg-light border rounded px-2 py-1 small">v=DMARC1; p=none; rua=mailto:dmarc@{{ parse_url($store->domain, PHP_URL_HOST) ?? $store->domain }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="dns-spinner" class="d-none text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="text-muted small ms-2">Consultando DNS…</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var dnsCheckUrl = @json(route('remarketing.stores.dns-check', $store));

    function renderCheck(passed, record) {
        var icon = passed
            ? '<i class="fas fa-circle-check text-success"></i>'
            : '<i class="fas fa-circle-xmark text-danger"></i>';
        var detail = passed
            ? (record ? '<span class="text-success">OK — ' + $('<span>').text(record).html() + '</span>' : '<span class="text-success">OK</span>')
            : '<span class="text-danger">No encontrado</span>';
        return { icon: icon, detail: detail };
    }

    $('#btn-dns-check').on('click', function () {
        $('#dns-spinner').removeClass('d-none');
        $('#dns-results').addClass('d-none');

        $.getJSON(dnsCheckUrl, function (data) {
            $('#dns-spinner').addClass('d-none');
            $('#dns-results').removeClass('d-none');

            var spfRec  = data.details && data.details.spf  ? data.details.spf.record  : null;
            var dkimRec = data.details && data.details.dkim ? data.details.dkim.record : null;
            var dmarcRec= data.details && data.details.dmarc? data.details.dmarc.record: null;

            var spf  = renderCheck(data.spf,  spfRec);
            var dkim = renderCheck(data.dkim, dkimRec);
            var dmarc= renderCheck(data.dmarc,dmarcRec);

            $('#spf-icon').html(spf.icon);
            $('#spf-detail').html(spf.detail);
            data.spf  ? $('#spf-hint').addClass('d-none')  : $('#spf-hint').removeClass('d-none');

            $('#dkim-icon').html(dkim.icon);
            $('#dkim-detail').html(dkim.detail);
            data.dkim ? $('#dkim-hint').addClass('d-none') : $('#dkim-hint').removeClass('d-none');

            $('#dmarc-icon').html(dmarc.icon);
            $('#dmarc-detail').html(dmarc.detail);
            data.dmarc? $('#dmarc-hint').addClass('d-none'): $('#dmarc-hint').removeClass('d-none');
        }).fail(function () {
            $('#dns-spinner').addClass('d-none');
            toastr.error('No se pudo verificar el DNS. Inténtalo de nuevo.');
        });
    });
});
</script>
@endpush
