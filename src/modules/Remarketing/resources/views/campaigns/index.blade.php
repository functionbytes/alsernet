@extends('layouts.theme')

@section('title', 'Campañas')

@section('page_header')
    @include('core::components.card', ['title' => 'Campañas'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Campañas de email</h5>
                        <p class="small mb-0 text-muted">Envíos masivos y campañas programadas</p>
                    </div>
                    <a href="{{ route('remarketing.campaigns.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva campaña
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                @if($campaigns->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No hay campañas creadas todavía</p>
                        <a href="{{ route('remarketing.campaigns.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primera campaña
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Programado</th>
                                    <th class="text-center">Enviados</th>
                                    <th class="text-center">Open rate</th>
                                    <th class="text-center">CTR</th>
                                    <th>Revenue</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaigns as $campaign)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold small">{{ $campaign->name }}</div>
                                        </td>
                                        <td class="small text-truncate" style="max-width:200px">{{ $campaign->subject }}</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'draft'     => ['secondary', 'Borrador'],
                                                    'scheduled' => ['info',      'Programado'],
                                                    'sending'   => ['warning',   'Enviando'],
                                                    'sent'      => ['success',   'Enviado'],
                                                    'cancelled' => ['danger',    'Cancelado'],
                                                    'paused'    => ['secondary', 'Pausado'],
                                                ];
                                                [$color, $label] = $statusColors[$campaign->status] ?? ['secondary', $campaign->status];
                                            @endphp
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $label }}</span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $campaign->scheduled_at?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td class="text-center small">{{ number_format($campaign->sent) }}</td>
                                        <td class="text-center small">
                                            @if($campaign->sent > 0)
                                                {{ number_format(($campaign->opened / $campaign->sent) * 100, 1) }}%
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-center small">
                                            @if($campaign->sent > 0)
                                                {{ number_format(($campaign->clicked / $campaign->sent) * 100, 1) }}%
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="small fw-semibold">
                                            {{ $campaign->revenue > 0 ? number_format($campaign->revenue, 2) . ' €' : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if(in_array($campaign->status, ['draft', 'scheduled']))
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('remarketing.campaigns.edit', $campaign) }}">Editar</a>
                                                        </li>
                                                    @endif
                                                    @if($campaign->status === 'draft')
                                                        <li>
                                                            <button class="dropdown-item btn-schedule" data-id="{{ $campaign->id }}">Programar</button>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <button class="dropdown-item btn-send-test" data-id="{{ $campaign->id }}">Enviar prueba</button>
                                                    </li>
                                                    @if(in_array($campaign->status, ['scheduled', 'sending']))
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-cancel-campaign" data-id="{{ $campaign->id }}" data-name="{{ $campaign->name }}">Cancelar envío</button>
                                                        </li>
                                                    @endif
                                                    @if($campaign->status === 'draft')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-delete"
                                                                    data-id="{{ $campaign->id }}"
                                                                    data-name="{{ $campaign->name }}"
                                                                    data-url="{{ route('remarketing.campaigns.destroy', $campaign) }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($campaigns->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $campaigns->links() }}
                </div>
            @endif

        </div>

    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar campaña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar la campaña <strong id="delete-name"></strong>? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar campaña</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Send test modal --}}
    <div class="modal fade" id="sendTestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Enviar email de prueba</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Email de destino</label>
                    <input type="email" id="test-email" class="form-control" placeholder="test@ejemplo.com">
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" id="btn-confirm-test" class="btn btn-primary w-100 mb-2">Enviar prueba</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var currentCampaignId = null;

    $(document).on('click', '.btn-delete', function () {
        $('#delete-name').text($(this).data('name'));
        $('#deleteForm').attr('action', $(this).data('url'));
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-send-test', function () {
        currentCampaignId = $(this).data('id');
        $('#test-email').val('');
        $('#sendTestModal').modal('show');
    });

    $('#btn-confirm-test').on('click', function () {
        var email = $('#test-email').val();
        if (!email) { toastr.warning('Introduce un email válido'); return; }
        $.ajax({
            url: '/api/remarketing/campaigns/' + currentCampaignId + '/send-test',
            method: 'POST',
            data: { email: email },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message || 'Email de prueba enviado');
                $('#sendTestModal').modal('hide');
            },
            error: function () { toastr.error('Error al enviar el email de prueba'); }
        });
    });

    $(document).on('click', '.btn-cancel-campaign', function () {
        if (!confirm('¿Cancelar el envío de la campaña "' + $(this).data('name') + '"?')) return;
        $.ajax({
            url: '/api/remarketing/campaigns/' + $(this).data('id') + '/cancel',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) { toastr.success(res.message || 'Campaña cancelada'); location.reload(); },
            error: function () { toastr.error('Error al cancelar la campaña'); }
        });
    });
});
</script>
@endpush
