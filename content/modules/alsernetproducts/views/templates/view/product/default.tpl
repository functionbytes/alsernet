{widget name="alvarezbanner" object=$product.id zone=1 type=2}

<div class="page-product-default">

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
                            {block name='product_reference'}
                                {if isset($product.reference_to_display) && $product.reference_to_display neq ''}
                                    <div class="product-reference">{l s='Ref.' mod='alsernetproducts'}:
                                        <span>{$product.reference_to_display}</span></div>
                                {/if}
                            {/block}
                        </div>
                    </div>

                    {if $language.id != 1 && $product.blocked}
                        <div class="product-blocked">
                            <p class="product-blocked-text">
                                {l s='Sorry, this product cannot be shipped to your country' mod='alsernetproducts'}
                            </p>
                        </div>
                    {elseif $product.isPing}

                        {include file='catalog/_partials/product-prices.tpl'}
                        <div class="product-actions">
                            <div class="request-price">
                                <div class="request-price-text">
                                    {if $iso_code == "es"}
                                        {l s='Available in our physical stores or by phone  ping'  mod='alsernetproducts'}
                                        <b>{$shop.phone}</b>
                                    {else}
                                        {l s='Available in our physical stores or by phone  ping'  mod='alsernetproducts'}
                                        <b>{l s='check it out and call us at' mod='alsernetproducts'}</b>
                                    {/if}
                                </div>
                                {if $iso_code == "es"}
                                    <div class="request-price-text-legend">
                                        {l s='* Telephone service hours: Monday to Friday from 9:00 a.m. to 8:00 p.m.; Saturdays from 10:00 a.m. to 2:00 p.m. ping' mod='alsernetproducts'}
                                    </div>
                                {/if}
                                {if $iso_code == "es" || $iso_code == "pt"}
                                    <div class="request-price-btn-sticky hidden-md-up">
                                        <a class="btn btn-primary" href="tel:{$shop.phone}">
                                            <i class="fa fa-phone"></i> {l s='Call us at [phone]' sprintf=['[phone]' => $shop.phone] mod='alsernetproducts'}
                                        </a>
                                    </div>
                                    <div class="request-price-btn">
                                        <a class="btn btn-primary btn-wecallyouus">
                                            <i class="fa fa-phone"></i> {l s='We call you ping' mod='alsernetproducts'}
                                        </a>
                                    </div>
                                    <div class="request-price-form">
                                        {widget name="alsernetforms" forms="wecallyouus"}
                                    </div>
                                {/if}
                            </div>
                        </div>
                    {elseif $product.quantity > 0}

                        {if (!isset($request_price) || !$request_price.value)}

                            {include file='catalog/_partials/product-prices.tpl'}

                            {if $product.is_customizable && count($product.customizations.fields)}
                                {if $product.view == 'lot'}
                                    {hook h='displayWkProductOverideTpl' idProduct=$product.id view=$product.view}
                                {else}
                                    {block name='product_customization'}
                                        {include file="catalog/_partials/product-customization.tpl" customizations=$product.customizations}
                                    {/block}
                                {/if}
                            {/if}

                        {/if}
                        <div class="product-actions">

                            {if $product.request_price == '1' || ($product.request_price == '0' && $product.phone_sale == '1')}
                                <div class="request-price">

                                    <div class="request-price-text">
                                        {if $iso_code == "es"}
                                            {l s='We have the best price'  mod='alsernetproducts'}
                                            <b>{l s='check it out and call us at' mod='alsernetproducts'} {$shop.phone}</b>
                                        {else}
                                            {l s='We have the best price'  mod='alsernetproducts'}
                                            <b>{l s='check it out and call us at' mod='alsernetproducts'}</b>
                                        {/if}
                                    </div>
                                    {if $iso_code == "es" }
                                        <div class="request-price-text-legend">
                                            {l s='* Telephone service hours: Monday to Friday from 9:00 a.m. to 8:00 p.m.; Saturdays from 10:00 a.m. to 2:00 p.m.' mod='alsernetproducts'}
                                        </div>
                                    {/if}
                                    {if $iso_code == "es" || $iso_code == "pt"}
                                        <div class="request-price-btn-sticky hidden-md-up">
                                            <a class="btn btn-primary" href="tel:{$shop.phone}">
                                                <i class="fa fa-phone"></i> {l s='Call us at' mod='alsernetproducts'}{$shop.phone}
                                            </a>
                                        </div>
                                        <div class="request-price-btn">
                                            <a class="btn btn-primary btn-wecallyouus">
                                                <i class="fa fa-phone"></i> {l s='We call you' mod='alsernetproducts'}
                                            </a>
                                        </div>
                                        <div class="request-price-form">
                                            {widget name="alsernetforms" forms="wecallyouus"}
                                        </div>
                                    {/if}

                                </div>
                                {if (!isset($custom_show_price) || $custom_show_price)
                                && (isset($custom_show_add_basket_btn)
                                && !$custom_show_add_basket_btn)
                                && (!isset($text_unsaleable_products)
                                || !$text_unsaleable_products)}
                                    <div class="request-available">
                                        <div class="msg-available-title">{l s='To purchase this product, contact us via email: web@a-alvarez.com' sprintf=['[phone]' => $shop.phone] mod='alsernetproducts'}</div>
                                    </div>
                                {/if}
                            {else}

                                <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">

                                    <input type="hidden" name="token" value="{$static_token}">
                                    <input type="hidden" name="id_product" value="{$product.id}" id="id_product">
                                    <input type="hidden" name="id_product_attribute"
                                           value="{$product.id_product_attribute}" id="id_product_attribute">
                                    <input type="hidden" name="id_customization" value="{$product.id_customization}"
                                           id="id_customization">
                                    <input type="hidden" name="current-price-value" value="">

                                    {block name='product_variants'}
                                        {include file='catalog/_partials/product-variants.tpl'}
                                    {/block}

                                    {block name='product_quantity'}
                                        {include file='catalog/_partials/product_quantity.tpl'}
                                    {/block}

                                    {block name='product_add_to_cart'}
                                        {include file='catalog/_partials/product-add-to-cart.tpl'}
                                    {/block}

                                </form>
                            {/if}

                        </div>
                        {widget name="alvarezbanner" object=$product.id zone=7 type=2}
                        {widget name="alvarezbanner" object=$product.id zone=4 type=2}

                    {else}

                        {assign var="product_similar_url" value=$urls.pages.index}

                        {if $breadcrumb && $breadcrumb.links}
                            {foreach from=$breadcrumb.links item=breadcrumb_link name=breadcrumb_iteration}
                                {if !$smarty.foreach.breadcrumb_iteration.last}
                                    {assign var="product_similar_url" value=$breadcrumb_link.url}
                                {/if}
                            {/foreach}
                        {/if}
                        <div class="product-unavailable">
                            <div class="product-unavailable-text">
                                {l s='Sorry, [b]this product is out of stock[/b]' sprintf=['[b]' => '<strong>', '[/b]' => '</strong>'] mod='alsernetproducts'}
                            </div>
                            <div class="product-unavailable-btn-sticky">
                                <a class="btn btn-primary" href="{$product_similar_url}">
                                    <i class="icon-action-redo"></i>
                                    {l s='See similar products' mod='alsernetproducts'}
                                </a>
                            </div>
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


</div>

{block name='product_footer'}
    {hook h='displayFooterProduct' product=$product category=$category}
    {hook h='displayProductAlsernet' mod='alsernetgooglegtm'}
    {hook h='displayProductAlsernetFooter' mod='alsernetgooglegtm'}
{/block}