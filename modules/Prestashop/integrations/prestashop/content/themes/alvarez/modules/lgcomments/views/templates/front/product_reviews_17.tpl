<a id="product_details_reviews"></a>

<div class="container-comment">
    <div  class="card" id="lgcomment">
        {if $productform}
            <div class="comment-actions">
                <div class="row">

                    <!--<div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12  ">
                        <div class="content-text">
                            {if !isset($numberofreviews) || ($numberofreviews < 1)}
                            {l s='Be the first to write an opinion.' d='Shop.Theme.Actions'}
                            {/if}
                        </div>
                    </div>-->

                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="content-button{if !isset($numberofreviews) || ($numberofreviews < 1)} hide{/if}">
                            <button class="review-action  btn btn-primary">
                                <span id="send_review" data-close="{l s='close' d='Shop.Theme.Actions'}">
                                  {l s='Click here to leave a review' mod='lgcomments'}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

        {if $productfilter and $numlgcomments > $productfilternb}
            <div class="lgcomment_summary">
                <div class="commentfiltertitle"><span style="text-transform:uppercase;font-weight:bold;">{l s='Filter reviews' d='Shop.Theme.Global'}</span></div>
                <div class="commentfilter" data-filter="five-stars"><span {if $fivestars == 0}style="pointer-events: none;"{/if}><img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/10stars.png" width="80%">({$fivestars|escape:'htmlall':'UTF-8'})</span></div>
                <div class="commentfilter" data-filter="four-stars"><span {if $fourstars == 0}style="pointer-events: none;"{/if}><img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/8stars.png" width="80%">({$fourstars|escape:'htmlall':'UTF-8'})</span></div>
                <div class="commentfilter" data-filter="three-stars"><span {if $threestars == 0}style="pointer-events: none;"{/if}><img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/6stars.png" width="80%">({$threestars|escape:'htmlall':'UTF-8'})</span></div>
                <div class="commentfilter" data-filter="two-stars"><span {if $twostars == 0}style="pointer-events: none;"{/if}><img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/4stars.png" width="80%">({$twostars|escape:'htmlall':'UTF-8'})</span></div>
                <div class="commentfilter" data-filter="one-star"><span {if $onestar == 0}style="pointer-events: none;"{/if}><img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/2stars.png" width="80%">({$onestar|escape:'htmlall':'UTF-8'})</span></div>
                <div class="commentfilter" data-filter="zero-star"><span {if $zerostar == 0}style="pointer-events: none;"{/if}><img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/0stars.png" width="80%">({$zerostar|escape:'htmlall':'UTF-8'})</span></div>
                <div class="commentfilterreset"><span><i class="icon-refresh"></i>  {l s='Reset' d='Shop.Theme.Actions'}</span></div>
            </div><br>
        {/if}

        {if isset($numberofreviews) && $numberofreviews > 0}
        {if isset($display_product_rich_snippets) && $display_product_rich_snippets && isset($display_product_schema_in_product_sheet) && $display_product_schema_in_product_sheet}
             <span itemtype="https://schema.org/Product" itemscope>
                    <meta itemprop="name" content="{$product->name|escape:'quotes':'UTF-8'}">
                    <meta itemprop="description" content="{$product->description|strip_tags:false|escape:'quotes':'UTF-8'}">
                    {if isset($product->reference) && $product->reference}
                        <meta itemprop="sku" content="{$product->reference|escape:'quotes':'UTF-8'}">
                    {/if}
            {if isset($product->manufacturer_name) && $product->manufacturer_name}
                <meta itemprop="brand" content="{$product->manufacturer_name|escape:'quotes':'UTF-8'}">
            {/if}

                    <span itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
                        {if isset($product->quantity) && $product->quantity > 0}
                            <link itemprop="availability" href="https://schema.org/InStock" />
                        {/if}
                        <meta itemprop="price" content="{$product->price|floatval}">
                        <meta itemprop="priceCurrency" content="{$currency->iso_code|escape:'htmlall':'UTF-8'}" />
                    </span>
            {/if}


            {foreach from=$lgcomments item=lgcomment}
                <div {if isset($display_product_rich_snippets) && $display_product_rich_snippets} itemprop="review" itemscope itemtype="https://schema.org/Review"{/if} class="comment" data-filter="{if $lgcomment.stars >= 10}five-stars{elseif $lgcomment.stars >= 8 and $lgcomment.stars < 10}four-stars{elseif $lgcomment.stars >= 6 and $lgcomment.stars < 8}three-stars{elseif $lgcomment.stars >= 4 and $lgcomment.stars < 6}two-stars{elseif $lgcomment.stars >= 2 and $lgcomment.stars < 4}one-star{elseif $lgcomment.stars >= 0 and $lgcomment.stars < 2}zero-star{/if}">
                    <div class="row">
                             <div class="col-md-12 info-block">
                                <div class="head" itemprop="name">
                                    <p class="title" itemprop="name">
                                        {stripslashes($lgcomment.title|escape:'quotes':'UTF-8')}

                                        {if !empty($lgcomment.nick) && $lgcomment.nick != ''}
                                            <p class="nick"{if isset($display_product_rich_snippets) && $display_product_rich_snippets} itemprop="author" itemscope itemtype="https://schema.org/Person"{/if}>
                                                ({if (empty($lgcomment.nick) || $lgcomment.nick == "")}{l s='Anonymous' d='Shop.Theme.Global'}{else}{$lgcomment.nick|escape:'quotes':'UTF-8'}{/if})
                                            </p>
                                        {/if}
                                    </p>

                                </div>
                                <img class="stars" src="/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/{$lgcomment.stars|escape:'htmlall':'UTF-8'}stars.png"  alt="rating" >
                                <span class="rating-hidden"{if isset($display_product_rich_snippets) && $display_product_rich_snippets} itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating"{/if}>
                                    <span{if isset($display_product_rich_snippets) && $display_product_rich_snippets} itemprop="ratingValue"{/if}>{if strpos($lgcomment.rating, '.5')}{$lgcomment.rating|escape:'htmlall':'UTF-8'}{else}{{$lgcomment.rating|intval}|escape:'htmlall':'UTF-8'}{/if}</span>
                                    {if isset($display_product_rich_snippets) && $display_product_rich_snippets}
                                        <meta itemprop="bestRating" content="{$ratingscale|escape:'htmlall':'UTF-8'}">
                                        <meta itemprop="worstRating" content="0">
                                    {/if}
                                </span>
                            </div>
                            <div class="col-md-12">
                                <div class="comments">
                                    {$lgcomment.comment nofilter}
                                </div>
                            </div>
                            {if $lgcomment.answer && $lgcomment.answer != '<p>0</p>'}
                                <div class="col-md-12">
                                    <div class="answer">
                                        {l s='Answer:' d='Shop.Theme.Global'}
                                        {$lgcomment.answer nofilter}
                                    </div>
                                </div>
                            {/if}
                        </div>
                     </div>
            {/foreach}

        
        {/if}
            {if $defaultdisplay < $numlgcomments}
                <div class="comment-more">
                <div id="more_less">
                        <a class="button btn btn-default button button-small" id="displayMore" href="{Context::getContext()->link->getModuleLink('lgcomments', 'productreviews', ['id_product' => $product->id])}"><span><i class="icon-plus-square"></i> {l s='Display more' d='Shop.Theme.Actions'}</span></a>
                </div>
            </div>
            {/if}

            {include file="module:lgcomments/views/templates/front/form_review_popup.tpl"}
    </div>
</div>