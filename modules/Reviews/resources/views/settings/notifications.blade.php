@extends('layouts.theme')

@section('title', 'Preferencias de notificación')

@section('content')
    <div class="row">
        <!-- Formulario principal (izquierda) -->
        <div class="col-lg-8">
            <div class="card w-100">
                <form action="{{ route('settings.reviews.notifications.update') }}" method="POST" id="notificationsForm">
                    @csrf

                    <div class="card-body">
                        <h5 class="mb-2">Preferencias de notificación</h5>
                        <p class="card-subtitle mb-4">
                            Controla qué notificaciones deseas recibir y a través de qué canales.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th width="35%">Tipo de notificación</th>
                                        <th width="15%" class="text-center">Email</th>
                                        <th width="15%" class="text-center">In-App</th>
                                        <th width="15%" class="text-center">Slack</th>
                                        <th width="15%" class="text-center">Activado</th>
                                        <th width="5%" class="text-center">Test</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($types as $typeKey => $typeData)
                                        @php
                                            $preference = $preferences->get($typeKey);
                                            $isEnabled = $preference ? $preference->is_enabled : true;
                                            $enabledChannels = $preference ? $preference->channels : ['database'];
                                        @endphp
                                        <tr class="notification-row {{ $isEnabled ? '' : 'opacity-50' }}">
                                            <td>
                                                <input type="hidden" name="preferences[{{ $loop->index }}][notification_type]" value="{{ $typeKey }}">
                                                <div>
                                                    <strong>{{ $typeData['label'] }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $typeData['description'] }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input channel-checkbox"
                                                           type="checkbox"
                                                           name="preferences[{{ $loop->index }}][channels][]"
                                                           value="mail"
                                                           id="mail_{{ $typeKey }}"
                                                           {{ in_array('mail', $enabledChannels) ? 'checked' : '' }}
                                                           {{ !$isEnabled ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input channel-checkbox"
                                                           type="checkbox"
                                                           name="preferences[{{ $loop->index }}][channels][]"
                                                           value="database"
                                                           id="database_{{ $typeKey }}"
                                                           {{ in_array('database', $enabledChannels) ? 'checked' : '' }}
                                                           {{ !$isEnabled ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input channel-checkbox"
                                                           type="checkbox"
                                                           name="preferences[{{ $loop->index }}][channels][]"
                                                           value="slack"
                                                           id="slack_{{ $typeKey }}"
                                                           {{ in_array('slack', $enabledChannels) ? 'checked' : '' }}
                                                           {{ !$isEnabled ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input enable-toggle"
                                                           type="checkbox"
                                                           name="preferences[{{ $loop->index }}][is_enabled]"
                                                           value="1"
                                                           id="enabled_{{ $typeKey }}"
                                                           data-row-index="{{ $loop->index }}"
                                                           {{ $isEnabled ? 'checked' : '' }}>
                                                </div>
                                                <input type="hidden"
                                                       name="preferences[{{ $loop->index }}][is_enabled]"
                                                       value="0"
                                                       class="disabled-input">
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-light test-notification"
                                                        data-type="{{ $typeKey }}"
                                                        title="Enviar notificación de prueba">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        @if($typeData['supports_filters'])
                                            <tr class="filter-row {{ $isEnabled ? '' : 'd-none' }}" data-parent="{{ $typeKey }}">
                                                <td colspan="6" class="ps-5">
                                                    <div class="card bg-light border-0 mb-2">
                                                        <div class="card-body py-2">
                                                            <small class="text-muted d-block mb-2"><strong>Filtros opcionales:</strong></small>
                                                            <div class="row g-2">
                                                                <div class="col-md-4">
                                                                    <label class="form-label small">Rating mínimo</label>
                                                                    <select class="form-select form-select-sm select2"
                                                                            name="preferences[{{ $loop->index }}][filters][min_rating]">
                                                                        <option value="">Sin filtro</option>
                                                                        @for($i = 1; $i <= 5; $i++)
                                                                            <option value="{{ $i }}"
                                                                                {{ ($preference?->filters['min_rating'] ?? null) == $i ? 'selected' : '' }}>
                                                                                {{ $i }} estrella{{ $i > 1 ? 's' : '' }}
                                                                            </option>
                                                                        @endfor
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label small">Rating máximo</label>
                                                                    <select class="form-select form-select-sm select2"
                                                                            name="preferences[{{ $loop->index }}][filters][max_rating]">
                                                                        <option value="">Sin filtro</option>
                                                                        @for($i = 1; $i <= 5; $i++)
                                                                            <option value="{{ $i }}"
                                                                                {{ ($preference?->filters['max_rating'] ?? null) == $i ? 'selected' : '' }}>
                                                                                {{ $i }} estrella{{ $i > 1 ? 's' : '' }}
                                                                            </option>
                                                                        @endfor
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                            Guardar preferencias
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel informativo (derecha) -->
        <div class="col-lg-4">
            <!-- Guía rápida -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Canales de notificación
                    </h6>
                    <div class="mb-3">
                        <div class="d-flex align-items-start mb-2">
                            <i class="fas fa-envelope text-primary me-2 mt-1"></i>
                            <div>
                                <strong>Email</strong>
                                <p class="text-muted mb-0">Recibe notificaciones en tu correo electrónico</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-2">
                            <i class="fas fa-bell text-info me-2 mt-1"></i>
                            <div>
                                <strong>In-App</strong>
                                <p class="text-muted mb-0">Notificaciones dentro de la plataforma</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="fab fa-slack text-success me-2 mt-1"></i>
                            <div>
                                <strong>Slack</strong>
                                <p class="text-muted mb-0">Recibe notificaciones en tu canal de Slack</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Tipos de notificación
                    </h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><strong>Nueva reseña:</strong> Cualquier reseña nueva recibida</li>
                        <li class="mb-2"><strong>Reseña negativa:</strong> Solo reseñas de 1-3 estrellas</li>
                        <li class="mb-2"><strong>Reseña positiva:</strong> Solo reseñas de 4-5 estrellas</li>
                        <li class="mb-2"><strong>Exportación lista:</strong> Cuando termina una exportación</li>
                        <li class="mb-2"><strong>Respuesta publicada:</strong> Cuando se publica una respuesta</li>
                        <li class="mb-2"><strong>Conexión expirando:</strong> Cuando una conexión está por expirar</li>
                        <li><strong>Resumen diario:</strong> Resumen de actividad diaria</li>
                    </ul>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3 text-info">
                        Filtros
                    </h6>
                    <p class="text-muted mb-0">
                        Algunos tipos de notificación permiten filtros adicionales para mayor control.
                        Por ejemplo, puedes recibir notificaciones solo de reseñas con 3 estrellas o menos.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Inicializar: deshabilitar hidden inputs para notificaciones ya activadas
        $('.enable-toggle:checked').each(function() {
            $(this).siblings('.disabled-input').prop('disabled', true);
        });

        // Toggle enable/disable functionality
        $('.enable-toggle').on('change', function() {
            const rowIndex = $(this).data('row-index');
            const $row = $(this).closest('tr');
            const $filterRow = $row.next('.filter-row');
            const isEnabled = $(this).is(':checked');

            // Toggle row opacity
            $row.toggleClass('opacity-50', !isEnabled);

            // Enable/disable channel checkboxes
            $row.find('.channel-checkbox').prop('disabled', !isEnabled);

            // Show/hide filter row
            if ($filterRow.length) {
                $filterRow.toggleClass('d-none', !isEnabled);
            }

            // Update hidden input value
            if (isEnabled) {
                $(this).siblings('.disabled-input').prop('disabled', true);
            } else {
                $(this).siblings('.disabled-input').prop('disabled', false);
            }
        });

        // Test notification
        $('.test-notification').on('click', function() {
            const type = $(this).data('type');
            const $button = $(this);
            const originalHtml = $button.html();

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '{{ route("settings.reviews.notifications.test", ":type") }}'.replace(':type', type),
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success(response.message || 'Notificación de prueba enviada');
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al enviar notificación de prueba';
                    toastr.error(message);
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Ensure at least one channel is selected
        $('#notificationsForm').on('submit', function(e) {
            let hasError = false;

            $('.notification-row').each(function() {
                const $row = $(this);
                const $enableToggle = $row.find('.enable-toggle');

                if ($enableToggle.is(':checked')) {
                    const $checkedChannels = $row.find('.channel-checkbox:checked');

                    if ($checkedChannels.length === 0) {
                        hasError = true;
                        toastr.error('Debe seleccionar al menos un canal para las notificaciones activadas');
                        return false;
                    }
                }
            });

            if (hasError) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
