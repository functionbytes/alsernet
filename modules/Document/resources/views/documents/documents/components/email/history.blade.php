<!-- Document Email History -->
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1 fw-bold">
                Emails enviados
            </h5>

            <p class="small mb-0 text-muted">Historial de correos electrónicos enviados al cliente</p>
        </div>

    </div>

    @php
        $recentMails = $document->mails()->orderBy('created_at', 'desc')->take(3)->get();
        $totalMails = $document->mails()->count();
    @endphp

    @if($recentMails->count() > 0)
        <div class="card-body p-4">
            @if($totalMails > 3)
                <div class="alert alert-info alert-sm mb-3 mx-3" role="alert">
                    <small><i class="fas fa-info-circle me-1"></i> Mostrando los últimos 3 emails de {{ $totalMails }} totales</small>
                </div>
            @endif

            <div class="email-history-list">
                @foreach($recentMails as $mail)
                    @php
                        $typeConfig = [
                            'request' => ['icon' => 'fa-duotone fa-paper-plane', 'bg' => 'primary-subtle', 'text' => 'primary'],
                            'reminder' => ['icon' => 'fa-duotone fa-bell', 'bg' => 'warning-subtle', 'text' => 'warning'],
                            'upload' => ['icon' => 'fa-duotone fa-cloud-upload-alt', 'bg' => 'info-subtle', 'text' => 'info'],
                            'approval' => ['icon' => 'fa-duotone fa-check', 'bg' => 'success-subtle', 'text' => 'success'],
                            'rejection' => ['icon' => 'fa-duotone fa-times', 'bg' => 'danger-subtle', 'text' => 'danger'],
                            'missing' => ['icon' => 'fa-duotone fa-exclamation', 'bg' => 'warning-subtle', 'text' => 'warning'],
                            'custom' => ['icon' => 'fa-duotone fa-envelope', 'bg' => 'secondary-subtle', 'text' => 'secondary'],
                        ];
                        $config = $typeConfig[$mail->email_type] ?? $typeConfig['custom'];
                    @endphp
                    <a href="{{ route('documents.emails.preview', [$document->uid, $mail->uid]) }}"
                       class="email-item d-block text-decoration-none"
                       target="_blank">
                        <div class="d-flex gap-3 align-items-start">
                            {{-- Icon Circle --}}

                            {{-- Content --}}
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold small text-dark">{{ $mail->email_type_label }}</span>
                                    @if($mail->status === 'sent')
                                        <span class="badge bg-success-subtle text-success">
                                            Enviado
                                        </span>
                                    @elseif($mail->status === 'failed')
                                        <span class="badge bg-danger-subtle text-white">
                                            Fallido
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">
                                            En cola
                                        </span>
                                    @endif
                                </div>

                                <p class="email-subject text-muted small mb-1" title="{{ $mail->subject }}">
                                    {{ Str::limit($mail->subject, 45) }}
                                </p>

                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">
                                        <i class="fa fa-calendar-alt me-1"></i>
                                        {{ $mail->sent_at ? $mail->sent_at->format('d/m/Y') : $mail->created_at->format('d/m/Y') }}
                                    </small>
                                    <small class="text-muted">
                                        <i class="fa fa-clock me-1"></i>
                                        {{ $mail->sent_at ? $mail->sent_at->format('H:i') : $mail->created_at->format('H:i') }}
                                    </small>
                                </div>
                            </div>

                        </div>
                    </a>
                @endforeach
            </div>
            <div class="border-top mt-3 pt-3" >
                <a href="{{ route('documents.emails', $document->uid) }}" class="btn btn-primary w-100 ">
                    Ver historial completo
                </a>
            </div>

        </div>
    @else
        <div class="card-body p-4 text-center">
            <div class="mb-3">
                <i class="fas fa-envelope text-muted" style="font-size: 2rem;"></i>
            </div>
            <p class="text-muted small mb-0">
                <strong>Sin emails enviados</strong>
            </p>
        </div>
    @endif
</div>

@push('styles')
    <style>
        .email-history-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .email-item {
            display: block;
            padding: 12px 16px;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
            color: inherit;
        }

        .email-item:hover {
            background-color: #f8f9fa;
        }

        .email-item:hover .email-arrow {
            transform: translateX(3px);
            color: #90bb13;
        }

        .email-item:last-child {
            border-bottom: none;
        }

        .email-icon-wrapper {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .email-subject {
            line-height: 1.4;
            margin: 0;
        }

        .email-arrow {
            display: flex;
            align-items: center;
            font-size: 12px;
            transition: all 0.2s ease;
            padding-top: 8px;
        }

        .min-width-0 {
            min-width: 0;
        }
    </style>
@endpush

