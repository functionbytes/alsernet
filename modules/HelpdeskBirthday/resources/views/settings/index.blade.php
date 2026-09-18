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
        Desde aquí se controla qué bono se regala, a quién se le envía y a qué ritmo.
    </p>
</div>

@include('core::components.alerts')

<form method="POST" action="{{ route('helpdeskbirthday.settings.update') }}">
    @csrf

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-1">El bono de cumpleaños</h6>
            <p class="text-muted small mb-3">
                No hay un cupón del día igual para todos: al preparar la campaña se le pide a
                gestión un bono <strong>para cada cumpleañero</strong>, y cada correo lleva el
                código de esa persona. Aquí solo se elige <strong>qué bono</strong> se emite.
            </p>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small" for="bd-bono-type">Tipo de bono en gestión (IDTBONO_PROMOCION)</label>
                    <input type="number" name="bono_type_id" id="bd-bono-type" min="0" step="1"
                           class="form-control @error('bono_type_id') is-invalid @enderror"
                           value="{{ old('bono_type_id', $settings['bono_type_id']) }}">
                    <div class="form-text">
                        El importe, la validez y la compra mínima los decide gestión a partir de este
                        tipo, no este panel: son los que devuelve al emitir cada bono y los que se ven
                        luego en la campaña. Sin un tipo indicado no se puede emitir nada y la campaña
                        del día queda en pausa.
                    </div>
                    @error('bono_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
