{*
 *  Please read the terms of the CLUF license attached to this module(cf "licences" folder)
 *
 *  @author    Línea Gráfica E.C.E. S.L.
 *  @copyright Lineagrafica.es - Línea Gráfica E.C.E. S.L. all rights reserved.
 *  @license   https://www.lineagrafica.es/licenses/license_en.pdf
 *             https://www.lineagrafica.es/licenses/license_es.pdf
 *             https://www.lineagrafica.es/licenses/license_fr.pdf
 *}

{if isset($number_of_reviews) && $number_of_reviews > 0}
    {if isset($display_product_rich_snippets) && $display_product_rich_snippets && isset($display_product_schema_in_product_list) && $display_product_schema_in_product_list}
        <span itemtype="https://schema.org/Product" itemscope>
            <meta itemprop="name" content="{$productname|escape:'quotes':'UTF-8'}">
            <meta itemprop="description" content="{$productdescription|strip_tags:false|escape:'quotes':'UTF-8'}">
            {if isset($productsku) && $productsku}
                <meta itemprop="sku" content="{$productsku|escape:'quotes':'UTF-8'}">
            {/if}
            {if isset($productbrand) && $productbrand}
                <meta itemprop="brand" content="{$productbrand|escape:'quotes':'UTF-8'}">
            {/if}

            <span itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
                {if isset($productquantity) && $productquantity > 0}
                    <link itemprop="availability" href="https://schema.org/InStock" />
                {/if}
                <meta itemprop="price" content="{$productprice|floatval}">
                <meta itemprop="priceCurrency" content="{$currency->iso_code|escape:'htmlall':'UTF-8'}" />
            </span>
    {/if}

    <div class="stars-container"{if isset($display_product_rich_snippets) && $display_product_rich_snippets} itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating"{/if}>
        <div style="{if $cattopmargin != 0}padding-top:{$cattopmargin|intval}px;{/if}{if $catbotmargin != 0}padding-bottom:{$catbotmargin|intval}px;{/if} {*display:table; margin: {$cattopmargin|escape:'htmlall':'UTF-8'}px auto {$catbotmargin|escape:'htmlall':'UTF-8'}px auto;*} text-align: left;">
            <a href="{$productlink|escape:'htmlall':'UTF-8'}#idTab798" class="comment_anchor">
                <img src="{*{$path_lgcomments|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/{$averagestars|escape:'htmlall':'UTF-8'}stars.png"
                     alt="rating" >
                {if $number_of_reviews == 1}
                    <span style="{*width:100px; *}text-align:center;">{l s='1 review' d='Shop.Theme.Global'}</span>
                {/if}
                {if $number_of_reviews > 1}
                    <span style="{*width:100px; *}text-align:center;">{$number_of_reviews|escape:'htmlall':'UTF-8'} {l s='reviews' d='Shop.Theme.Global'}</span>
                {/if}
            </a>
        </div>

        {if isset($display_product_rich_snippets) && $display_product_rich_snippets}
            {if $ratingscale == 5}
                <meta itemprop="ratingValue" content="{$averagecomments/2|escape:'quotes':'UTF-8'}">
                <meta itemprop="bestRating" content="5">
                <meta itemprop="worstRating" content="0">
            {elseif $ratingscale == 10}
                <meta itemprop="ratingValue" content="{$averagecomments|escape:'quotes':'UTF-8'}">
                <meta itemprop="bestRating" content="10">
                <meta itemprop="worstRating" content="0">
            {elseif $ratingscale == 20}
                <meta itemprop="ratingValue" content="{$averagecomments*2|escape:'quotes':'UTF-8'}">
                <meta itemprop="bestRating" content="20">
                <meta itemprop="worstRating" content="0">
            {else}
                <meta itemprop="ratingValue" content="{$averagecomments|escape:'quotes':'UTF-8'}">
                <meta itemprop="bestRating" content="10">
                <meta itemprop="worstRating" content="0">
            {/if}

            <meta itemprop="ratingCount" content="{$number_of_reviews|escape:'quotes':'UTF-8'}">
        {/if}
    </div>

    {if isset($display_product_rich_snippets) && $display_product_rich_snippets && isset($display_product_schema_in_product_list) && $display_product_schema_in_product_list}
        </span>
    {/if}
{/if}


{if !$number_of_reviews &&  $page.page_name == 'product'}
    {if $product->id == $product_id}
        <div class="product-opinions">
            <a href="javascript:void(0);" onclick="$('#mostrarformreview').click();">{l s='Be the first to rate this product' d='Shop.Theme.Catalog'}</a>
        </div>
    {/if}
{/if}