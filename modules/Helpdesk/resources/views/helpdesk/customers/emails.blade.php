@extends('layouts.theme')

@section('title', 'Emails - ' . $customer->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Emails del cliente'])
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-12">

            {{-- Info del cliente --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bv-icon-circle-64">
                                {{ strtoupper(substr($customer->name, 0, 2)) }}
                            </div>
                            <div>
                                <h5 class="mb-1">{{ $customer->name }}</h5>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-envelope me-1"></i> {{ $customer->email }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('manager.helpdesk.customers.show', $customer) }}" class="btn btn-outline-primary">
                            <i class="fa fa-arrow-left me-1"></i> Volver al cliente
                        </a>
                    </div>
                </div>
            </div>

            {{-- Lista de emails --}}
            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Emails enviados ({{ $mails->total() }})</h5>
                </div>
                <div class="card-body p-0">
                    @forelse($mails as $mail)
                        <div class="email-item border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-md-1 text-center">
                                    @if($mail->status === 'sent')
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    @elseif($mail->status === 'failed')
                                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                                    @else
                                        <i class="fas fa-clock fa-2x text-warning"></i>
                                    @endif
                                </div>
                                <div class="col-md-7">
                                    <h6 class="mb-1">{{ $mail->subject }}</h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-envelope me-1"></i> Para: {{ implode(', ', $mail->to_addresses ?? []) }}
                                    </p>
                                    @if($mail->module)
                                        <span class="badge bg-info-subtle text-info">{{ $mail->module }}</span>
                                    @endif
                                    @if($mail->entity_type)
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $mail->entity_type }} #{{ $mail->entity_id }}
                                        </span>
                                    @endif
                                    <p class="mb-0 text-muted small mt-1">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $mail->created_at->format('d/m/Y H:i:s') }}
                                        ({{ $mail->created_at->diffForHumans() }})
                                    </p>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-{{ $mail->status_color }}-subtle text-{{ $mail->status_color }}">
                                        {{ $mail->status_label }}
                                    </span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <a href="{{ route('helpdeskemaillog.show', $mail->uid) }}"
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-eye me-1"></i> Ver
                                    </a>
                                </div>
                            </div>

                            @if($mail->error_message)
                                <div class="mt-2">
                                    <div class="alert alert-danger mb-0 small py-2">
                                        <strong>Error:</strong> {{ $mail->error_message }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-5 text-center">
                            <i class="fas fa-inbox fa-4x text-muted mb-3 d-block opacity-25"></i>
                            <p class="text-muted mb-0">No se han enviado emails a este cliente</p>
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
    .email-item {
        transition: background-color 0.2s;
    }
    .email-item:hover {
        background-color: #f5f6f8;
    }
</style>
@endpush
