{*
* Modal de oferta con descuento - Carrito Abandonado
* AlsernetShopping Module
*}

<!-- Modal Oferta de Descuento -->
<div class="abandoned-cart-modal modal-discount" id="modal-discount-offer" data-modal-type="discount_offer">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <button type="button" class="modal-close">&times;</button>
            <div class="modal-icon">
                <i class="fas fa-percent"></i>
            </div>
            <div class="discount-badge">
                <span id="discount-percentage">10%</span> {l s='DESCUENTO' d='Modules.Alsernetshopping.Shop'}
            </div>
            <h3>{l s='¡Oferta Especial Solo Para Ti!' d='Modules.Alsernetshopping.Shop'}</h3>
        </div>
        
        <div class="modal-body">
            <p>{l s='¡No queremos que te vayas! Usa este código de descuento exclusivo:' d='Modules.Alsernetshopping.Shop'}</p>
            
            <div class="discount-code">
                <label>{l s='Código de descuento:' d='Modules.Alsernetshopping.Shop'}</label>
                <div class="code-container">
                    <input type="text" id="discount-code-field" readonly value="" class="form-control">
                    <button type="button" class="btn-copy" onclick="copyDiscountCode()">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            
            <div class="price-comparison">
                <div class="price-breakdown">
                    <div class="price-line">
                        <span>{l s='Subtotal:' d='Modules.Alsernetshopping.Shop'}</span>
                        <span class="price-original" id="original-total">€0.00</span>
                    </div>
                    <div class="price-line discount-line">
                        <span>{l s='Descuento:' d='Modules.Alsernetshopping.Shop'}</span>
                        <span class="savings" id="discount-amount">-€0.00</span>
                    </div>
                    <div class="price-line total-line">
                        <span><strong>{l s='Total con descuento:' d='Modules.Alsernetshopping.Shop'}</strong></span>
                        <span class="price-discounted" id="discounted-total">€0.00</span>
                    </div>
                    <div class="savings-highlight">
                        {l s='¡Ahorras' d='Modules.Alsernetshopping.Shop'} <span class="savings" id="total-savings">€0.00</span>!
                    </div>
                </div>
            </div>
            
            <div class="countdown-timer" id="discount-timer" style="display: none;">
                <div>{l s='¡Esta oferta expira en:' d='Modules.Alsernetshopping.Shop'}</div>
                <div class="timer" id="countdown-display">05:00</div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-success btn-apply-discount">
                <i class="fas fa-tag"></i>
                {l s='Aplicar Descuento y Comprar' d='Modules.Alsernetshopping.Shop'}
            </button>
            <button type="button" class="btn btn-secondary modal-close">
                {l s='No, Gracias' d='Modules.Alsernetshopping.Shop'}
            </button>
        </div>
    </div>
</div>