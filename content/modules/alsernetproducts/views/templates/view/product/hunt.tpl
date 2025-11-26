<div class="page-product-hunt">
    <div class="container pb-1 pb-lg-10 mb-10">
        <div class="row">
            <div class="col-md-6 mb-4 ">
                <div class="img-hunt">
                    <img src="/themes/alvarez/assets/img/theme/product/hunt.jpg">
                </div>
            </div>
            <div class="col-md-6 hunt-first">
                <div class="details">
                    <h4 class="text-primary font-weight-bold ls-25">{l s='title service hunt' mod='alsernetproducts'}</h4>
                    <h2 class="title text-left">{$product.name}</h2>
                    <p>
                        {l s="Coste del hunt:" mod='alsernetproducts'} <strong class="pricing">{$product.price}</strong>  {l s="Este dinero te será descontado íntegramente de tu compra" mod='alsernetproducts'}
                    </p>
                    <ul class="registration-benefits">
                        <li>
                            <p>{l s="Realice un" mod='alsernetproducts'}</span> <strong>{l s="ANÁLISIS GUIADO DE SU JUEGO" mod='alsernetproducts'}</strong></p>
                        </li>
                        <li>
                            <p>{l s="Pruebe" mod='alsernetproducts'}</span> <strong>{l s="LO ÚLTIMO" mod='alsernetproducts'}</strong></p>
                        </li>
                    </ul>

                    <div class="product-actions hunt">

                        {block name='product_buy'}
                            <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">
                                <input type="hidden" name="token" value="{$static_token}">
                                <input type="hidden" name="id_product" value="{$product.id}" id="id_product">
                                <input type="hidden" name="id_product_attribute" value="{$product.id_product_attribute}" id="id_product_attribute">
                                <input type="hidden" name="id_customization" value="{$product.id_customization}" id="id_customization">

                                <div id="product-description-short-{$product.id}" class="description-short" itemprop="description">{$product.description_short nofilter}</div>

                                {hook h='displayWkProductOverideTpl' idProduct=$product.id}

                                <div class="add-cart-hunt product-add-to-cart-hunt">
                                    {include file='catalog/_partials/product-add-to-cart.tpl'}
                                </div>

                            </form>
                        {/block}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>