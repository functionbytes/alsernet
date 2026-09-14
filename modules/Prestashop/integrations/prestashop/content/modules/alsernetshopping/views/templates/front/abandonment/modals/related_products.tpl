{*
* Modal de productos relacionados - Carrito Abandonado
* AlsernetShopping Module
*}

<!-- Modal Productos Relacionados -->
<div class="abandoned-cart-modal modal-related" id="modal-related-products" data-modal-type="related_products">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <button type="button" class="modal-close">&times;</button>
            <div class="modal-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <h3>{l s='¡Completa tu compra!' d='modules.Alsernetshopping.Shop'}</h3>
        </div>

        <div class="modal-body">
            <div class="current-cart">
                <h4>{l s='En tu carrito:' d='modules.Alsernetshopping.Shop'}</h4>
                <div class="cart-products-list" id="cart-products-related">
                    {* Los productos del carrito se cargarán dinámicamente *}
                </div>
            </div>

            <div class="recommendations">
                <h4>{l s='Te recomendamos también:' d='modules.Alsernetshopping.Shop'}</h4>
                <div class="recommended-products-grid" id="recommended-products">
                    {* Los productos recomendados se cargarán dinámicamente *}
                </div>
            </div>

            <div class="combo-offer" id="combo-offer-section" style="display: none;">
                <div class="combo-badge">
                    <i class="fas fa-gift"></i>
                    {l s='OFERTA COMBO' d='modules.Alsernetshopping.Shop'}
                </div>
                <div>
                    {l s='Compra todo junto y ahorra' d='modules.Alsernetshopping.Shop'}
                    <strong><span id="combo-discount">15%</span></strong>
                </div>
                <div class="combo-savings">
                    {l s='Ahorras:' d='modules.Alsernetshopping.Shop'} <span id="combo-savings-amount">€0.00</span>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-success btn-add-all-and-buy" style="display: none;">
                <i class="fas fa-cart-plus"></i>
                {l s='Añadir Todo y Comprar' d='modules.Alsernetshopping.Shop'}
            </button>
            <button type="button" class="btn btn-primary btn-complete-purchase">
                <i class="fas fa-shopping-cart"></i>
                {l s='Ir al Carrito' d='modules.Alsernetshopping.Shop'}
            </button>
            <button type="button" class="btn btn-secondary modal-close">
                {l s='Continuar Navegando' d='modules.Alsernetshopping.Shop'}
            </button>
        </div>
    </div>
</div>
