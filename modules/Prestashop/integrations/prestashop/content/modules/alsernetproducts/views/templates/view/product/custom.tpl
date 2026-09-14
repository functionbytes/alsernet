{widget name="alvarezbanner" object=$product.id zone=1 type=2}

<div class="page-product-default page-product-custom {if isset($product.custom) && $product.custom == 'ping'}page-product-ping{/if}">



    <section id="main">
        <div class="row ">
            <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                <section class="product product-single" id="content">
                    {include file='catalog/_partials/product-cover-thumbnails.tpl'}
                </section>
            </div>
            <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-6  ">
                <div class="product-details">

                    <meta itemprop="name" content="{$product_manufacturer->name}"/>
                    <meta itemprop="sku" content="{if $product.reference}{$product.reference}{else}{$product.id}{/if}"/>
                    <meta itemprop="mpn" content="{if $product.mpn}{$product.mpn}{elseif $product.reference}{$product.reference}{else}{$product.id}{/if}"/>
                    <h1 class="product-title">{$product.name}</h1>
                    {widget name="alsernetproducts" type="reviews" option="viewreviews" product=$product }

                    <div class="product-bm-wrapper">
                        <div class="product-meta">

                        </div>
                    </div>




                    {if $language.id != 1 && $product.blocked}
                        <div class="product-blocked">
                            <p class="product-blocked-text">
                                {l s='Sorry, this product cannot be shipped to your country' mod='alsernetproducts'}
                            </p>
                        </div>

                    {elseif $product.quantity > 0}

                        <div class="product-actions">
                                <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">

                                    <input type="hidden" name="token" value="{$static_token}">
                                    <input type="hidden" name="id_product" value="{$product.id}" id="id_product">
                                    <input type="hidden" name="id_product_attribute"
                                           value="{$product.id_product_attribute}" id="id_product_attribute">
                                    <input type="hidden" name="id_customization" value="{$product.id_customization}"
                                           id="id_customization">
                                    <input type="hidden" name="current-price-value" value="">

                                    {hook h='displayIdxrcustomproduct' mod='idxrcustomproduct'}


                                </form>
                        </div>

                        {widget name="alvarezbanner" object=$product.id zone=7 type=2}
                        {widget name="alvarezbanner" object=$product.id zone=4 type=2}

                    {/if}


                    {if not $product.id_manufacturer and !isset($social_share_links)}
                        <div class="row">
                            <div class="col-12 col-md-6 px-1">
                                <div class="reassurance-container border p-1">
                                    <div class="icon-box icon-box-side d-flex align-items-center">
                                            <span class="icon-box-icon text-dark me-2">
                                              <i class="fa-regular fa-lock"></i>
                                            </span>
                                        <div class="icon-box-content">
                                            <h4 class="icon-box-title mb-0">{l s='Secure payments' mod='alsernetproducts'}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 px-1">
                                <div class="reassurance-container border p-1">
                                    <div class="icon-box icon-box-side d-flex align-items-center">
                                        <span class="icon-box-icon text-dark me-2">
                                          <i class="fa-regular fa-arrows-rotate"></i>
                                        </span>
                                        <div class="icon-box-content">
                                            <h4 class="icon-box-title mb-0">{l s='Guaranteed returns' mod='alsernetproducts'}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {else}
                        <div class="row">
                            {if $product.id_manufacturer}
                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <div class="reassurance-container">
                                        <div class="row flex-column">
                                            <div class="icon-box icon-box-side">
                                        <span class="icon-box-icon text-dark">
                                            <i class="fa-regular fa-lock"></i>
                                        </span>
                                                <div class="icon-box-content">
                                                    <h4 class="icon-box-title">{l s='Secure payments' mod='alsernetproducts'}</h4>
                                                </div>
                                            </div>

                                            <div class="icon-box icon-box-side">
                                        <span class="icon-box-icon text-dark">
                                            <i class="fa-regular fa-arrows-rotate"></i>
                                        </span>
                                                <div class="icon-box-content">
                                                    <h4 class="icon-box-title">{l s='Guaranteed returns' mod='alsernetproducts'}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {widget name="alsernetproducts" product=$product type="social"}
                                </div>
                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-6  ">
                                    <div class="manufacture-container full">
                                        <div class="manufacture-content">
                                            <div class="manufacture-images">
                                                {if isset($manufacturer_image_url)}
                                                    <a href="{$product_brand_url}">
                                                        <img src="{$manufacturer_image_url}"
                                                             class="img img-fluid manufacturer-logo"
                                                             alt="{$product_manufacturer->name}" loading="lazy">
                                                    </a>
                                                {else}
                                                    <span>
                                                    <a href="{$product_brand_url}">{$product_manufacturer->name}</a>
                                                </span>
                                                {/if}
                                            </div>

                                        </div>

                                    </div>
                                </div>
                            {else}
                                <div class="px-1 col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <div class="reassurance-container">
                                        <div class="row flex-column">
                                            <div class="icon-box icon-box-side">
                                        <span class="icon-box-icon text-dark">
                                            <i class="fa-regular fa-lock"></i>
                                        </span>
                                                <div class="icon-box-content">
                                                    <h4 class="icon-box-title">{l s='Secure payments' mod='alsernetproducts'}</h4>
                                                </div>
                                            </div>

                                            <div class="icon-box icon-box-side">
                                        <span class="icon-box-icon text-dark">
                                            <i class="fa-regular fa-arrows-rotate"></i>
                                        </span>
                                                <div class="icon-box-content">
                                                    <h4 class="icon-box-title">{l s='Guaranteed returns' mod='alsernetproducts'}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-1 col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-6  ">
                                    {if isset($social_share_links) && $social_share_links|is_array}
                                        <div class="sharing-container">
                                            <div class="sharing-content">
                                                <h4 class="icon-box-title">{l s='Share' mod='alsernetproducts'}</h4>
                                                <div class="social-links">
                                                    <div class="social-icons social-no-color border-thin">
                                                        {foreach from=$social_share_links item='social_share_link'}
                                                            <a href="{$social_share_link.url}" class="social-icon">
                                                                <i class="fa-brands fa-{$social_share_link.class}"></i>
                                                            </a>
                                                        {/foreach}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    {/if}
                                </div>
                            {/if}

                        </div>
                    {/if}


                </div>

            </div>
        </div>
    </section>

    {include file="sub/product_info/accordions.tpl"}


    {block name='product_footer'}
        {widget name='alvarezayuda' product=$product}
    {/block}

    {block name='product_accessories'}
        {if $accessories}
            <section class="product-accessories clearfix">
                <h3 class="h5 products-section-title">{l s='You might also like' mod='alsernetproducts'}</h3>
                <div class="products">
                    <div class="owl-row {if isset($productClassWidget)} {$productClassWidget}{/if}">
                        <div id="category-products2">
                            {foreach from=$accessories item="product_accessory"}
                                <div class="item{if $smarty.foreach.mypLoop.index == 0} first{/if}">
                                    {block name='product_miniature'}
                                        {if isset($productProfileDefault) && $productProfileDefault}
                                            {* exits THEME_NAME/profiles/profile_name.tpl -> load template*}
                                            {hook h='displayLeoProfileProduct' product=$product_accessory profile=$productProfileDefault}
                                        {else}
                                            {include file='catalog/_partials/miniatures/product.tpl' product=$product_accessory}
                                        {/if}
                                    {/block}
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            </section>
        {/if}
    {/block}

    <div class="modal fade fittingModal" id="fittingModal" tabindex="-1" role="dialog" aria-labelledby="fittingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content p-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="fittingModalLabel">LA IMPORTANCIA DE UN FITTING PERSONALIZADO</h5>
                </div>

                <div class="modal-body">
                    <p>
                        Un Fitting personalizado dinámico no es solo una recomendación, es la clave para desbloquear tu potencial completo en el golf.
                        Hay miles de combinaciones de especificaciones (lie, loft, varilla, grip, etc.) y solo si eliges las más adecuadas para ti
                        puedes garantizar el máximo rendimiento.
                    </p>
                    <ul>
                        <li><b>OPTIMIZAR EL RENDIMIENTO:</b> No te arriesgues a adivinar; un ajuste profesional maximiza la distancia y mejora la precisión.</li>
                        <li><b>OPTIMIZAR EL VUELO DE BOLA:</b> Un vuelo de bola incorrecto (demasiado bajo o alto) implica pérdida significativa de distancia.</li>
                        <li><b>MEJORAR LA CONSISTENCIA:</b> La clave para bajar la puntuación es la consistencia, y comienza con un equipo adaptado a ti.</li>
                        <li><b>COMPRAR CON CONFIANZA:</b> El fitting te da seguridad de invertir en un equipo validado para llevar tu juego al siguiente nivel.</li>
                    </ul>

                    <div class="form-check mt-3">
                        <div class="check">
                        <input class="form-check-input fixed-size-input" type="checkbox" id="confirmFitting">
                        <label class="form-check-label " for="confirmFitting">
                            Confirmo que me he sometido a un Ajuste Personalizado Dinámico (Fitting) cara a cara recientemente.
                        </label>
                    </div>
                    </div>

                    <div class="form-check mt-2">
                        <div class="check">
                        <input class="form-check-input fixed-size-input" type="checkbox" id="noFitting">
                        <label class="form-check-label" for="noFitting">
                            No me he sometido a un Ajuste Personalizado Dinámico (Fitting) cara a cara para esta compra. Confirmo que entiendo las desventajas de comprar palos de golf PING sin un ajuste personalizado.
                        </label>
                        </div>
                    </div>

                    <div class="alert alert-danger mt-3 d-none" id="alertFitting">
                        ⚠️ Debes confirmar que te has sometido a un ajuste personalizado para continuar.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="closeFittingModal" class="btn btn-primary w-100 closeFittingModal">Aceptar y continuar</button>
                </div>
            </div>
        </div>
    </div>



</div>

{block name='product_footer'}
    {hook h='displayFooterProduct' product=$product category=$category}
    {hook h='displayProductAlsernet' mod='alsernetgooglegtm'}
    {hook h='displayProductAlsernetFooter' mod='alsernetgooglegtm'}
{/block}