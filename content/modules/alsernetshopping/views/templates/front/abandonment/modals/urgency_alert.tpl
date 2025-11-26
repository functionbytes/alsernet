{*
* Modal de alerta de urgencia/stock - Carrito Abandonado
* AlsernetShopping Module
*}

<!-- Modal Alerta de Urgencia -->
<div class="abandoned-cart-modal modal-urgency" id="modal-urgency-alert" data-modal-type="urgency_alert">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <button type="button" class="modal-close">&times;</button>
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="urgency-title">{l s='¡Stock Limitado!' d='Modules.Alsernetshopping.Shop'}</h3>
        </div>
        
        <div class="modal-body">
            <div class="urgency-message">
                <i class="fas fa-clock"></i>
                {l s='Algunos productos de tu carrito tienen pocas unidades disponibles' d='Modules.Alsernetshopping.Shop'}
            </div>
            
            <div class="low-stock-list" id="low-stock-products">
                {* Los productos con stock bajo se cargarán dinámicamente *}
            </div>
            
            <div class="social-proof" id="social-proof-section">
                <i class="fas fa-users"></i>
                <span id="people-viewing">12</span> {l s='personas están viendo estos productos ahora mismo' d='Modules.Alsernetshopping.Shop'}
            </div>
            
            <div class="stock-indicators">
                <div class="stock-item" data-product-id="template" style="display: none;">
                    <div class="product-name"></div>
                    <div class="stock-bar">
                        <div class="stock-level"></div>
                    </div>
                    <div class="stock-text"></div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-danger btn-secure-purchase">
                <i class="fas fa-lock"></i>
                {l s='Asegurar Mi Compra Ahora' d='Modules.Alsernetshopping.Shop'}
            </button>
            <button type="button" class="btn btn-secondary modal-close">
                {l s='Seguir Navegando' d='Modules.Alsernetshopping.Shop'}
            </button>
        </div>
    </div>
</div>