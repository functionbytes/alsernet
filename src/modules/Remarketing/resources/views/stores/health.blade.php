@extends('layouts.theme')

@section('title', 'Configuración DNS — ' . $store->name)

@section('page_header')
    @php
        $passing = (int) $check['spf'] + (int) $check['dkim'] + (int) $check['dmarc'];
        $scoreColor = $passing === 3 ? 'success' : ($passing >= 1 ? 'warning' : 'danger');
    @endphp
    @include('core::components.card', [
        'title' => 'Configuración DNS del remitente',
        'subtitle' => $store->name . ' · ' . (parse_url($store->domain, PHP_URL_HOST) ?? $store->domain),
        'actions' => '<a href="' . route('remarketing.stores.index') . '" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                     </a>
                     <button id="btn-recheck" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-rotate me-1"></i> Verificar de nuevo
                     </button>',
    ])
@endsection

@section('content')

    @php
        $domain = parse_url($store->domain, PHP_URL_HOST) ?? $store->domain;
    @endphp

    {{-- Score banner --}}
    <div class="alert alert-{{ $scoreColor }}-subtle border-{{ $scoreColor }} d-flex align-items-center gap-3 mb-4">
        <div class="rounded-circle bg-{{ $scoreColor }}-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
            @if($passing === 3)
                <i class="fas fa-check-circle fs-5 text-success"></i>
            @elseif($passing >= 1)
                <i class="fas fa-exclamation-circle fs-5 text-warning"></i>
            @else
                <i class="fas fa-times-circle fs-5 text-danger"></i>
            @endif
        </div>
        <div>
            <div class="fw-bold">{{ $passing }}/3 verificaciones pasadas</div>
            <div class="small text-muted">
                @if($passing === 3)
                    El dominio <strong>{{ $domain }}</strong> está correctamente configurado para envío de email.
                @else
                    Completa la configuración DNS para mejorar la entregabilidad de tus campañas.
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3" id="checks-container">

        {{-- SPF --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-bottom p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-shield-halved text-primary"></i>
                        <h6 class="mb-0 fw-bold">SPF — Sender Policy Framework</h6>
                    </div>
                    @if($check['spf'])
                        <span class="badge bg-success-subtle text-success px-3 py-2">
                            <i class="fas fa-check me-1"></i> Configurado
                        </span>
                    @else
                        <span class="badge bg-danger-subtle text-danger px-3 py-2">
                            <i class="fas fa-times me-1"></i> No encontrado
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        El registro SPF autoriza los servidores que pueden enviar email en nombre de tu dominio.
                        Previene spoofing y mejora la entregabilidad.
                    </p>

                    @if($check['spf'])
                        <div class="bg-success-subtle rounded p-3 small">
                            <div class="fw-semibold text-success mb-1">Registro encontrado</div>
                            <code class="text-break">{{ $check['details']['spf']['record'] ?? '—' }}</code>
                        </div>
                    @else
                        <div class="alert alert-warning-subtle border-warning small mb-3">
                            <i class="fas fa-triangle-exclamation me-2 text-warning"></i>
                            No se encontró ningún registro SPF en <strong>{{ $domain }}</strong>.
                        </div>
                        <div class="fw-semibold small mb-2">Cómo configurarlo:</div>
                        <ol class="small ps-3 mb-3">
                            <li class="mb-1">Accede al panel DNS de tu proveedor de dominio.</li>
                            <li class="mb-1">Crea o edita un registro <strong>TXT</strong> en la raíz (<code>@</code>).</li>
                            <li class="mb-1">Si ya tienes un SPF, agrega <code>include:{{ $domain }}</code> antes del mecanismo final.</li>
                            <li>Si no tienes SPF, crea este registro:</li>
                        </ol>
                        <div class="bg-light rounded p-3 d-flex align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">Tipo: TXT &nbsp;·&nbsp; Host: <code>@</code> &nbsp;·&nbsp; TTL: 3600</div>
                                <code id="spf-record" class="d-block text-break">v=spf1 include:{{ $domain }} ~all</code>
                            </div>
                            <button class="btn btn-sm btn-light btn-copy" data-target="spf-record" title="Copiar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- DKIM --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-bottom p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-key text-primary"></i>
                        <h6 class="mb-0 fw-bold">DKIM — DomainKeys Identified Mail</h6>
                    </div>
                    @if($check['dkim'])
                        <span class="badge bg-success-subtle text-success px-3 py-2">
                            <i class="fas fa-check me-1"></i> Configurado
                        </span>
                    @else
                        <span class="badge bg-danger-subtle text-danger px-3 py-2">
                            <i class="fas fa-times me-1"></i> No encontrado
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        DKIM firma criptográficamente los emails salientes. Los servidores receptores verifican la firma
                        para confirmar que el mensaje no fue alterado en tránsito.
                    </p>

                    @if($check['dkim'])
                        <div class="bg-success-subtle rounded p-3 small">
                            <div class="fw-semibold text-success mb-1">Registro encontrado — selector: <code>{{ $check['details']['dkim']['selector'] ?? 'rmk' }}</code></div>
                            <code class="text-break">{{ $check['details']['dkim']['record'] ?? '—' }}</code>
                        </div>
                    @else
                        <div class="alert alert-warning-subtle border-warning small mb-3">
                            <i class="fas fa-triangle-exclamation me-2 text-warning"></i>
                            No se encontró el registro DKIM con selector <code>rmk</code> en <strong>{{ $domain }}</strong>.
                        </div>
                        <div class="fw-semibold small mb-2">Cómo configurarlo:</div>
                        <ol class="small ps-3 mb-3">
                            <li class="mb-1">Solicita la clave pública DKIM a tu administrador del sistema.</li>
                            <li class="mb-1">Crea un registro <strong>TXT</strong> con el host indicado abajo.</li>
                            <li>Pega el valor de la clave pública en el campo <em>Valor</em>.</li>
                        </ol>
                        <div class="bg-light rounded p-3 d-flex align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">Tipo: TXT &nbsp;·&nbsp; TTL: 3600</div>
                                <div class="small mb-1">Host: <code id="dkim-host">rmk._domainkey.{{ $domain }}</code></div>
                                <div class="small">Valor: <code>v=DKIM1; k=rsa; p=&lt;CLAVE_PUBLICA&gt;</code></div>
                            </div>
                            <button class="btn btn-sm btn-light btn-copy" data-target="dkim-host" title="Copiar host">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- DMARC --}}
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-bottom p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-lock text-primary"></i>
                        <h6 class="mb-0 fw-bold">DMARC — Domain-based Message Authentication</h6>
                    </div>
                    @if($check['dmarc'])
                        <span class="badge bg-success-subtle text-success px-3 py-2">
                            <i class="fas fa-check me-1"></i> Configurado
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning px-3 py-2">
                            <i class="fas fa-exclamation me-1"></i> Recomendado
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        DMARC define qué hacer con los emails que no pasan SPF o DKIM. También genera reportes para monitorizar
                        el uso del dominio en el envío de email.
                    </p>

                    @if($check['dmarc'])
                        <div class="bg-success-subtle rounded p-3 small">
                            <div class="fw-semibold text-success mb-1">Registro encontrado</div>
                            <code class="text-break">{{ $check['details']['dmarc']['record'] ?? '—' }}</code>
                        </div>
                    @else
                        <div class="alert alert-info-subtle border-info small mb-3">
                            <i class="fas fa-circle-info me-2 text-info"></i>
                            DMARC es opcional pero altamente recomendado. Sin él, Gmail y Outlook pueden marcar tus emails como spam.
                        </div>
                        <div class="fw-semibold small mb-2">Cómo configurarlo:</div>
                        <ol class="small ps-3 mb-3">
                            <li class="mb-1">Crea un registro <strong>TXT</strong> en <code>_dmarc.{{ $domain }}</code>.</li>
                            <li class="mb-1">Empieza con política <code>p=none</code> para monitorizar sin bloquear.</li>
                            <li>Una vez verificado el flujo, cambia a <code>p=quarantine</code> o <code>p=reject</code>.</li>
                        </ol>
                        <div class="bg-light rounded p-3 d-flex align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">Tipo: TXT &nbsp;·&nbsp; Host: <code>_dmarc.{{ $domain }}</code> &nbsp;·&nbsp; TTL: 3600</div>
                                <code id="dmarc-record" class="d-block text-break">v=DMARC1; p=none; rua=mailto:dmarc-rua@{{ $domain }}; ruf=mailto:dmarc-ruf@{{ $domain }}; fo=1</code>
                            </div>
                            <button class="btn btn-sm btn-light btn-copy" data-target="dmarc-record" title="Copiar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Propagation note --}}
    <div class="text-center text-muted small py-2">
        <i class="fas fa-clock me-1"></i>
        Los cambios DNS pueden tardar hasta 48 horas en propagarse globalmente.
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    // copy to clipboard
    $(document).on('click', '.btn-copy', function () {
        var target = $(this).data('target');
        var text = $('#' + target).text().trim();
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Copiado al portapapeles');
        });
    });

    // re-check via page reload (force fresh DNS query)
    $('#btn-recheck').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-rotate fa-spin me-1"></i> Verificando...');
        window.location.reload();
    });
});
</script>
@endpush
