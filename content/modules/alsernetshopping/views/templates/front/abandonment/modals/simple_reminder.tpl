{*
* Modal de recordatorio simple - Carrito Abandonado
* AlsernetShopping Module
*}

<!-- Modal Recordatorio Simple -->
<div class="abandoned-cart-modal modal-simple-reminder" id="modal-simple-reminder" data-modal-type="simple_reminder">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <button type="button" class="modal-close">&times;</button>
            <div class="modal-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3>{l s='¡Tienes productos en tu carrito!' d='Modules.Alsernetshopping.Shop'}</h3>
        </div>
        
        <div class="modal-body">
            <p>{l s='Hemos notado que tienes algunos productos increíbles en tu carrito. ¡No los pierdas!' d='Modules.Alsernetshopping.Shop'}</p>
            
            <div class="current-cart">
                <h4>{l s='Productos en tu carrito:' d='Modules.Alsernetshopping.Shop'}</h4>
                <div class="cart-products-list" id="cart-products-simple">
                    {* Los productos se cargarán dinámicamente *}
                </div>
            </div>
            
            <div class="cart-summary">
                <div class="total-amount">
                    <strong>{l s='Total:' d='Modules.Alsernetshopping.Shop'} <span id="cart-total-simple">€0.00</span></strong>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-primary btn-complete-purchase">
                <i class="fas fa-credit-card"></i>
                {l s='Completar Compra' d='Modules.Alsernetshopping.Shop'}
            </button>
            <button type="button" class="btn btn-secondary modal-close">
                {l s='Seguir Navegando' d='Modules.Alsernetshopping.Shop'}
            </button>
        </div>
    </div>
</div>