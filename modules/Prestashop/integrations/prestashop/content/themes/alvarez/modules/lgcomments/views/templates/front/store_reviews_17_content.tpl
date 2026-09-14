{*
 *  Please read the terms of the CLUF license attached to this module(cf "licences" folder)
 *
 *  @author    Línea Gráfica E.C.E. S.L.
 *  @copyright Lineagrafica.es - Línea Gráfica E.C.E. S.L. all rights reserved.
 *  @license   https://www.lineagrafica.es/licenses/license_en.pdf
 *             https://www.lineagrafica.es/licenses/license_es.pdf
 *             https://www.lineagrafica.es/licenses/license_fr.pdf
 *}
<section id="main">
    <header class="page-header">
        <h1 class="h1">{l s='Customer reviews' d='Shop.Theme.Global'}</h1>
    </header>
    {if $numerocomentarios}
        <section id="lgcomments_products" class="lgcomments_store_reviews">
            {if $reviews|count}
                {if $storefilter}
                    <div class="lgcomment_summary lgcomment_block">
                        <div class="row">
                            <div class="col-xs-12 col-md-6">
                                <table>
                                    <tr>
                                        <th colspan="4">
                                            <div class="title">{l s='Summary' d='Shop.Theme.Global'}</div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td>
                                            <img class="logo" src="{$logo_url|escape:'htmlall':'UTF-8'}" alt="{$shop_name|escape:'html':'UTF-8'}" width="100">
                                        </td>
                                        <td>
                                            <img src="{$modules_dir|escape:'htmlall':'UTF-8'}/views/img/positive.png" alt="positive" width="25">
                                        </td>
                                        <td>
                                            <p class="sumary-text small"><b>{(($stars.fivestars+$stars.fourstars)/$numerocomentarios*100)|round:1|escape:'htmlall':'UTF-8'}%</b></p>
                                        </td>
                                        <td>
                                            <p class="sumary-text small">({($stars.fivestars+$stars.fourstars)|escape:'htmlall':'UTF-8'} {l s='reviews' d='Shop.Theme.Global'})</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        {if $ratingscale == 5}
                                            <td>
                                                <p class="sumary-text small">{l s='Average rating:' d='Shop.Theme.Global'} <b>{$mediacomentarios2/2|escape:'htmlall':'UTF-8'}/5</b></p>
                                            </td>
                                        {elseif $ratingscale == 10}
                                            <td>
                                                <p class="sumary-text small">{l s='Average rating:' d='Shop.Theme.Global'} <b>{$mediacomentarios2|escape:'htmlall':'UTF-8'}/10</b></p>
                                            </td>
                                        {elseif $ratingscale == 20}
                                            <td>
                                                <p class="sumary-text small">{l s='Average rating:' d='Shop.Theme.Global'} <b>{($mediacomentarios2*2)|round:0|escape:'htmlall':'UTF-8'}/20</b></p>
                                            </td>
                                        {else}
                                            <td>
                                                <p class="sumary-text small">{l s='Average rating:' d='Shop.Theme.Global'} <b>{$mediacomentarios2|escape:'htmlall':'UTF-8'}/10</b></p>
                                            </td>
                                        {/if}
                                        <td>
                                            <p class="small"><img src="{$modules_dir|escape:'htmlall':'UTF-8'}/views/img/neutral.png" alt="neutral" width="25"></p>
                                        </td>
                                        <td>
                                            <p class="sumary-text small"><b>{(($stars.threestars+$stars.twostars)/$numerocomentarios*100)|round:1|escape:'htmlall':'UTF-8'}%</b></p>
                                        </td>
                                        <td>
                                            <p class="sumary-text small">({($stars.threestars+$stars.twostars)|escape:'htmlall':'UTF-8'} {l s='reviews' d='Shop.Theme.Global'})</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="sumary-text small">{l s='Number of reviews:' d='Shop.Theme.Global'} {$numerocomentarios|escape:'htmlall':'UTF-8'}</p>
                                        </td>
                                        <td>
                                            <p class="small"><img src="{$modules_dir|escape:'htmlall':'UTF-8'}/views/img/negative.png" alt="negative" width="25"></p>
                                        </td>
                                        <td>
                                            <p class="sumary-text small"><b>{(($stars.onestar+$stars.zerostar)/$numerocomentarios*100)|round:1|escape:'htmlall':'UTF-8'}%</b></p>
                                        </td>
                                        <td>
                                            <p class="sumary-text small">({($stars.onestar+$stars.zerostar)|escape:'htmlall':'UTF-8'} {l s='reviews' d='Shop.Theme.Global'})</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-xs-12 col-md-6">
                                <table>
                                    <tr>
                                        <th colspan="2">
                                            <div class="title">
                                                {l s='Filter reviews' d='Shop.Theme.Global'}
                                                {*<span class="small">
                                                    <a href="{$star_link|escape:'htmlall':'UTF-8'}">
                                                        (<i class="material-icons" style="font-size: 120%;">sync</i>
                                                        {l s='Reset' d='Shop.Theme.Global'}&nbsp;)
                                                    </a>
                                                </span>*}
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="filer-text small">
                                                <a href="{$star_link|escape:'htmlall':'UTF-8'}?star=five" {if $stars.fivestars == 0}style="pointer-events: none;"{/if}>
                                                    <img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/10stars.png" width="100">
                                                    ({$stars.fivestars|escape:'htmlall':'UTF-8'})
                                                </a>
                                            </p>
                                        </td>
                                        <td>
                                            <p class="filer-text small">
                                                <a href="{$star_link|escape:'htmlall':'UTF-8'}?star=four" {if $stars.fourstars == 0}style="pointer-events: none;"{/if}>
                                                    <img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/8stars.png" width="100">
                                                    ({$stars.fourstars|escape:'htmlall':'UTF-8'})
                                                </a>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="filer-text small">
                                                <a href="{$star_link|escape:'htmlall':'UTF-8'}?star=three" {if $stars.threestars == 0}style="pointer-events: none;"{/if}>
                                                    <img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/6stars.png" width="100">
                                                    ({$stars.threestars|escape:'htmlall':'UTF-8'})
                                                </a>
                                            </p>
                                        </td>
                                        <td>
                                            <p class="filer-text small">
                                                <a href="{$star_link|escape:'htmlall':'UTF-8'}?star=two" {if $stars.twostars == 0}style="pointer-events: none;"{/if}>
                                                    <img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/4stars.png" width="100">
                                                    ({$stars.twostars|escape:'htmlall':'UTF-8'})
                                                </a>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="filer-text small">
                                                <a href="{$star_link|escape:'htmlall':'UTF-8'}?star=one" {if $stars.onestar == 0}style="pointer-events: none;"{/if}>
                                                    <img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/2stars.png" width="100">
                                                    ({$stars.onestar|escape:'htmlall':'UTF-8'})
                                                </a>
                                            </p>
                                        </td>
                                        <td>
                                            <p class="filer-text small">
                                                <a href="{$star_link|escape:'htmlall':'UTF-8'}?star=zero" {if $stars.zerostar == 0}style="pointer-events: none;"{/if}>
                                                    <img src="{*{$modules_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/0stars.png" width="100">
                                                    ({$stars.zerostar|escape:'htmlall':'UTF-8'})
                                                </a>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                {/if}
                {if $storeform}
                    <div class="lgcomment_button" style="border: none; width: 100%;">
                        <button id="send_store_review" class="btn btn-primary-outline btn-lg" style="margin: 0 auto;" type="button">
                            {l s='Click here to leave a review' mod='lgcomments'}
                        </button>
                    </div>
                {/if}
                <div class="lgcomment_reviews">
                    <div class="row">
                        {include 'module:lgcomments/views/templates/front/store_reviews_comments_17.tpl' reviews=$reviews}
                    </div>
                </div>
                <div class="lgcomment_pagination">
                    {block name='pagination'}
                        {include file='_partials/pagination_reviews.tpl' pagination=$pagination}
                    {/block}
                </div>
                {if $storeform}
                    <div style="display:none">
                        {include file="module:lgcomments/views/templates/front/form_review_popup.tpl"}
                    </div>
                {/if}
            {else}
                <section id="content" class="page-content page-not-found">
                    <h4>{l s='Sorry for the inconvenience.' d='Shop.Theme.Global'}</h4>
                    <p>{l s='There are not reviews yet' d='Shop.Theme.Global'}</p>
                </section>
            {/if}
        </section>
    {else}
        <section id="content" class="page-content page-not-found">
            <p>{l s='There are not reviews yet' d='Shop.Theme.Global'}</p>
        </section>
        {if $storeform}
            <div class="lgcomment_button" style="border: none; width: 100%;">
                <button id="send_store_review" class="btn btn-primary-outline btn-lg" style="margin: 0 auto;" type="button">
                    {l s='Click here to leave a review' mod='lgcomments'}
                </button>
            </div>
            <div style="display:none">
                {include file="module:lgcomments/views/templates/front/form_review_popup.tpl"}
            </div>
        {/if}
    {/if}
</section>
