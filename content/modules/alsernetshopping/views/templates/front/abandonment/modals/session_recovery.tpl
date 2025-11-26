{*
* Modal de recuperación de sesión - Carrito Abandonado
* AlsernetShopping Module
*}

<!-- Modal Recuperación de Sesión -->
<div class="abandoned-cart-modal modal-recovery" id="modal-session-recovery" data-modal-type="session_recovery">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <button type="button" class="modal-close">&times;</button>
            <div class="modal-icon">
                <i class="fas fa-undo"></i>
            </div>
            <h3>{l s='¡Bienvenido de vuelta!' d='Modules.Alsernetshopping.Shop'}</h3>
        </div>
        
        <div class="modal-body">
            <div class="welcome-info">
                <div>
                    <strong>{l s='Última visita:' d='Modules.Alsernetshopping.Shop'}</strong> 
                    <span id="last-visit-date">Hace 2 días</span>
                </div>
                <div>
                    <strong>{l s='Productos guardados:' d='Modules.Alsernetshopping.Shop'}</strong> 
                    <span id="saved-products-count">3</span> {l s='artículos' d='Modules.Alsernetshopping.Shop'}
                </div>
                <div>
                    <strong>{l s='Total guardado:' d='Modules.Alsernetshopping.Shop'}</strong> 
                    <span id="saved-total">€0.00</span>
                </div>
            </div>
            
            <div class="good-news" id="good-news-section" style="display: none;">
                <div class="news-header">
                    <i class="fas fa-thumbs-up"></i>
                    {l s='¡Buenas noticias!' d='Modules.Alsernetshopping.Shop'}
                </div>
                <div class="price-drops-list" id="price-drops">
                    {* Los productos con bajada de precio se cargarán dinámicamente *}
                </div>
            </div>
            
            <div class="personalized-offers" id="personalized-offers-section" style="display: none;">
                <h4>
                    <i class="fas fa-star"></i>
                    {l s='Ofertas personalizadas para ti:' d='Modules.Alsernetshopping.Shop'}
                </h4>
                <div class="offers-grid" id="personal-offers">
                    {* Las ofertas personalizadas se cargarán dinámicamente *}
                </div>
            </div>
            
            <div class="cart-recovery">
                <h4>{l s='Tu carrito guardado:' d='Modules.Alsernetshopping.Shop'}</h4>
                <div class="saved-cart-products" id="saved-cart-products">
                    {* Los productos del carrito guardado se cargarán dinámicamente *}
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-primary btn-restore-cart">
                <i class="fas fa-shopping-cart"></i>
                {l s='Recuperar Carrito' d='Modules.Alsernetshopping.Shop'}
            </button>
            <button type="button" class="btn btn-success btn-explore-offers" style="display: none;">
                <i class="fas fa-gift"></i>
                {l s='Ver Ofertas' d='Modules.Alsernetshopping.Shop'}
            </button>
            <button type="button" class="btn btn-secondary modal-close">
                {l s='Continuar Navegando' d='Modules.Alsernetshopping.Shop'}
            </button>
        </div>
    </div>
</div>