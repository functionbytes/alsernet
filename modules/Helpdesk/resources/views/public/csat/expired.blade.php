@extends('helpdesk::public.csat.layout')

@section('title', 'Encuesta expirada - ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 csat-wrapper">

            <div class="text-center mb-4">
                <span class="fw-bold fs-5">{{ config('app.name') }}</span>
            </div>

            <div class="card shadow-sm text-center">
                <div class="card-body p-4 p-md-5">

                    <i class="fas fa-clock-rotate-left csat-icon-expired mb-3"></i>
                    <h5 class="fw-bold mb-2">Esta encuesta ha expirado</h5>
                    <p class="text-muted small mb-4">
                        El período para responder esta valoración ha concluido.
                        Las encuestas tienen un tiempo límite para proteger la privacidad.
                    </p>

                    <hr class="my-3">

                    <p class="text-muted small mb-0">
                        Si necesitas ayuda o tienes alguna consulta, no dudes en
                        contactarnos a través de nuestros canales de soporte.
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('css')
<style>
body { background-color: #f6f7f9; }

.csat-wrapper { max-width: 480px; }

.csat-icon-expired {
    font-size: 3rem;
    color: #6c757d;
    display: block;
}
</style>
@endpush
