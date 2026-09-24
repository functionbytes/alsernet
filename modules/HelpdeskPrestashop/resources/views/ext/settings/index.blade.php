@extends('layouts.theme')

@section('title', 'Ajustes del chat de PrestaShop')

@section('page_header')
    @include('core::components.card', ['title' => 'Ajustes del chat', 'subtitle' => 'Límites de vales y reembolsos, instrucciones de retorno y respuestas rápidas, sin despliegue'])
@endsection

@include('helpdeskprestashop::ext.settings._assets')

@php
    $money = fn ($v) => number_format((float) $v, 2, ',', '.').' €';

    $ri = (array) ($current['refunds.return_instructions'] ?? []);
    $riDefault = (array) ($defaults['refunds.return_instructions'] ?? []);
    $addressLines = fn ($a) => implode("\n", array_filter(array_map('trim', explode('|', (string) $a)), 'strlen'));

    $reasonRows = old('vouchers.reasons');
    if (! is_array($reasonRows)) {
        $reasonRows = [];
        foreach ((array) ($current['vouchers.reasons'] ?? []) as $key => $label) {
            $reasonRows[] = ['key' => $key, 'label' => $label];
        }
    }
    $reasonRows = array_values($reasonRows);

    $replyRows = old('replies');
    if (! is_array($replyRows)) {
        $replyRows = (array) ($current['quick_replies'] ?? []);
    }
    $replyRows = array_values($replyRows);

    $disabled = ! $ready;
@endphp

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="psc-settings-stack">
                @include('helpdeskprestashop::ext.settings._flash')

                @unless ($ready)
                    <div class="psc-note psc-note--warn" role="alert">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span class="psc-note-txt">Falta crear la tabla de ajustes del chat: hay una migración pendiente de ejecutar. Hasta entonces se muestran los valores de configuración y no se puede guardar.</span>
                    </div>
                @endunless

                <form action="{{ route('manager.helpdesk.ps.ext.settings.update') }}" method="POST" id="psSettingsForm" novalidate>
                    @csrf

                    {{-- ── Vales de compensación ── --}}
                    <div class="card">
                        <div class="card-header border-bottom p-3 psc-settings-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Vales de compensación</h5>
                                <small class="text-muted">Importe máximo por vale según el permiso de quien lo crea, plazos de validez que se ofrecen y motivos del desplegable.</small>
                            </div>
                            @if ($sectionOverridden['vouchers'])
                                <span class="psc-tag psc-tag--progress">Personalizado</span>
                            @endif
                        </div>
                        <div class="card-body psc-settings-body">
                            <div class="psc-fieldrow psc-settings-fieldrow">
                                <label class="psc-field">
                                    <span class="lbl">Límite de agente (€)</span>
                                    <input type="number" name="vouchers[agent_limit]" min="1" max="{{ $voucherCap }}" step="0.01" class="mono"
                                           value="{{ old('vouchers.agent_limit', $current['vouchers.agent_limit']) }}" @disabled($disabled)>
                                    <span class="hint">Por defecto: {{ $money($defaults['vouchers.agent_limit']) }}. Permiso «crear vales».</span>
                                    @include('helpdeskprestashop::ext.settings._err', ['field' => 'vouchers.agent_limit'])
                                </label>
                                <label class="psc-field">
                                    <span class="lbl">Límite de supervisor (€)</span>
                                    <input type="number" name="vouchers[approver_limit]" min="1" max="{{ $voucherCap }}" step="0.01" class="mono"
                                           value="{{ old('vouchers.approver_limit', $current['vouchers.approver_limit']) }}" @disabled($disabled)>
                                    <span class="hint">Por defecto: {{ $money($defaults['vouchers.approver_limit']) }}. Permiso «aprobar vales». Tope del puente: {{ $voucherCap }} €.</span>
                                    @include('helpdeskprestashop::ext.settings._err', ['field' => 'vouchers.approver_limit'])
                                </label>
                            </div>

                            <label class="psc-field">
                                <span class="lbl">Validez permitida (días)</span>
                                <input type="text" name="vouchers[validity_days]" class="mono" maxlength="60" autocomplete="off"
                                       value="{{ old('vouchers.validity_days', implode(', ', (array) $current['vouchers.validity_days'])) }}" @disabled($disabled)>
                                <span class="hint">Días separados por comas, de 1 a 365. Por defecto: {{ implode(', ', (array) $defaults['vouchers.validity_days']) }}.</span>
                                @include('helpdeskprestashop::ext.settings._err', ['field' => 'vouchers.validity_days'])
                            </label>

                            <div class="psc-settings-sub">
                                <span class="psc-settings-subttl">Motivos</span>
                                <span class="psc-settings-subtxt">La clave queda guardada en cada vale y en el log de actividad: cámbiala solo en motivos nuevos.</span>
                            </div>
                            @include('helpdeskprestashop::ext.settings._err', ['field' => 'vouchers.reasons'])
                            <div class="psc-settings-rows" data-settings-list="reasons" data-next="{{ count($reasonRows) }}">
                                @foreach ($reasonRows as $i => $row)
                                    @include('helpdeskprestashop::ext.settings._reason-row', ['i' => $i, 'row' => $row])
                                @endforeach
                            </div>
                            <button type="button" class="psc-btn psc-btn--dashed psc-settings-add" data-settings-add="reasons" @disabled($disabled)>Añadir motivo</button>
                        </div>
                    </div>

                    {{-- ── Reembolsos ── --}}
                    <div class="card">
                        <div class="card-header border-bottom p-3 psc-settings-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Reembolsos e instrucciones de retorno</h5>
                                <small class="text-muted">Importe máximo por reembolso parcial (con IVA) y el texto que se manda al cliente para devolver un paquete.</small>
                            </div>
                            @if ($sectionOverridden['refunds'])
                                <span class="psc-tag psc-tag--progress">Personalizado</span>
                            @endif
                        </div>
                        <div class="card-body psc-settings-body">
                            <div class="psc-fieldrow psc-settings-fieldrow">
                                <label class="psc-field">
                                    <span class="lbl">Límite de agente (€)</span>
                                    <input type="number" name="refunds[agent_limit]" min="1" max="10000" step="0.01" class="mono"
                                           value="{{ old('refunds.agent_limit', $current['refunds.agent_limit']) }}" @disabled($disabled)>
                                    <span class="hint">Por defecto: {{ $money($defaults['refunds.agent_limit']) }}. Permiso «emitir reembolsos».</span>
                                    @include('helpdeskprestashop::ext.settings._err', ['field' => 'refunds.agent_limit'])
                                </label>
                                <label class="psc-field">
                                    <span class="lbl">Límite de supervisor (€)</span>
                                    <input type="number" name="refunds[approver_limit]" min="1" max="10000" step="0.01" class="mono"
                                           value="{{ old('refunds.approver_limit', $current['refunds.approver_limit']) }}" @disabled($disabled)>
                                    <span class="hint">Por defecto: {{ $money($defaults['refunds.approver_limit']) }}. Permiso «aprobar reembolsos».</span>
                                    @include('helpdeskprestashop::ext.settings._err', ['field' => 'refunds.approver_limit'])
                                </label>
                            </div>

                            <div class="psc-fieldrow psc-settings-fieldrow">
                                <label class="psc-field psc-field--grow">
                                    <span class="lbl">Transportista</span>
                                    <input type="text" name="refunds[carrier]" maxlength="150"
                                           value="{{ old('refunds.carrier', $ri['carrier'] ?? '') }}" @disabled($disabled)>
                                    <span class="hint">Por defecto: {{ ($riDefault['carrier'] ?? '') !== '' ? $riDefault['carrier'] : 'vacío' }}.</span>
                                    @include('helpdeskprestashop::ext.settings._err', ['field' => 'refunds.carrier'])
                                </label>
                                <label class="psc-field psc-settings-narrow">
                                    <span class="lbl">Plazo (días)</span>
                                    <input type="number" name="refunds[validity_days]" min="1" max="365" step="1" class="mono"
                                           value="{{ old('refunds.validity_days', $ri['validity_days'] ?? 14) }}" @disabled($disabled)>
                                    <span class="hint">Por defecto: {{ (int) ($riDefault['validity_days'] ?? 14) }}.</span>
                                    @include('helpdeskprestashop::ext.settings._err', ['field' => 'refunds.validity_days'])
                                </label>
                            </div>

                            <label class="psc-field">
                                <span class="lbl">Dirección de devoluciones</span>
                                <textarea name="refunds[address]" rows="4" maxlength="1000" @disabled($disabled)>{{ old('refunds.address', $addressLines($ri['address'] ?? '')) }}</textarea>
                                <span class="hint">Una línea por renglón, hasta 8. Sin dirección, el bloque de retorno del chat pide configurarla en vez de mandar una inventada.</span>
                                @include('helpdeskprestashop::ext.settings._err', ['field' => 'refunds.address'])
                            </label>

                            <label class="psc-field">
                                <span class="lbl">Pasos para el cliente</span>
                                <textarea name="refunds[steps]" rows="5" maxlength="3000" @disabled($disabled)>{{ old('refunds.steps', implode("\n", (array) ($ri['steps'] ?? []))) }}</textarea>
                                <span class="hint">Un paso por línea, hasta 10. Se sustituyen :rma (número de devolución), :order (pedido) y :days (plazo).</span>
                                @include('helpdeskprestashop::ext.settings._err', ['field' => 'refunds.steps'])
                            </label>
                        </div>
                    </div>

                    {{-- ── Respuestas rápidas ── --}}
                    <div class="card">
                        <div class="card-header border-bottom p-3 psc-settings-head">
                            <div>
                                <h5 class="mb-0 fw-bold">Respuestas rápidas con datos reales</h5>
                                <small class="text-muted">Plantillas del bloque «Respuestas con datos reales» del panel de PrestaShop en el inbox. Si al cliente abierto le falta el dato de alguna variable, esa respuesta no se ofrece.</small>
                            </div>
                            @if ($sectionOverridden['replies'])
                                <span class="psc-tag psc-tag--progress">Personalizado</span>
                            @endif
                        </div>
                        <div class="card-body psc-settings-body">
                            @include('helpdeskprestashop::ext.settings._err', ['field' => 'replies'])
                            <div class="psc-settings-rows" data-settings-list="replies" data-next="{{ count($replyRows) }}">
                                @foreach ($replyRows as $i => $row)
                                    @include('helpdeskprestashop::ext.settings._reply-row', ['i' => $i, 'row' => $row])
                                @endforeach
                            </div>
                            <div class="psc-state psc-state--compact psc-settings-empty {{ $replyRows === [] ? '' : 'psc-settings-hidden' }}" data-settings-empty="replies">
                                <div class="s">Sin respuestas rápidas: el bloque no se mostrará en el inbox.</div>
                            </div>
                            <button type="button" class="psc-btn psc-btn--dashed psc-settings-add" data-settings-add="replies" @disabled($disabled)>Añadir respuesta</button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-footer psc-settings-foot">
                            <button type="submit" class="psc-btn psc-btn--primary" @disabled($disabled)>Guardar ajustes</button>
                            @if ($updatedAt)
                                <span class="psc-settings-caption">Último cambio: {{ \Illuminate\Support\Carbon::parse($updatedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}{{ $updatedBy ? ' · '.$updatedBy : '' }}</span>
                            @endif
                        </div>
                    </div>
                </form>

                {{-- Plantillas del repetidor (fuera del form: no se envían). --}}
                <template id="psSettingsTpl-reasons">
                    @include('helpdeskprestashop::ext.settings._reason-row', ['i' => '__i__', 'row' => []])
                </template>
                <template id="psSettingsTpl-replies">
                    @include('helpdeskprestashop::ext.settings._reply-row', ['i' => '__i__', 'row' => []])
                </template>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Variables de las respuestas</h6>
                </div>
                <div class="card-body psc-settings-help">
                    <p>Escríbelas entre llaves en el título, el subtítulo o el texto. Pulsa una para añadirla al texto de la última respuesta en la que hayas escrito.</p>
                    <div class="psc-settings-vars">
                        @foreach ($variables as $name => $label)
                            <button type="button" class="psc-settings-var" data-settings-var="{{ $name }}">
                                <span class="k">{{ '{'.$name.'}' }}</span>
                                <span class="v">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Cómo se aplica</h6>
                </div>
                <div class="card-body psc-settings-help">
                    <p>Los cambios valen desde la siguiente acción de cada agente, sin despliegue. Solo se guarda lo que cambia el valor de configuración: lo que dejes igual sigue al .env del servidor.</p>
                    <p class="mb-0">El puente de PrestaShop vuelve a comprobar los importes: aunque subas un límite aquí, no crea vales de más de {{ $voucherCap }} €.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Volver a la configuración</h6>
                </div>
                <div class="card-body psc-settings-help">
                    <p>Descarta lo guardado en una sección y vuelve a los valores de config/.env.</p>
                    <div class="psc-settings-resets">
                        @foreach (\Modules\HelpdeskPrestashop\Services\Ext\SettingsService::SECTION_LABELS as $section => $label)
                            <form action="{{ route('manager.helpdesk.ps.ext.settings.reset') }}" method="POST" data-settings-reset>
                                @csrf
                                <input type="hidden" name="section" value="{{ $section }}">
                                <button type="submit" class="psc-btn psc-btn--outline" @disabled($disabled || ! $sectionOverridden[$section])>Restablecer {{ \Illuminate\Support\Str::lower($label) }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
