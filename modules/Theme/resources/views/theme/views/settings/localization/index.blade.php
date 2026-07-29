@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de localización'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')
        <form method="POST" action="{{ route('manager.backups.localization.update') }}">
            @csrf
            @method('PUT')

            <!-- Localization Settings Card -->
            <div class="card">
                <!-- Header Section -->
                <div class="card-header p-4 border-bottom ">
                    <div>
                        <h5 class="mb-1 fw-bold">Configuración de localización</h5>
                        <p class="small mb-0">Configura el idioma predeterminado, zona horaria, formatos de fecha y
                            hora, y configuración de moneda para la aplicación.</p>
                    </div>
                </div>

                <!-- Form -->
                <div class="card-body">
                    <div class="row">
                        <!-- Language & Timezone -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Idioma y zona horaria</h6>

                                    <div class="mb-3">
                                        <label for="defaultLanguage" class="form-label fw-semibold">Idioma
                                            predeterminado <span class="text-danger">*</span></label>
                                        <select class="form-select" id="defaultLanguage" name="default_language"
                                                required>
                                            <option value="">Seleccionar idioma...</option>
                                            @foreach($languages as $lang)
                                                <option value="{{ $lang->code }}" {{ $defaultLanguage === $lang->code ? 'selected' : '' }}>
                                                    {{ $lang->name }} ({{ $lang->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Idioma predeterminado de la aplicación</small>
                                    </div>

                                    <div class="mb-0">
                                        <label for="timezone" class="form-label fw-semibold">Zona horaria <span
                                                    class="text-danger">*</span></label>
                                        <select class="form-select" id="timezone" name="timezone" required>
                                            @php
                                                $timezones = [
                                                    'America/New_York' => 'New York (UTC-5)',
                                                    'America/Chicago' => 'Chicago (UTC-6)',
                                                    'America/Denver' => 'Denver (UTC-7)',
                                                    'America/Los_Angeles' => 'Los Angeles (UTC-8)',
                                                    'America/Mexico_City' => 'Ciudad de México (UTC-6)',
                                                    'America/Bogota' => 'Bogotá (UTC-5)',
                                                    'America/Lima' => 'Lima (UTC-5)',
                                                    'America/Santiago' => 'Santiago (UTC-4)',
                                                    'America/Buenos_Aires' => 'Buenos Aires (UTC-3)',
                                                    'America/Sao_Paulo' => 'São Paulo (UTC-3)',
                                                    'Europe/London' => 'Londres (UTC+0)',
                                                    'Europe/Paris' => 'París (UTC+1)',
                                                    'Europe/Madrid' => 'Madrid (UTC+1)',
                                                    'Europe/Berlin' => 'Berlín (UTC+1)',
                                                    'Europe/Rome' => 'Roma (UTC+1)',
                                                    'Europe/Moscow' => 'Moscú (UTC+3)',
                                                    'Asia/Dubai' => 'Dubai (UTC+4)',
                                                    'Asia/Kolkata' => 'Kolkata (UTC+5:30)',
                                                    'Asia/Bangkok' => 'Bangkok (UTC+7)',
                                                    'Asia/Singapore' => 'Singapur (UTC+8)',
                                                    'Asia/Shanghai' => 'Shanghai (UTC+8)',
                                                    'Asia/Tokyo' => 'Tokio (UTC+9)',
                                                    'Australia/Sydney' => 'Sídney (UTC+10)',
                                                    'Pacific/Auckland' => 'Auckland (UTC+12)',
                                                ];
                                            @endphp
                                            @foreach($timezones as $tz => $label)
                                                <option value="{{ $tz }}" {{ $settings['timezone'] === $tz ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Zona horaria predeterminada para fechas y
                                            horas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Time Formats -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Formatos de fecha y hora</h6>

                                    <div class="mb-3">
                                        <label for="dateFormat" class="form-label fw-semibold">Formato de fecha <span
                                                    class="text-danger">*</span></label>
                                        <select class="form-select" id="dateFormat" name="date_format" required>
                                            <option value="d/m/Y" {{ $settings['date_format'] === 'd/m/Y' ? 'selected' : '' }}>
                                                DD/MM/AAAA (31/12/2024)
                                            </option>
                                            <option value="m/d/Y" {{ $settings['date_format'] === 'm/d/Y' ? 'selected' : '' }}>
                                                MM/DD/AAAA (12/31/2024)
                                            </option>
                                            <option value="Y-m-d" {{ $settings['date_format'] === 'Y-m-d' ? 'selected' : '' }}>
                                                AAAA-MM-DD (2024-12-31)
                                            </option>
                                            <option value="d-m-Y" {{ $settings['date_format'] === 'd-m-Y' ? 'selected' : '' }}>
                                                DD-MM-AAAA (31-12-2024)
                                            </option>
                                            <option value="d.m.Y" {{ $settings['date_format'] === 'd.m.Y' ? 'selected' : '' }}>
                                                DD.MM.AAAA (31.12.2024)
                                            </option>
                                        </select>
                                        <small class="text-muted">Formato para mostrar fechas</small>
                                    </div>

                                    <div class="mb-0">
                                        <label for="timeFormat" class="form-label fw-semibold">Formato de hora <span
                                                    class="text-danger">*</span></label>
                                        <select class="form-select" id="timeFormat" name="time_format" required>
                                            <option value="H:i" {{ $settings['time_format'] === 'H:i' ? 'selected' : '' }}>
                                                24 horas (14:30)
                                            </option>
                                            <option value="h:i A" {{ $settings['time_format'] === 'h:i A' ? 'selected' : '' }}>
                                                12 horas (02:30 PM)
                                            </option>
                                            <option value="H:i:s" {{ $settings['time_format'] === 'H:i:s' ? 'selected' : '' }}>
                                                24 horas con segundos (14:30:45)
                                            </option>
                                            <option value="h:i:s A" {{ $settings['time_format'] === 'h:i:s A' ? 'selected' : '' }}>
                                                12 horas con segundos (02:30:45 PM)
                                            </option>
                                        </select>
                                        <small class="text-muted">Formato para mostrar horas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Configuración de moneda</h6>

                                    <div class="mb-3">
                                        <label for="currency" class="form-label fw-semibold">Moneda <span
                                                    class="text-danger">*</span></label>
                                        <select class="form-select" id="currency" name="currency" required>
                                            @php
                                                $currencies = [
                                                    'USD' => 'Dólar estadounidense ($)',
                                                    'EUR' => 'Euro (€)',
                                                    'GBP' => 'Libra esterlina (£)',
                                                    'MXN' => 'Peso mexicano ($)',
                                                    'COP' => 'Peso colombiano ($)',
                                                    'ARS' => 'Peso argentino ($)',
                                                    'CLP' => 'Peso chileno ($)',
                                                    'PEN' => 'Sol peruano (S/)',
                                                    'BRL' => 'Real brasileño (R$)',
                                                    'JPY' => 'Yen japonés (¥)',
                                                    'CNY' => 'Yuan chino (¥)',
                                                    'INR' => 'Rupia india (₹)',
                                                ];
                                            @endphp
                                            @foreach($currencies as $code => $label)
                                                <option value="{{ $code }}" {{ $settings['currency'] === $code ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Moneda predeterminada para precios</small>
                                    </div>

                                    <div class="mb-0">
                                        <label for="currencyPosition" class="form-label fw-semibold">Posición de símbolo
                                            <span class="text-danger">*</span></label>
                                        <select class="form-select" id="currencyPosition" name="currency_position"
                                                required>
                                            <option value="before" {{ $settings['currency_position'] === 'before' ? 'selected' : '' }}>
                                                Antes del monto ($100.00)
                                            </option>
                                            <option value="after" {{ $settings['currency_position'] === 'after' ? 'selected' : '' }}>
                                                Después del monto (100.00€)
                                            </option>
                                        </select>
                                        <small class="text-muted">Donde colocar el símbolo de moneda</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Vista previa</h6>

                                    <div class="mb-3">
                                        <small class="text-muted d-block">Fecha</small>
                                        <p class="mb-0 fw-500"
                                           id="datePreview">{{ now()->format($settings['date_format']) }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted d-block">Hora</small>
                                        <p class="mb-0 fw-500"
                                           id="timePreview">{{ now()->format($settings['time_format']) }}</p>
                                    </div>

                                    <div class="mb-0">
                                        <small class="text-muted d-block">Moneda</small>
                                        <p class="mb-0 fw-500" id="currencyPreview">
                                            @if($settings['currency_position'] === 'before')
                                                {{ $settings['currency'] }} 1,234.56
                                            @else
                                                1,234.56 {{ $settings['currency'] }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="alert alert-info border-0 bg-info-subtle text-info mt-3 mb-0">
                                        <div class="d-flex align-items-start gap-2">
                                            <div>
                                                <strong>Nota:</strong> Los cambios se aplicarán en toda la aplicación
                                                después de guardar.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar</button>
                    <a href="{{ route('manager.backups') }}" class="btn btn-secondary w-100">Cancelar</a>
                </div>

            </div>

        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Update date preview
                const dateFormat = document.getElementById('dateFormat');
                const datePreview = document.getElementById('datePreview');

                if (dateFormat && datePreview) {
                    dateFormat.addEventListener('change', function () {
                        // This is a simple preview, in production you'd call backend for proper formatting
                        const formatMap = {
                            'd/m/Y': '{{ now()->format('d/m/Y') }}',
                            'm/d/Y': '{{ now()->format('m/d/Y') }}',
                            'Y-m-d': '{{ now()->format('Y-m-d') }}',
                            'd-m-Y': '{{ now()->format('d-m-Y') }}',
                            'd.m.Y': '{{ now()->format('d.m.Y') }}'
                        };
                        datePreview.textContent = formatMap[this.value] || this.value;
                    });
                }

                // Update time preview
                const timeFormat = document.getElementById('timeFormat');
                const timePreview = document.getElementById('timePreview');

                if (timeFormat && timePreview) {
                    timeFormat.addEventListener('change', function () {
                        const formatMap = {
                            'H:i': '{{ now()->format('H:i') }}',
                            'h:i A': '{{ now()->format('h:i A') }}',
                            'H:i:s': '{{ now()->format('H:i:s') }}',
                            'h:i:s A': '{{ now()->format('h:i:s A') }}'
                        };
                        timePreview.textContent = formatMap[this.value] || this.value;
                    });
                }

                // Update currency preview
                const currency = document.getElementById('currency');
                const currencyPosition = document.getElementById('currencyPosition');
                const currencyPreview = document.getElementById('currencyPreview');

                function updateCurrencyPreview() {
                    if (currency && currencyPosition && currencyPreview) {
                        const currencyCode = currency.value;
                        const position = currencyPosition.value;
                        const amount = '1,234.56';

                        if (position === 'before') {
                            currencyPreview.textContent = `${currencyCode} ${amount}`;
                        } else {
                            currencyPreview.textContent = `${amount} ${currencyCode}`;
                        }
                    }
                }

                if (currency) {
                    currency.addEventListener('change', updateCurrencyPreview);
                }

                if (currencyPosition) {
                    currencyPosition.addEventListener('change', updateCurrencyPreview);
                }
            });
        </script>
    @endpush

@endsection
