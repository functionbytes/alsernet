@extends('layouts.theme')

@section('title', 'Emails Enviados - ' . $attention->radicado)

@section('content')

    @include('core::components.card', ['title' => 'Historial de Emails'])

    <div class="row">
        <div class="col-lg-12">
            <!-- Info del peticiones -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Radicado: <span class="text-primary">{{ $attention->radicado }}</span></h5>
                            <p class="mb-0 text-muted">
                                {{ $attention->type->name }} - {{ $attention->full_name }}
                            </p>
                        </div>
                        <a href="{{ route('attention.show', $attention->uid) }}" class="btn btn-outline-primary">
                            <i class="fa-duotone fa-arrow-left"></i> Volver al peticiones
                        </a>
                    </div>
                </div>
            </div>

            <!-- Lista de emails -->
            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Emails Enviados ({{ $mails->count() }})</h5>
                </div>
                <div class="card-body p-0">
                    @forelse($mails as $mail)
                        <div class="email-item border-bottom p-3 {{ $loop->last ? '' : 'hover-bg-light' }}">
                            <div class="row align-items-center">
                                <div class="col-md-1 text-center">
                                    <div class="email-icon">
                                        @if($mail->status === 'sent')
                                            <i class="fa-duotone fa-check-circle fa-2x text-success"></i>
                                        @elseif($mail->status === 'failed')
                                            <i class="fa-duotone fa-times-circle fa-2x text-danger"></i>
                                        @else
                                            <i class="fa-duotone fa-clock fa-2x text-warning"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <h6 class="mb-1">{{ $mail->subject }}</h6>
                                    <p class="mb-1 text-muted">
                                        <i class="fa-duotone fa-envelope me-1"></i> Para: {{ $mail->to }}
                                    </p>
                                    <p class="mb-0 text-muted">
                                        <i class="fa-duotone fa-calendar me-1"></i>
                                        {{ $mail->created_at->format('d/m/Y H:i:s') }}
                                        ({{ $mail->created_at->diffForHumans() }})
                                    </p>
                                </div>
                                <div class="col-md-2 text-center">
                                    @if($mail->status === 'sent')
                                        <span class="badge bg-success">Enviado</span>
                                    @elseif($mail->status === 'failed')
                                        <span class="badge bg-danger">Falló</span>
                                    @elseif($mail->status === 'pending')
                                        <span class="badge bg-warning">Pendiente</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $mail->status }}</span>
                                    @endif
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-sm btn-outline-primary" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#email-{{ $mail->id }}">
                                        <i class="fa-duotone fa-eye"></i> Ver
                                    </button>
                                </div>
                            </div>

                            <!-- Contenido colapsable -->
                            <div class="collapse mt-3" id="email-{{ $mail->id }}">
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <div class="mb-2">
                                            <strong>De:</strong> {{ $mail->from ?? config('mail.from.address') }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Para:</strong> {{ $mail->to }}
                                        </div>
                                        @if($mail->cc)
                                            <div class="mb-2">
                                                <strong>CC:</strong> {{ $mail->cc }}
                                            </div>
                                        @endif
                                        <div class="mb-3">
                                            <strong>Asunto:</strong> {{ $mail->subject }}
                                        </div>

                                        <hr>

                                        <div class="email-content">
                                            @if($mail->body)
                                                {!! clean_html($mail->body) !!}
                                            @else
                                                <p class="text-muted">No hay contenido disponible</p>
                                            @endif
                                        </div>

                                        @if($mail->error_message)
                                            <hr>
                                            <div class="alert alert-danger mb-0">
                                                <strong>Error:</strong> {{ $mail->error_message }}
                                            </div>
                                        @endif

                                        @if($mail->sent_at)
                                            <hr>
                                            <small class="text-muted">
                                                <i class="fa-duotone fa-clock"></i>
                                                Enviado el {{ $mail->sent_at->format('d/m/Y H:i:s') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-5 text-center">
                            <i class="fa-duotone fa-inbox fa-4x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No se han enviado emails para este peticiones</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if($mails->hasPages())
                <div class="card card-body mt-3">
                    {{ $mails->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .hover-bg-light:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .email-content {
            max-height: 500px;
            overflow-y: auto;
            padding: 15px;
            background: white;
            border-radius: 5px;
        }

        .email-item {
            transition: background-color 0.2s;
        }
    </style>
@endpush
