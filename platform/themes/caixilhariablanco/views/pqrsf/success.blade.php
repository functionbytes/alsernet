@extends('template::layouts.default')

@section('title', 'Solicitud radicada')

@section('content')


<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">

                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">

                        {{-- Icono de exito --}}
                        <div class="mb-4 position-relative d-inline-block">
                            <div class="success-bg-glow"></div>
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle position-relative"
                                 style="width:120px;height:120px;background:linear-gradient(135deg,#13C672 0%,#90bb13 100%);box-shadow:0 8px 24px rgba(19,198,114,0.4);">
                                <i class="fas fa-check-circle text-white" style="font-size:4rem;"></i>
                            </div>
                        </div>

                        <h2 class="fw-bold mb-3">Solicitud radicada exitosamente</h2>

                        <p class="text-muted mb-4">
                            Su solicitud ha sido recibida y registrada en nuestro sistema.
                            @if(!$attention->is_anonymous && $attention->customer_email)
                                <br>Se ha enviado una confirmacion a <strong style="color:#90bb13;">{{ $attention->customer_email }}</strong>.
                            @endif
                        </p>

                        {{-- Numero de radicado --}}
                        <div class="radicado-box mb-4">
                            <p class="text-muted mb-2 small">Numero de radicado</p>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <h3 class="fw-bold mb-0" style="letter-spacing:2px;color:#90bb13;" id="radicadoNumber">
                                    {{ $attention->radicado }}
                                </h3>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="copyBtn" onclick="copyRadicado()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Detalles --}}
                        <div class="row text-start mb-4">
                            <div class="col-sm-6 mb-3">
                                <p class="text-muted mb-1 small">Asunto</p>
                                <p class="fw-semibold mb-0">{{ $attention->subject }}</p>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <p class="text-muted mb-1 small">Estado</p>
                                <span class="badge bg-info">{{ $attention->status->label() }}</span>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <p class="text-muted mb-1 small">Fecha de radicacion</p>
                                <p class="fw-semibold mb-0">{{ $attention->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        {{-- ¿Que sigue? --}}
                        <div class="what-next-section mb-4">
                            <h5 class="fw-bold mb-4">¿Que sigue ahora?</h5>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="next-step-icon flex-shrink-0">
                                        <i class="fas fa-inbox"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">1. Recepcion</h6>
                                        <p class="text-muted small mb-0">Su solicitud ha sido recibida y asignada automaticamente</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="next-step-icon flex-shrink-0">
                                        <i class="fas fa-gears"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">2. Revision</h6>
                                        <p class="text-muted small mb-0">El departamento correspondiente analizara su caso</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="next-step-icon flex-shrink-0">
                                        <i class="fas fa-reply"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">3. Respuesta</h6>
                                        <p class="text-muted small mb-0">Recibira una respuesta segun el medio elegido</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Alerta importante --}}
                        <div class="alert border-0 text-start mb-4" style="background:rgba(254,201,15,0.1);">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle me-3 mt-1" style="color:#FEC90F;font-size:1.25rem;"></i>
                                <div>
                                    <strong>Importante:</strong> Guarde su numero de radicado
                                    <strong style="color:#90bb13;">{{ $attention->radicado }}</strong>.
                                    Lo necesitara para consultar el estado de su solicitud.
                                </div>
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <a href="{{ route('pqrsf.tracking.result', $attention->radicado) }}" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-magnifying-glass me-2"></i>Consultar estado
                            </a>
                            <a href="{{ route('pqrsf.form') }}" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-file-pen me-2"></i>Radicar otra solicitud
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

@push('css')
<style>
    .success-bg-glow {
        position: absolute;
        width: 200px;
        height: 200px;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(19,198,114,0.15) 0%, transparent 70%);
        border-radius: 50%;
        animation: pulse-glow 2s ease-in-out infinite;
    }

    @keyframes pulse-glow {
        0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
        50% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.8; }
    }

    .radicado-box {
        background: linear-gradient(135deg, rgba(144,187,19,0.05), rgba(19,198,114,0.05));
        border: 2px solid #90bb13;
        border-radius: 1rem;
        padding: 1.5rem;
    }

    #copyBtn.copied {
        background-color: #13C672;
        border-color: #13C672;
        color: #fff;
    }

    .what-next-section {
        text-align: left;
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 1rem;
    }

    .next-step-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #90bb13 0%, #13C672 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.25rem;
        box-shadow: 0 4px 12px rgba(144,187,19,0.3);
    }
</style>
@endpush

@push('scripts')
<script>
function copyRadicado() {
    var radicado = document.getElementById('radicadoNumber').textContent.trim();
    var btn = document.getElementById('copyBtn');

    navigator.clipboard.writeText(radicado).then(function () {
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('copied');
        setTimeout(function () {
            btn.innerHTML = original;
            btn.classList.remove('copied');
        }, 2000);
    });
}
</script>
@endpush

@endsection
