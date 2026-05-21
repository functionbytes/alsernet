@extends('layouts.theme')

@section('title', isset($campaign) ? 'Editar campaña' : 'Nueva campaña')

@section('page_header')
    @include('core::components.card', ['title' => isset($campaign) ? 'Editar campaña' : 'Nueva campaña'])
@endsection

@section('content')

    @include('core::components.alerts')

    <form action="{{ isset($campaign) ? route('remarketing.campaigns.update', $campaign) : route('remarketing.campaigns.store') }}"
          method="POST" id="campaignForm">
        @csrf
        @if(isset($campaign)) @method('PUT') @endif

        <div class="card">
            <div class="card-header border-bottom p-0">
                <ul class="nav nav-tabs border-0 px-3" id="campaignTabs">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details">
                            Detalles
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-audience">
                            Audiencia
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-template">
                            Plantilla
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-schedule">
                            Programación
                        </button>
                    </li>
                </ul>
            </div>
            <div class="tab-content">

                {{-- Tab: details --}}
                <div class="tab-pane fade show active p-4" id="tab-details">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label">Nombre de la campaña <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $campaign->name ?? '') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Asunto del email <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject', $campaign->subject ?? '') }}" required>
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Preheader</label>
                            <input type="text" name="preheader" class="form-control"
                                   value="{{ old('preheader', $campaign->preheader ?? '') }}"
                                   placeholder="Texto breve visible tras el asunto en la bandeja de entrada">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nombre del remitente <span class="text-danger">*</span></label>
                            <input type="text" name="from_name" class="form-control @error('from_name') is-invalid @enderror"
                                   value="{{ old('from_name', $campaign->from_name ?? config('mail.from.name')) }}" required>
                            @error('from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email del remitente <span class="text-danger">*</span></label>
                            <input type="email" name="from_email" class="form-control @error('from_email') is-invalid @enderror"
                                   value="{{ old('from_email', $campaign->from_email ?? config('mail.from.address')) }}" required>
                            @error('from_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

                {{-- Tab: audience --}}
                <div class="tab-pane fade p-4" id="tab-audience">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Tienda <span class="text-danger">*</span></label>
                            <select name="store_id" class="form-select @error('store_id') is-invalid @enderror">
                                <option value="">Selecciona una tienda</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('store_id', $campaign->store_id ?? '') == $store->id ? 'selected' : '' }}>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('store_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Segmento</label>
                            <select name="segment_id" class="form-select">
                                <option value="">Toda la lista (sin filtro)</option>
                                @foreach($segments as $segment)
                                    <option value="{{ $segment->id }}" {{ old('segment_id', $campaign->segment_id ?? '') == $segment->id ? 'selected' : '' }}>
                                        {{ $segment->name }} ({{ number_format($segment->member_count) }} miembros)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <hr class="my-3">
                            <h6 class="mb-2">Holdout group (control)</h6>
                            <p class="text-muted small mb-3">
                                Un porcentaje aleatorio (pero determinístico) de la audiencia queda excluido del envío para medir el revenue incremental real de la campaña.
                                Recomendado 10% para campañas con audiencia &gt; 10.000.
                            </p>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Holdout %</label>
                            <div class="input-group">
                                <input type="number" name="settings[holdout_pct]"
                                       class="form-control @error('settings.holdout_pct') is-invalid @enderror"
                                       value="{{ old('settings.holdout_pct', data_get($campaign->settings ?? [], 'holdout_pct', 0)) }}"
                                       min="0" max="50" step="1">
                                <span class="input-group-text">%</span>
                            </div>
                            @error('settings.holdout_pct') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <div class="form-text">0 = sin holdout. Máximo 50%.</div>
                        </div>

                    </div>
                </div>

                {{-- Tab: template --}}
                <div class="tab-pane fade p-4" id="tab-template">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Plantilla de email</label>
                            <select name="template_id" id="templateSelect" class="form-select">
                                <option value="">Sin plantilla (HTML personalizado)</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}" {{ old('template_id', $campaign->template_id ?? '') == $tpl->id ? 'selected' : '' }}>
                                        {{ $tpl->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Idioma de envío</label>
                            <select name="lang_id" id="langSelect" class="form-select">
                                <option value="">Predeterminado del sistema</option>
                                @foreach($languages as $lang)
                                    <option value="{{ $lang->id }}" {{ old('lang_id', $campaign->lang_id ?? '') == $lang->id ? 'selected' : '' }}>
                                        {{ $lang->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Previsualización</label>
                                <button type="button" id="btn-refresh-preview" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-sync me-1"></i> Actualizar
                                </button>
                            </div>
                            <div class="border rounded" style="height:400px;overflow:hidden">
                                <iframe id="template-preview" style="width:100%;height:100%;border:0"
                                        srcdoc="<p style='font-family:sans-serif;color:#888;text-align:center;padding:40px'>Selecciona una plantilla para previsualizar</p>">
                                </iframe>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Tab: schedule --}}
                <div class="tab-pane fade p-4" id="tab-schedule">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label">Modo de envío</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="send_mode" id="send-now" value="now"
                                           {{ old('send_mode', 'schedule') === 'now' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="send-now">Enviar ahora</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="send_mode" id="send-schedule" value="schedule"
                                           {{ old('send_mode', 'schedule') === 'schedule' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="send-schedule">Programar envío</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="scheduled-at-field">
                            <label class="form-label">Fecha y hora de envío</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control"
                                   value="{{ old('scheduled_at', isset($campaign) && $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                            <div class="form-text">La hora es en zona horaria del servidor ({{ config('app.timezone') }}).</div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    {{ isset($campaign) ? 'Guardar cambios' : 'Crear campaña' }}
                </button>
                <a href="{{ route('remarketing.campaigns.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Show/hide scheduled_at based on send mode
    function toggleScheduleField() {
        if ($('[name="send_mode"]:checked').val() === 'now') {
            $('#scheduled-at-field').addClass('d-none');
        } else {
            $('#scheduled-at-field').removeClass('d-none');
        }
    }
    $('[name="send_mode"]').on('change', toggleScheduleField);
    toggleScheduleField();

    // Preview template on change
    $('#templateSelect, #langSelect').on('change', function () {
        loadPreview($('#templateSelect').val());
    });

    $('#btn-refresh-preview').on('click', function () {
        loadPreview($('#templateSelect').val());
    });

    function loadPreview(templateId) {
        var langId = $('#langSelect').val();
        @if(isset($campaign))
            // Existing campaign: render via campaign preview endpoint (includes layout + campaign variables)
            $.get('{{ route('remarketing.campaigns.preview', $campaign) }}', { lang_id: langId }, function (html) {
                $('#template-preview').attr('srcdoc', html);
            }).fail(function () {
                $('#template-preview').attr('srcdoc', '<p style="font-family:sans-serif;color:#888;text-align:center;padding:40px">No se pudo cargar la previsualización</p>');
            });
        @else
            // New campaign: render template preview only
            if (!templateId) return;
            $.get('/panel/remarketing/templates/' + templateId + '/preview', { lang_id: langId }, function (html) {
                $('#template-preview').attr('srcdoc', html);
            }).fail(function () {
                $('#template-preview').attr('srcdoc', '<p style="font-family:sans-serif;color:#888;text-align:center;padding:40px">No se pudo cargar la previsualización</p>');
            });
        @endif
    }

    // Load preview on init
    loadPreview($('#templateSelect').val());

});
</script>
@endpush
