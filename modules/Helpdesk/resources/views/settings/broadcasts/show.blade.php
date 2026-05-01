@extends('layouts.theme')

@section('title', $broadcast->name)

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">

        {{-- Broadcast info --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $broadcast->name }}</h5>
                            <p class="small mb-0 text-muted">Detalle del broadcast y sus destinatarios</p>
                        </div>
                        <a href="{{ route('settings.helpdesk.broadcasts.index') }}" class="btn btn-light">
                            Volver
                        </a>
                    </div>
                </div>

                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted small">Canal</dt>
                                <dd class="col-sm-7">
                                    @php
                                        $channelColors = [
                                            'whatsapp' => 'success',
                                            'facebook' => 'primary',
                                            'instagram' => 'danger',
                                            'email' => 'info',
                                            'widget' => 'secondary',
                                        ];
                                        $channelColor = $channelColors[$broadcast->channel] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $channelColor }}-subtle text-{{ $channelColor }}">
                                        {{ ucfirst($broadcast->channel) }}
                                    </span>
                                </dd>

                                <dt class="col-sm-5 text-muted small mt-2">Estado</dt>
                                <dd class="col-sm-7 mt-2">
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'scheduled' => 'info',
                                            'sending' => 'warning',
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                        ];
                                        $statusLabels = [
                                            'draft' => 'Borrador',
                                            'scheduled' => 'Programado',
                                            'sending' => 'Enviando',
                                            'sent' => 'Enviado',
                                            'failed' => 'Fallido',
                                        ];
                                        $statusColor = $statusColors[$broadcast->status] ?? 'secondary';
                                        $statusLabel = $statusLabels[$broadcast->status] ?? $broadcast->status;
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                        {{ $statusLabel }}
                                    </span>
                                </dd>

                                <dt class="col-sm-5 text-muted small mt-2">Tipo</dt>
                                <dd class="col-sm-7 mt-2">
                                    {{ $broadcast->template_type === 'hsm' ? 'Template HSM' : 'Texto libre' }}
                                </dd>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted small">Destinatarios</dt>
                                <dd class="col-sm-7">
                                    <span class="fw-semibold">{{ number_format($broadcast->recipients_count ?? 0) }}</span>
                                </dd>

                                <dt class="col-sm-5 text-muted small mt-2">Entregados</dt>
                                <dd class="col-sm-7 mt-2">
                                    <span class="text-success fw-semibold">{{ number_format($broadcast->delivered_count ?? 0) }}</span>
                                </dd>

                                <dt class="col-sm-5 text-muted small mt-2">Fallidos</dt>
                                <dd class="col-sm-7 mt-2">
                                    <span class="text-danger fw-semibold">{{ number_format($broadcast->failed_count ?? 0) }}</span>
                                </dd>

                                <dt class="col-sm-5 text-muted small mt-2">Fecha programada</dt>
                                <dd class="col-sm-7 mt-2">
                                    {{ $broadcast->scheduled_at ? $broadcast->scheduled_at->format('d/m/Y H:i') : '—' }}
                                </dd>

                                <dt class="col-sm-5 text-muted small mt-2">Fecha enviado</dt>
                                <dd class="col-sm-7 mt-2">
                                    {{ $broadcast->sent_at ? $broadcast->sent_at->format('d/m/Y H:i') : '—' }}
                                </dd>
                            </dl>
                        </div>

                        @if($broadcast->body)
                            <div class="col-12">
                                <label class="text-muted small fw-semibold">Mensaje</label>
                                <div class="bg-light rounded p-3 mt-1">
                                    <p class="mb-0 small" style="white-space: pre-wrap;">{{ $broadcast->body }}</p>
                                </div>
                            </div>
                        @endif

                        @if($broadcast->template_id)
                            <div class="col-12 col-md-6">
                                <label class="text-muted small fw-semibold">Template HSM</label>
                                <div class="mt-1">
                                    <code>{{ $broadcast->template_id }}</code>
                                </div>
                            </div>
                        @endif

                        @if($broadcast->filters)
                            <div class="col-12 col-md-6">
                                <label class="text-muted small fw-semibold">Filtros</label>
                                <div class="mt-1 d-flex flex-wrap gap-2">
                                    @foreach(array_filter($broadcast->filters) as $key => $value)
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $key }}: {{ $value }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Recipients table --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Destinatarios</h6>
                </div>

                <div class="card-body">
                    @if($recipients->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID cliente</th>
                                        <th>ID externo</th>
                                        <th>Estado</th>
                                        <th>Enviado</th>
                                        <th>Error</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recipients as $recipient)
                                        <tr>
                                            <td>
                                                <span class="text-muted small">#{{ $recipient->customer_id }}</span>
                                            </td>
                                            <td>
                                                @if($recipient->external_id)
                                                    <code class="small">{{ $recipient->external_id }}</code>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $rStatusColors = [
                                                        'pending' => 'secondary',
                                                        'sent' => 'info',
                                                        'delivered' => 'success',
                                                        'read' => 'primary',
                                                        'failed' => 'danger',
                                                    ];
                                                    $rStatusLabels = [
                                                        'pending' => 'Pendiente',
                                                        'sent' => 'Enviado',
                                                        'delivered' => 'Entregado',
                                                        'read' => 'Leido',
                                                        'failed' => 'Fallido',
                                                    ];
                                                    $rColor = $rStatusColors[$recipient->status] ?? 'secondary';
                                                    $rLabel = $rStatusLabels[$recipient->status] ?? $recipient->status;
                                                @endphp
                                                <span class="badge bg-{{ $rColor }}-subtle text-{{ $rColor }}">
                                                    {{ $rLabel }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($recipient->sent_at)
                                                    <span class="small text-muted">{{ $recipient->sent_at->format('d/m/Y H:i') }}</span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($recipient->error)
                                                    <span class="text-danger small" title="{{ $recipient->error }}">
                                                        {{ Str::limit($recipient->error, 60) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No hay destinatarios registrados para este broadcast.</p>
                        </div>
                    @endif
                </div>

                @if($recipients->hasPages())
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                Mostrando <strong>{{ $recipients->firstItem() }}</strong> a <strong>{{ $recipients->lastItem() }}</strong>
                                de <strong>{{ $recipients->total() }}</strong> destinatarios
                            </div>
                            {{ $recipients->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
