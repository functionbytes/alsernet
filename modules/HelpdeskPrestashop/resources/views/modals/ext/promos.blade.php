{{-- Extensión "promos": Editar cupón (pieza 33 · ps-voucher-edit) y
     Promociones de la tienda (pieza 34 · ps-vouchers-shop). Mini-modales
     .bv-modal de una columna; los maneja js/ext/promos.js. Los permisos solo
     deciden qué se ofrece: los controladores los vuelven a comprobar. --}}
@php
    $psPromosUser = auth()->user();
    $psPromosCanEdit = (bool) $psPromosUser?->can('helpdeskprestashop.vouchers.edit');
    $psPromosCanApply = (bool) $psPromosUser?->can('helpdeskprestashop.carts.manage');
    $psPromosCss = 'modules/helpdeskprestashop/css/ext/promos.css';
    $psPromosJs = 'modules/helpdeskprestashop/js/ext/promos.js';
@endphp
<link rel="stylesheet" href="{{ asset($psPromosCss) }}?v={{ @filemtime(public_path($psPromosCss)) }}">

<div class="bv-modal" data-bv-modal-name="ps-voucher-edit"
     data-can-edit="{{ $psPromosCanEdit ? 1 : 0 }}">
    <div class="modal w-md">

        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-ticket"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">Chat · Cupones</div>
                <div class="modal-title">Editar cupón</div>
            </div>
            <button class="modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="modal-body psc-promos-body">
            <div class="psc-note psc-note--warn bv-hidden" id="psPromosEditError"><span class="psc-note-txt"></span></div>

            <div class="psc-promos-loading" id="psPromosEditLoading">
                <div class="psc-skel"></div>
                <div class="psc-loading">Cargando cupón…</div>
            </div>

            <div class="psc-promos-form bv-hidden" id="psPromosEditForm">
                <div class="psc-promos-code">
                    <span class="c" id="psPromosEditCode">—</span>
                    <span class="v" id="psPromosEditValue">—</span>
                </div>

                <div class="psc-note psc-note--info bv-hidden" id="psPromosEditUsed"><span class="psc-note-txt"></span></div>
                <div class="psc-note psc-note--lock bv-hidden" id="psPromosEditLocked"><span class="psc-note-txt"></span></div>

                <div class="psc-field bv-hidden" id="psPromosEditAmountField">
                    <span class="lbl">Importe</span>
                    <div class="psc-money-input">
                        <input type="number" class="finput mono" id="psPromosEditAmount" min="0.01" max="500" step="0.01" inputmode="decimal">
                        <span class="sfx">€</span>
                    </div>
                </div>

                <div class="psc-field bv-hidden" id="psPromosEditPercentField">
                    <span class="lbl">Porcentaje</span>
                    <div class="psc-money-input">
                        <input type="number" class="finput mono" id="psPromosEditPercent" min="0.01" max="100" step="0.01" inputmode="decimal">
                        <span class="sfx">%</span>
                    </div>
                </div>

                <div class="psc-fieldrow">
                    <div class="psc-field">
                        <span class="lbl">Mínimo</span>
                        <div class="psc-money-input">
                            <input type="number" class="finput mono" id="psPromosEditMinimum" min="0" step="0.01" inputmode="decimal">
                            <span class="sfx">€</span>
                        </div>
                    </div>
                    <div class="psc-field">
                        <span class="lbl">Caduca</span>
                        <input type="date" class="finput" id="psPromosEditDate">
                    </div>
                </div>

                <div class="psc-field">
                    <span class="lbl">Usos disponibles</span>
                    <input type="number" class="finput mono" id="psPromosEditQuantity" min="1" max="20" step="1" inputmode="numeric">
                </div>

                <div class="psc-row">
                    <span class="k">Usos</span>
                    <span class="v mono" id="psPromosEditUses">—</span>
                </div>
                <div class="psc-row">
                    <span class="k">Restricciones</span>
                    <span class="v" id="psPromosEditRestrictions">—</span>
                </div>

                <div class="psc-promos-hint" id="psPromosEditHint">Solo editable si no se ha usado. Con usos, se ofrece duplicarlo en vez de modificarlo.</div>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psPromosEditSave" disabled>Guardar cupón</button>
            <button type="button" class="psc-btn psc-btn--outline" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

<div class="bv-modal" data-bv-modal-name="ps-vouchers-shop"
     data-shop-url="{{ route('manager.helpdesk.ps.ext.promos.shop') }}"
     data-can-apply="{{ $psPromosCanApply ? 1 : 0 }}">
    <div class="modal w-md">

        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">Tienda · Cupones</div>
                <div class="modal-title">Promociones de la tienda</div>
            </div>
            <button class="modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="modal-body psc-promos-body">
            <label class="psc-promos-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" id="psPromosSearch" placeholder="Buscar promoción vigente…" autocomplete="off">
            </label>

            <div class="psc-promos-list" id="psPromosShopList"></div>

            <div class="psc-note psc-note--info">
                <span class="psc-note-txt">Solo lista las reglas públicas y vigentes. Las de un cliente concreto siguen en su tab de Cupones.</span>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="psc-btn psc-btn--outline" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset($psPromosJs) }}?v={{ @filemtime(public_path($psPromosJs)) }}" defer></script>
    @endpush
@endonce
