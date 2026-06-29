@extends('layouts.theme')

@section('page_title', 'Preferencias de notificación')

@section('content')

<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Preferencias de notificación</h5>
                    <p class="small mb-0 text-muted">Configura cómo y cuándo recibir cada tipo de notificación</p>
                </div>
                <button class="btn btn-primary" id="btn-save-prefs">
                    <i class="fas fa-save me-1"></i>Guardar cambios
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            @if(empty($types))
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-bell-slash fa-2x mb-3"></i>
                    <p>No hay tipos de notificación registrados.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Notificación</th>
                                <th class="text-center" style="width:110px">
                                    <i class="fas fa-envelope me-1 text-muted"></i>Email
                                </th>
                                <th class="text-center" style="width:110px">
                                    <i class="fas fa-bell me-1 text-muted"></i>Panel
                                </th>
                                <th class="text-center" style="width:110px">
                                    <i class="fas fa-bolt me-1 text-muted"></i>Tiempo real
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($types as $key => $type)
                                @php
                                    $mailEnabled      = $prefs[$key]['mail']      ?? in_array('mail', $type['defaultChannels']);
                                    $databaseEnabled  = $prefs[$key]['database']  ?? in_array('database', $type['defaultChannels']);
                                    $broadcastEnabled = $prefs[$key]['broadcast'] ?? in_array('broadcast', $type['defaultChannels']);
                                @endphp
                                <tr class="border-bottom">
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold small">{{ $type['label'] }}</div>
                                        @if($type['description'])
                                            <div class="text-muted" style="font-size:.75rem">{{ $type['description'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                                            <input class="form-check-input pref-toggle"
                                                   type="checkbox"
                                                   data-type="{{ $key }}"
                                                   data-channel="mail"
                                                   {{ $mailEnabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                                            <input class="form-check-input pref-toggle"
                                                   type="checkbox"
                                                   data-type="{{ $key }}"
                                                   data-channel="database"
                                                   {{ $databaseEnabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                                            <input class="form-check-input pref-toggle"
                                                   type="checkbox"
                                                   data-type="{{ $key }}"
                                                   data-channel="broadcast"
                                                   {{ $broadcastEnabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>Los cambios se aplican a partir del siguiente envío.
            </small>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver a notificaciones
            </a>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
$('#btn-save-prefs').on('click', function () {
    const $btn = $(this);
    const preferences = [];

    $('.pref-toggle').each(function () {
        preferences.push({
            notification_type: $(this).data('type'),
            channel: $(this).data('channel'),
            enabled: this.checked,
        });
    });

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

    $.ajax({
        url: '{{ route("notifications.preferences.update") }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        contentType: 'application/json',
        data: JSON.stringify({ preferences }),
        success: function () {
            toastr.success('Preferencias guardadas correctamente');
        },
        error: function () {
            toastr.error('Error al guardar las preferencias');
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar cambios');
        },
    });
});
</script>
@endpush
