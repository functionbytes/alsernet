{*
* Templates comunes reutilizables para modales de abandonment
* AlsernetShopping Module
*}

{* Template para producto en lista *}
<script type="text/template" id="product-item-template">
    <div class="product-item" data-product-id="{{id}}">
        <div class="product-image">
            <img src="{{image}}" alt="{{name}}" loading="lazy">
        </div>
        <div class="product-info">
            <h5 class="product-name">{{name}}</h5>
            <div class="product-price">
                {{#if original_price}}
                    <span class="price-original">€{{original_price}}</span>
                {{/if}}
                <span class="price-current">€{{price}}</span>
                {{#if discount}}
                    <span class="discount-badge">-{{discount}}%</span>
                {{/if}}
            </div>
            {{#if stock_warning}}
                <div class="stock-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{stock_message}}
                </div>
            {{/if}}
        </div>
        <div class="product-actions">
            {{#if show_add_button}}
                <button type="button" class="btn btn-sm btn-primary btn-add-product" data-product-id="{{id}}">
                    <i class="fas fa-plus"></i> {l s='Añadir' d='Modules.Alsernetshopping.Shop'}
                </button>
            {{/if}}
        </div>
    </div>
</script>

{* Template para producto con stock bajo *}
<script type="text/template" id="low-stock-product-template">
    <div class="low-stock-product" data-product-id="{{id}}">
        <div class="product-image">
            <img src="{{image}}" alt="{{name}}" loading="lazy">
        </div>
        <div class="product-info">
            <h5 class="product-name">{{name}}</h5>
            <div class="stock-warning-text">
                <i class="fas fa-exclamation-triangle"></i>
                {l s='Solo quedan' d='Modules.Alsernetshopping.Shop'} <strong>{{stock_remaining}}</strong> {l s='unidades' d='Modules.Alsernetshopping.Shop'}
            </div>
        </div>
        <div class="product-price">
            <span class="price-current">€{{price}}</span>
        </div>
    </div>
</script>

{* Template para oferta personalizada *}
<script type="text/template" id="personal-offer-template">
    <div class="offer-item" data-offer-id="{{id}}">
        <div class="offer-badge {{badge_class}}">
            <i class="{{icon}}"></i>
            <span>{{discount}}% OFF</span>
        </div>
        <div class="offer-content">
            <h5>{{title}}</h5>
            <p>{{description}}</p>
            <div class="offer-prices">
                <span class="price-original">€{{original_price}}</span>
                <span class="price-offer">€{{offer_price}}</span>
            </div>
        </div>
        <div class="offer-action">
            <button type="button" class="btn btn-sm btn-primary btn-claim-offer" data-offer-id="{{id}}">
                {l s='Reclamar' d='Modules.Alsernetshopping.Shop'}
            </button>
        </div>
    </div>
</script>

{* Template para producto rebajado *}
<script type="text/template" id="price-drop-template">
    <div class="price-drop-item" data-product-id="{{id}}">
        <div class="drop-badge">
            <i class="fas fa-arrow-down"></i>
            <span>-{{discount_percentage}}%</span>
        </div>
        <div class="product-info">
            <div class="product-image">
                <img src="{{image}}" alt="{{name}}" loading="lazy">
            </div>
            <div class="product-details">
                <h6>{{name}}</h6>
                <div class="price-comparison">
                    <span class="price-was">Era: €{{original_price}}</span>
                    <span class="price-now">Ahora: €{{current_price}}</span>
                </div>
            </div>
        </div>
    </div>
</script>