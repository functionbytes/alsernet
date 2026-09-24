{{-- Vale de compensación (pieza 04 · ps-voucher-create). Mini-modal
     .bv-modal de una columna; lo maneja prestashop-chat.js (PscVoucher).
     El límite por vale del agente sale de sus permisos (config vouchers) y el
     controlador lo vuelve a comprobar: aquí solo decide qué botón se ofrece. --}}
@php
    $psVoucherUser = auth()->user();
    $psVoucherLimit = $psVoucherUser?->can('helpdeskprestashop.vouchers.approve')
        ? (float) config('helpdeskprestashop.vouchers.approver_limit', 150)
        : ($psVoucherUser?->can('helpdeskprestashop.vouchers.create') ? (float) config('helpdeskprestashop.vouchers.agent_limit', 25) : 0);
@endphp
<div class="bv-modal" data-bv-modal-name="ps-voucher-create" data-limit="{{ $psVoucherLimit }}"
     data-url-template="{{ url('panel/helpdesk/customers/__ID__/ps/vouchers') }}">
    <div class="modal w-md">

        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-ticket"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">Chat · Cupones</div>
                <div class="modal-title">Crear vale de compensación</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="modal-body psc-voucher-body">
            <div class="psc-note psc-note--warn bv-hidden" id="psVchError"><span class="psc-note-txt"></span></div>

            <div class="psc-field">
                <span class="lbl">Importe</span>
                <div class="psc-money-input">
                    <input type="number" class="finput mono" id="psVchAmount" min="1" max="500" step="0.01" placeholder="15,00" inputmode="decimal">
                    <span class="sfx">€</span>
                </div>
            </div>

            <div class="psc-field">
                <span class="lbl">Validez</span>
                <div class="psc-seg" id="psVchDays">
                    @foreach(config('helpdeskprestashop.vouchers.validity_days', [30, 60, 90]) as $days)
                        <button type="button" data-days="{{ $days }}" class="{{ $loop->first ? 'is-on' : '' }}">{{ $days }} días</button>
                    @endforeach
                </div>
            </div>

            <div class="psc-field">
                <span class="lbl">Motivo interno</span>
                <div class="psc-voucher-reasons">
                    @foreach(config('helpdeskprestashop.vouchers.reasons', []) as $key => $label)
                        <label class="psc-radio-opt{{ $loop->first ? ' is-on' : '' }}">
                            <input type="radio" name="psVchReason" value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
                            <span class="psc-radio-body"><span class="t">{{ $label }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="psc-row">
                <span class="k">Tu límite por vale</span>
                <span class="v mono" id="psVchLimit">{{ number_format($psVoucherLimit, 2, ',', '.') }} €</span>
            </div>

            <div class="psc-preview">
                <div class="psc-preview-hd">Se creará</div>
                <div class="psc-preview-body" id="psVchPreview">Un código <b>GES-…</b> de un solo uso a nombre del cliente, que se insertará en el chat sin enviarlo.</div>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psVchCreate" disabled>Crear vale</button>
            <button type="button" class="psc-btn psc-btn--outline bv-hidden" id="psVchApproval">Pedir aprobación</button>
            <button type="button" class="psc-btn psc-btn--outline" data-bv-close>{{ __('helpdeskprestashop::chat.actions.cancel') }}</button>
        </div>
    </div>
</div>
