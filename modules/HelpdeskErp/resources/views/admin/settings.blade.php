@extends('layouts.theme')

@section('title', 'Ajustes de Gestión')

@section('page_header')
    @include('core::components.card', ['title' => 'Ajustes de Gestión', 'subtitle' => 'Cachés, resumen y avisos, vinculación y seguimiento del panel de Gestión (ERP) en el chat, sin despliegue'])
@endsection

@include('helpdeskerp::admin._assets')

@php
    use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminSettingsService as S;

    $disabled = ! $ready;
    $secs = function ($v) {
        $v = (int) $v;
        if ($v === 0) {
            return 'sin caché';
        }
        if ($v % 3600 === 0) {
            return ($v / 3600).' h';
        }
        if ($v % 60 === 0) {
            return ($v / 60).' min';
        }

        return $v.' s';
    };
    $isOn = fn (string $field, $current) => (string) old($field, $current ? '1' : '0') === '1';

    $trackingRows = old('tracking');
    if (! is_array($trackingRows)) {
        $trackingRows = [];
        foreach ((array) ($current['tracking.urls'] ?? []) as $carrier => $url) {
            $trackingRows[] = ['carrier' => $carrier, 'url' => $url];
        }
    }
    $trackingRows = array_values($trackingRows);
    $defaultTracking = (array) ($defaults['tracking.urls'] ?? []);
@endphp

@section('content')
    <div class="era-page">
        @include('helpdeskerp::admin._flash')

        @unless ($ready)
            <div class="era-note era-note--warn" role="alert">
                Falta crear la tabla de ajustes de Gestión: hay una migración pendiente de ejecutar. Hasta entonces se muestran los valores de configuración y no se puede guardar.
            </div>
        @endunless

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <form action="{{ route('manager.helpdesk.erp.admin.settings.update') }}" method="POST" id="eraSettingsForm" novalidate class="era-stack">
                    @csrf

                    {{-- ── Caché por estado ── --}}
                    <div class="card era-card">
                        <div class="card-header border-bottom p-3 era-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Caché por estado</h5>
                                <small class="text-muted">Segundos que se guarda cada respuesta del manager según su estado. Un escaneo de pedidos en curso nunca se guarda.</small>
                            </div>
                            <div class="era-head-side">
                                @if ($sectionOverridden['cache'])
                                    <span class="era-tag">Personalizado</span>
                                @endif
                                <button type="submit" form="eraReset-cache" class="era-btn era-btn--ghost" @disabled($disabled || ! $sectionOverridden['cache'])>Restablecer</button>
                            </div>
                        </div>
                        <div class="card-body era-grid">
                            @foreach (S::TTL_LABELS as $state => [$label, $hint])
                                <label class="era-field">
                                    <span class="era-lbl">{{ $label }} (s)</span>
                                    <input type="number" name="ttl[{{ $state }}]" min="0" max="86400" step="1" class="era-mono"
                                           value="{{ old('ttl.'.$state, $current['chat_ttl.'.$state]) }}" @disabled($disabled)>
                                    <span class="era-hint">{{ $hint }} Por defecto: {{ $secs($defaults['chat_ttl.'.$state]) }}.</span>
                                    @include('helpdeskerp::admin._err', ['field' => 'ttl.'.$state])
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Resumen y avisos ── --}}
                    <div class="card era-card">
                        <div class="card-header border-bottom p-3 era-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Resumen y avisos</h5>
                                <small class="text-muted">Lo que trae el resumen de Gestión al abrir un cliente y qué avisos se le muestran al agente.</small>
                            </div>
                            <div class="era-head-side">
                                @if ($sectionOverridden['overview'])
                                    <span class="era-tag">Personalizado</span>
                                @endif
                                <button type="submit" form="eraReset-overview" class="era-btn era-btn--ghost" @disabled($disabled || ! $sectionOverridden['overview'])>Restablecer</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="era-grid">
                                <label class="era-field">
                                    <span class="era-lbl">Pedidos en el resumen</span>
                                    <input type="number" name="overview[orders_limit]" min="1" max="100" step="1" class="era-mono"
                                           value="{{ old('overview.orders_limit', $current['overview.orders_limit']) }}" @disabled($disabled)>
                                    <span class="era-hint">Primera página de pedidos del resumen, de 1 a 100. Por defecto: {{ $defaults['overview.orders_limit'] }}.</span>
                                    @include('helpdeskerp::admin._err', ['field' => 'overview.orders_limit'])
                                </label>
                                <label class="era-field">
                                    <span class="era-lbl">Aviso de caducidad (días)</span>
                                    <input type="number" name="overview[expiry_days]" min="1" max="90" step="1" class="era-mono"
                                           value="{{ old('overview.expiry_days', $current['alerts.expiry_days']) }}" @disabled($disabled)>
                                    <span class="era-hint">Antelación con la que se avisa de vales y bonos que caducan. Por defecto: {{ $defaults['alerts.expiry_days'] }}.</span>
                                    @include('helpdeskerp::admin._err', ['field' => 'overview.expiry_days'])
                                </label>
                            </div>

                            <div class="era-sub">
                                <span class="era-subttl">Avisos que se muestran</span>
                                <span class="era-subtxt">Un aviso desactivado deja de salir en el resumen de todos los agentes.</span>
                            </div>
                            <div class="era-checks">
                                @foreach (S::ALERT_LABELS as $type => [$label, $hint])
                                    <div class="era-check">
                                        <input type="hidden" name="alerts[{{ $type }}]" value="0">
                                        <input type="checkbox" class="form-check-input" id="eraAlert-{{ $type }}" name="alerts[{{ $type }}]" value="1"
                                               @checked($isOn('alerts.'.$type, $current['alerts.enabled'][$type] ?? true)) @disabled($disabled)>
                                        <label for="eraAlert-{{ $type }}">
                                            <span class="era-check-ttl">{{ $label }}</span>
                                            <span class="era-hint">{{ $hint }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ── Vinculación automática ── --}}
                    <div class="card era-card">
                        <div class="card-header border-bottom p-3 era-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Vinculación automática</h5>
                                <small class="text-muted">Buscar en Gestión al contacto de cada conversación o ticket nuevo y vincularlo solo.</small>
                            </div>
                            <div class="era-head-side">
                                @if ($sectionOverridden['linking'])
                                    <span class="era-tag">Personalizado</span>
                                @endif
                                <button type="submit" form="eraReset-linking" class="era-btn era-btn--ghost" @disabled($disabled || ! $sectionOverridden['linking'])>Restablecer</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="era-check">
                                <input type="hidden" name="linking[auto]" value="0">
                                <input type="checkbox" class="form-check-input" id="eraLinkingAuto" name="linking[auto]" value="1"
                                       @checked($isOn('linking.auto', $current['linking.auto'])) @disabled($disabled)>
                                <label for="eraLinkingAuto">
                                    <span class="era-check-ttl">Vincular automáticamente</span>
                                    <span class="era-hint">Sin esto, solo se vincula cuando un agente pulsa «Buscar de nuevo». Por defecto: {{ $defaults['linking.auto'] ? 'activada' : 'desactivada' }}.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- ── Seguimiento de envíos ── --}}
                    <div class="card era-card">
                        <div class="card-header border-bottom p-3 era-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Seguimiento de envíos</h5>
                                <small class="text-muted">Plantilla de enlace de seguimiento por transportista. {tracking} se sustituye por el número de envío. La clave (minúsculas, sin espacios) se reconoce dentro del nombre o por el código del transportista en Gestión.</small>
                            </div>
                            <div class="era-head-side">
                                @if ($sectionOverridden['tracking'])
                                    <span class="era-tag">Personalizado</span>
                                @endif
                                <button type="submit" form="eraReset-tracking" class="era-btn era-btn--ghost" @disabled($disabled || ! $sectionOverridden['tracking'])>Restablecer</button>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('helpdeskerp::admin._err', ['field' => 'tracking'])
                            <div class="era-rows" data-era-list="tracking" data-next="{{ count($trackingRows) }}">
                                @foreach ($trackingRows as $i => $row)
                                    @include('helpdeskerp::admin._tracking-row', ['i' => $i, 'row' => $row, 'disabled' => $disabled])
                                @endforeach
                            </div>
                            <div class="era-empty {{ $trackingRows === [] ? '' : 'era-hidden' }}" data-era-empty="tracking">
                                Sin plantillas: el pedido muestra el número de seguimiento sin enlace.
                            </div>
                            <button type="button" class="era-btn era-btn--dashed" data-era-add="tracking" @disabled($disabled)>Añadir transportista</button>
                            @if ($defaultTracking !== [])
                                <p class="era-hint era-hint--block">De serie ({{ count($defaultTracking) }}): {{ implode(', ', array_keys($defaultTracking)) }}. Quitar una fila deja a ese transportista sin enlace.</p>
                            @endif
                        </div>
                    </div>

                    {{-- ── Métricas ── --}}
                    <div class="card era-card">
                        <div class="card-header border-bottom p-3 era-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Métricas de uso</h5>
                                <small class="text-muted">Registro ligero del uso del panel de Gestión, que alimenta «Métricas de Gestión».</small>
                            </div>
                            <div class="era-head-side">
                                @if ($sectionOverridden['metrics'])
                                    <span class="era-tag">Personalizado</span>
                                @endif
                                <button type="submit" form="eraReset-metrics" class="era-btn era-btn--ghost" @disabled($disabled || ! $sectionOverridden['metrics'])>Restablecer</button>
                            </div>
                        </div>
                        <div class="card-body era-grid">
                            <div class="era-check">
                                <input type="hidden" name="metrics[enabled]" value="0">
                                <input type="checkbox" class="form-check-input" id="eraMetricsEnabled" name="metrics[enabled]" value="1"
                                       @checked($isOn('metrics.enabled', $current['metrics.enabled'])) @disabled($disabled)>
                                <label for="eraMetricsEnabled">
                                    <span class="era-check-ttl">Registrar el uso</span>
                                    <span class="era-hint">Se escribe después de responder al agente: no añade espera.</span>
                                </label>
                            </div>
                            <label class="era-field">
                                <span class="era-lbl">Retención (días)</span>
                                <input type="number" name="metrics[retention_days]" min="7" max="730" step="1" class="era-mono"
                                       value="{{ old('metrics.retention_days', $current['metrics.retention_days']) }}" @disabled($disabled)>
                                <span class="era-hint">Lo más antiguo se borra cada noche. Por defecto: {{ $defaults['metrics.retention_days'] }}.</span>
                                @include('helpdeskerp::admin._err', ['field' => 'metrics.retention_days'])
                            </label>
                        </div>
                    </div>

                    <div class="card era-card">
                        <div class="card-body era-foot">
                            <button type="submit" class="era-btn era-btn--primary" @disabled($disabled)>Guardar ajustes</button>
                            @if ($updatedAt)
                                <span class="era-caption">Último cambio: {{ \Illuminate\Support\Carbon::parse($updatedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}{{ $updatedBy ? ' · '.$updatedBy : '' }}</span>
                            @endif
                        </div>
                    </div>
                </form>

                {{-- Formularios de «Restablecer» (fuera del principal: los botones
                     de cada sección los envían con el atributo form). --}}
                @foreach (S::SECTION_LABELS as $section => $label)
                    <form action="{{ route('manager.helpdesk.erp.admin.settings.reset') }}" method="POST" id="eraReset-{{ $section }}"
                          data-era-reset="{{ $label }}" class="era-hidden">
                        @csrf
                        <input type="hidden" name="section" value="{{ $section }}">
                    </form>
                @endforeach

                <template id="eraTpl-tracking">
                    @include('helpdeskerp::admin._tracking-row', ['i' => '__i__', 'row' => [], 'disabled' => false])
                </template>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card era-card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cómo se aplica</h6>
                    </div>
                    <div class="card-body era-help">
                        <p>Los cambios valen desde la siguiente petición de cada agente, sin despliegue. Solo se guarda lo que cambia el valor de configuración: lo que dejes igual sigue al .env del servidor.</p>
                        <p>«Restablecer» descarta lo guardado en esa sección y vuelve a los valores de configuración.</p>
                        <p class="mb-0">Cada cambio queda en el registro de actividad con el valor anterior y el nuevo.</p>
                    </div>
                </div>
                <div class="card era-card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cachés</h6>
                    </div>
                    <div class="card-body era-help">
                        <p>Gestión es de solo lectura y cada consulta va a Oracle de producción: una caché más larga descarga el ERP, una más corta enseña datos más frescos.</p>
                        <p class="mb-0">Con 0 segundos esa respuesta no se guarda. El agente siempre puede forzar la recarga desde el panel.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
