{extends file='layouts/layout-both-columns.tpl'}

{block name="left_column"}{/block}

{block name='top'}
    <div class="main-products content-products">
        <h1 class="products-title">{$categories.h1.$iso_code}</h1>
    </div>
    <div class="ofertas-deportes-wrapper">
        <div class="row">
            {foreach from=$products item=product}
                <div class="col-6 col-md-4 col-lg-4 mb-2 mb-lg-4 {if $product.title == "0"}mobile-hidden0{elseif $product.title == "0"}mobile-hidden1{/if}">
                    <div class="banner-product">
                        <a class="banner banner-fixed br-xs" href="{$product.url}">
                            <figure class="banner-media h-100">
                                <img src="{$product.image}"
                                     alt="{$product.title}">
                                <div class="overlay"></div>
                                    <div class="banner-content">
                                        <h2 class="banner-title  text-uppercase ">{$categories.title.$iso_code}</h2>
                                    </div>
                            </figure>
                        </a>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    <div class="ofertas-express">
        {widget name="alvarezbanner" object=$id_sport zone=98 type=1}
        {widget name="alvarezbanner" object=$id_sport zone=99 type=1}
    </div>
    <div class="ofertas-deportes-wrapper categories-wrapper">
        <div class="row">
            {foreach from=$categories.imagenes.$iso_code item=img}
                <div class="col-6 col-md-4 col-lg-4 mb-2 mb-lg-4 {if $img.title == "0"}mobile-hidden0{elseif $img.title == "0"}mobile-hidden1 {elseif $img.title == "2"}mobile-hidden2{/if}">
                    <div class="banner-product">
                        <a class="banner banner-fixed br-xs" href="{$img.url}">
                            <figure class="banner-media h-100">
                                <img src="/themes/alvarez/assets/img/theme/cms/146/imagenes_deportes/{$categories.deporte}/{$img.image}"
                                     alt="{$img.title}">
                                <div class="overlay"></div>
                                {if $img.title != "0" && $img.title != "1" && $img.title != "2"}
                                    <div class="banner-content">
                                        <h2 class="banner-title  text-uppercase ">{$img.title}</h2>
                                    </div>
                                {/if}
                            </figure>
                        </a>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    <div class="ofertas-express-message">
        <!--<h3 class="text-uppercase">{$categories.h3.$iso_code}</h3>-->
        <p>
            {$categories.texts.$iso_code|escape:'htmlall'|nl2br nofilter}
        </p>
    </div>
{/block}

{block name="content"}

{/block}

