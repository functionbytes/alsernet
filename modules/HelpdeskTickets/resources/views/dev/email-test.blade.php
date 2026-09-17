@extends('layouts.theme')

@section('title', 'Email test — Dev tools')

@section('page_header')
    @include('core::components.card', ['title' => 'Email test — Dev tools'])
@endsection

@section('content')
    <div class="row g-3">

        {{-- Left card: send email --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Enviar correo de prueba</h5>
                    <small class="text-muted">Envia directamente a Mailpit omitiendo el MAIL_HOST configurado</small>
                </div>
                <div class="card-body">
                    <form id="send-email-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="from_name">Nombre del remitente</label>
                                <input type="text" id="from_name" name="from_name" class="form-control" value="Cliente Test">
                                <div class="invalid-feedback" id="error-from_name"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="from_email">Email del remitente</label>
                                <input type="email" id="from_email" name="from_email" class="form-control" value="cliente@prueba.com">
                                <div class="invalid-feedback" id="error-from_email"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subject">Asunto</label>
                                <input type="text" id="subject" name="subject" class="form-control" value="Necesito ayuda con mi pedido">
                                <div class="invalid-feedback" id="error-subject"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="body">Cuerpo del mensaje</label>
                                <textarea id="body" name="body" class="form-control" rows="5">Hola, tengo un problema con mi pedido #12345. Por favor ayudenme a resolverlo.</textarea>
                                <div class="invalid-feedback" id="error-body"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="button" id="btn-send-email" class="btn btn-primary w-100 mb-2">
                        <span id="btn-send-text"><i class="fas fa-paper-plane me-1"></i> Enviar a Mailpit</span>
                        <span id="btn-send-spinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span> Enviando...
                        </span>
                    </button>
                    <p class="text-muted small mb-1">
                        El correo se envia a Mailpit (127.0.0.1:1025). Luego usa "Sincronizar" para crear el ticket.
                    </p>
                    <a href="http://localhost:8025" target="_blank" class="small">
                        <i class="fas fa-external-link-alt me-1"></i> Abrir Mailpit UI
                    </a>
                </div>
            </div>
        </div>

        {{-- Right card: sync --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Sincronizar y ver tickets</h5>
                    <small class="text-muted">Ejecuta <code>imap:emailticket --sync</code> y muestra los ultimos 5 tickets</small>
                </div>
                <div class="card-body">
                    <button type="button" id="btn-sync" class="btn btn-success w-100 mb-3">
                        <span id="btn-sync-text"><i class="fas fa-rotate me-1"></i> Sincronizar emails &rarr; tickets</span>
                        <span id="btn-sync-spinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span> Sincronizando...
                        </span>
                    </button>

                    <div id="tickets-result" class="text-muted small">
                        Haz click en Sincronizar para ver los ultimos tickets creados.
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
{{-- Solo datos: las URLs de servidor que el JS necesita. La lógica entera
     vive en dev-email-test.js. --}}
<script>
window.hdtDevEmailTestConfig = {
    sendUrl: @json(route('dev.email-test.send')),
    syncUrl: @json(route('dev.email-test.sync')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/dev-email-test.js') }}"></script>
@endpush
