@extends('layouts.theme')
@section('title', 'Ajustes de cumpleaños')
@section('page_header')
    @include('core::components.card', ['title' => 'Ajustes de cumpleaños'])
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-sliders text-primary me-2"></i>Ajustes de cumpleaños
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Desde aquí se controla el cupón del día, a quién se le envía y a qué ritmo.
    </p>
</div>

@include('core::components.alerts')

<form method="POST" action="{{ route('helpdeskbirthday.settings.update') }}">
    @csrf

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Cupón del día</h6>
            <p class="text-muted small mb-3">
                Hay dos formas de repartir el bono: que gestión genere <strong>uno por cliente</strong>
                —indicando el tipo de bono— o fijar un <strong>código único</strong> para todos. Con el
                tipo de bono configurado, cada persona recibe el suyo.
            </p>

            <div class="row g-3">
                {{-- El tipo de bono va primero y solo en su fila: es lo que decide
                     si cada cliente recibe un bono propio o todos comparten uno. --}}
                <div class="col-12">
                    <label class="form-label small" for="bd-bono-type">Tipo de bono en gestión (IDTBONO_PROMOCION)</label>
                    <input type="number" name="bono_type_id" id="bd-bono-type" min="0" step="1"
                           class="form-control @error('bono_type_id') is-invalid @enderror"
                           value="{{ old('bono_type_id', $settings['bono_type_id']) }}">
                    <div class="form-text">
                        Con un tipo indicado, al preparar la campaña se pide a gestión un bono para cada
                        cumpleañero y cada correo lleva su propio código. El importe, la validez y la
                        compra mínima salen del tipo, no de este panel. Déjalo en 0 para repartir en su
                        lugar el código único de abajo.
                    </div>
                    @error('bono_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label small" for="bd-coupon-code">Código del bono</label>
                    <input type="text" name="coupon_code" id="bd-coupon-code" class="form-control @error('coupon_code') is-invalid @enderror" value="{{ old('coupon_code', $settings['coupon_code']) }}" maxlength="190">
                    @error('coupon_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="bd-coupon-cv">Código de verificación</label>
                    <input type="text" name="coupon_verification_code" id="bd-coupon-cv" class="form-control @error('coupon_verification_code') is-invalid @enderror" value="{{ old('coupon_verification_code', $settings['coupon_verification_code']) }}" maxlength="190">
                    <div class="form-text">El cliente recibe el código como «bono-verificación», igual que en la tienda.</div>
                    @error('coupon_verification_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label small" for="bd-valid-from">Válido desde (si gestión no responde)</label>
                    <input type="date" name="coupon_valid_from" id="bd-valid-from" class="form-control @error('coupon_valid_from') is-invalid @enderror" value="{{ old('coupon_valid_from', $settings['coupon_valid_from']) }}">
                    @error('coupon_valid_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="bd-valid-to">Válido hasta (si gestión no responde)</label>
                    <input type="date" name="coupon_valid_to" id="bd-valid-to" class="form-control @error('coupon_valid_to') is-invalid @enderror" value="{{ old('coupon_valid_to', $settings['coupon_valid_to']) }}">
                    @error('coupon_valid_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label small" for="bd-amount">Importe</label>
                    <input type="number" step="0.01" min="0" name="coupon_amount" id="bd-amount" class="form-control @error('coupon_amount') is-invalid @enderror" value="{{ old('coupon_amount', $settings['coupon_amount']) }}">
                    @error('coupon_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small" for="bd-min">Compra mínima</label>
                    <input type="number" step="0.01" min="0" name="coupon_min_purchase" id="bd-min" class="form-control @error('coupon_min_purchase') is-invalid @enderror" value="{{ old('coupon_min_purchase', $settings['coupon_min_purchase']) }}">
                    @error('coupon_min_purchase')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small" for="bd-validate">Validar con gestión</label>
                    <select name="validate_against_erp" id="bd-validate" class="form-select">
                        <option value="1" @selected(old('validate_against_erp', $settings['validate_against_erp']))>Sí</option>
                        <option value="0" @selected(! old('validate_against_erp', $settings['validate_against_erp']))>No</option>
                    </select>
                    <div class="form-text">Al preparar la campaña se piden a gestión las fechas y el importe reales.</div>
                </div>

                <div class="col-12">
                    {{-- Consulta el bono AHORA, sin esperar a la campaña del día:
                         es la forma de comprobar que el código existe y qué vale
                         antes de dejarlo puesto. --}}
                    <button type="button" class="btn btn-outline-secondary" id="bd-check-coupon"
                            data-url="{{ route('helpdeskbirthday.settings.validate-coupon') }}">
                        Comprobar el bono en gestión
                    </button>
                    <span class="ms-2 small" id="bd-check-result"></span>

                    <div class="mt-3 d-none" id="bd-check-detail">
                        <dl class="row small mb-0">
                            <dt class="col-4 col-md-2 fw-normal text-muted">Origen</dt>
                            <dd class="col-8 col-md-4" id="bd-check-source">—</dd>

                            <dt class="col-4 col-md-2 fw-normal text-muted">Código</dt>
                            <dd class="col-8 col-md-4" id="bd-check-code">—</dd>

                            <dt class="col-4 col-md-2 fw-normal text-muted">Válido desde</dt>
                            <dd class="col-8 col-md-4" id="bd-check-from">—</dd>

                            <dt class="col-4 col-md-2 fw-normal text-muted">Válido hasta</dt>
                            <dd class="col-8 col-md-4" id="bd-check-to">—</dd>

                            <dt class="col-4 col-md-2 fw-normal text-muted">Importe</dt>
                            <dd class="col-8 col-md-4" id="bd-check-amount">—</dd>

                            <dt class="col-4 col-md-2 fw-normal text-muted">Compra mínima</dt>
                            <dd class="col-8 col-md-4" id="bd-check-min">—</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Ritmo de envío</h6>
            <p class="text-muted small mb-3">
                Los correos se reparten dentro de la ventana. Si no caben al ritmo permitido,
                manda el tope por hora y la campaña acaba más tarde.
                @if($sample)
                    Con 100 destinatarios saldría uno cada {{ $sample->intervalSeconds }} s
                    ({{ $sample->perHour() }}/h), terminando a las {{ $sample->estimatedEnd()->format('H:i') }}.
                @endif
            </p>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small" for="bd-window-start">Empieza a las</label>
                    <input type="time" name="window_start" id="bd-window-start" class="form-control @error('window_start') is-invalid @enderror" value="{{ old('window_start', $settings['window_start']) }}" required>
                    @error('window_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="bd-window-end">Termina a las</label>
                    <input type="time" name="window_end" id="bd-window-end" class="form-control @error('window_end') is-invalid @enderror" value="{{ old('window_end', $settings['window_end']) }}" required>
                    @error('window_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="bd-throttle">Máximo de correos por hora</label>
                    <input type="number" min="1" name="throttle_per_hour" id="bd-throttle" class="form-control @error('throttle_per_hour') is-invalid @enderror" value="{{ old('throttle_per_hour', $settings['throttle_per_hour']) }}" required>
                    @error('throttle_per_hour')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="bd-max">Máximo de destinatarios por día</label>
                    <input type="number" min="1" name="max_recipients" id="bd-max" class="form-control @error('max_recipients') is-invalid @enderror" value="{{ old('max_recipients', $settings['max_recipients']) }}" required>
                    <div class="form-text">Si el ERP devuelve más, la campaña se aborta sin enviar nada.</div>
                    @error('max_recipients')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-1">A quién se le envía</h6>
            <p class="text-muted small mb-3">
                Los dados de baja en el ERP quedan siempre fuera. Estas son las exclusiones que sí puedes elegir.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small" for="bd-commercial">Excluir a quien rechazó información comercial</label>
                    <select name="commercial_optin" id="bd-commercial" class="form-select">
                        <option value="1" @selected(old('commercial_optin', $settings['commercial_optin']))>Sí, excluirlos</option>
                        <option value="0" @selected(! old('commercial_optin', $settings['commercial_optin']))>No</option>
                    </select>
                    <div class="form-text">Se corresponde con la marca LOPD «no información comercial».</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="bd-lopd">Exigir LOPD aceptada</label>
                    <select name="lopd_accepted" id="bd-lopd" class="form-select">
                        <option value="1" @selected(old('lopd_accepted', $settings['lopd_accepted']))>Sí, solo con LOPD aceptada</option>
                        <option value="0" @selected(! old('lopd_accepted', $settings['lopd_accepted']))>No</option>
                    </select>
                    <div class="form-text">Más restrictivo: puede reducir bastante la lista.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="bd-has-email">Solo clientes con email</label>
                    <select name="has_email" id="bd-has-email" class="form-select">
                        <option value="1" @selected(old('has_email', $settings['has_email']))>Sí</option>
                        <option value="0" @selected(! old('has_email', $settings['has_email']))>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="bd-suppressions">Respetar la lista de supresión</label>
                    <select name="check_suppressions" id="bd-suppressions" class="form-select">
                        <option value="1" @selected(old('check_suppressions', $settings['check_suppressions']))>Sí</option>
                        <option value="0" @selected(! old('check_suppressions', $settings['check_suppressions']))>No</option>
                    </select>
                    <div class="form-text">Rebotes duros, quejas y bajas. El envío los bloquea igualmente; esto los descarta antes.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Otros</h6>
            <p class="text-muted small mb-3">
                De dónde salen los cumpleañeros, plantilla del correo y qué hacer con
                quien nació un 29 de febrero.
            </p>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small" for="bd-source">Origen de los cumpleañeros</label>
                    <select name="audience_source" id="bd-source" class="form-select @error('audience_source') is-invalid @enderror">
                        <option value="api" @selected(old('audience_source', $settings['audience_source']) === 'api')>API de clientes de este panel (recomendado)</option>
                        <option value="gestion" @selected(old('audience_source', $settings['audience_source']) === 'gestion')>API de Gestión</option>
                    </select>
                    <div class="form-text">
                        La de este panel filtra por fecha de nacimiento en la propia consulta y
                        tarda unos segundos. La de Gestión da el mismo resultado, pero tarda unos
                        16 segundos y descarga casi un mega de XML: úsala solo si la primera falla.
                    </div>
                    @error('audience_source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="bd-template">Plantilla del correo</label>
                    <input type="text" name="template_key" id="bd-template" class="form-control @error('template_key') is-invalid @enderror" value="{{ old('template_key', $settings['template_key']) }}" required>
                    <div class="form-text">Clave de la plantilla en el módulo Mailer.</div>
                    @error('template_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="bd-leap">Nacidos el 29 de febrero</label>
                    <select name="leap_day_policy" id="bd-leap" class="form-select">
                        <option value="feb28" @selected(old('leap_day_policy', $settings['leap_day_policy']) === 'feb28')>Felicitar el 28 de febrero</option>
                        <option value="mar01" @selected(old('leap_day_policy', $settings['leap_day_policy']) === 'mar01')>Felicitar el 1 de marzo</option>
                    </select>
                    <div class="form-text">Solo aplica en años no bisiestos.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Guardar ajustes</button>
    </div>
</form>

@can('helpdeskbirthday.manage')
    {{-- Fuera del formulario de ajustes: son dos acciones distintas y anidar
         formularios no es válido en HTML. --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Enviar una prueba</h6>
            <p class="text-muted small mb-3">
                Manda la felicitación con el cupón configurado a direcciones internas,
                para verla en un cliente de correo real antes del envío del día.
                No toca la campaña ni sus destinatarios. Máximo 5 direcciones.
            </p>

            <form method="POST" action="{{ route('helpdeskbirthday.settings.test-send') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-9">
                    <label class="form-label small" for="bd-test-emails">Direcciones de prueba</label>
                    <input type="text" name="test_emails" id="bd-test-emails"
                           class="form-control @error('test_emails') is-invalid @enderror"
                           placeholder="yo@empresa.com, companero@empresa.com" maxlength="500" required>
                    <div class="form-text">Separadas por comas o espacios.</div>
                    @error('test_emails')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary w-100">Enviar la prueba</button>
                </div>
            </form>
        </div>
    </div>
@endcan


@endsection

@push('scripts')
<script>
    document.getElementById('bd-check-coupon')?.addEventListener('click', async function () {
        const button = this;
        const result = document.getElementById('bd-check-result');
        const detail = document.getElementById('bd-check-detail');

        button.disabled = true;
        result.textContent = 'Consultando a gestión…';
        result.className = 'ms-2 small text-muted';
        detail.classList.add('d-none');

        try {
            // El endpoint lee el cupón GUARDADO, no lo que hay escrito en el
            // formulario: hay que guardar antes para comprobar un código nuevo.
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                result.textContent = payload.message ?? 'No se pudo comprobar el bono.';
                result.className = 'ms-2 small text-muted fw-semibold';
                return;
            }

            const data = payload.data;
            const validado = payload.source === 'erp';

            result.textContent = validado
                ? 'Gestión reconoce el bono.'
                : 'Gestión no respondió: se usarán los valores de este formulario.';
            result.className = 'ms-2 small fw-semibold';

            document.getElementById('bd-check-source').textContent = validado ? 'Validado con gestión' : 'Configurado a mano';
            document.getElementById('bd-check-code').textContent = data.coupon_code ?? '—';
            document.getElementById('bd-check-from').textContent = data.coupon_valid_from ?? '—';
            document.getElementById('bd-check-to').textContent = data.coupon_valid_to ?? '—';
            document.getElementById('bd-check-amount').textContent = data.coupon_amount != null ? data.coupon_amount + ' €' : '—';
            document.getElementById('bd-check-min').textContent = data.coupon_min_purchase != null ? data.coupon_min_purchase + ' €' : '—';
            detail.classList.remove('d-none');
        } catch (error) {
            result.textContent = 'No se pudo contactar con el servidor.';
            result.className = 'ms-2 small text-muted fw-semibold';
        } finally {
            button.disabled = false;
        }
    });
</script>
@endpush
