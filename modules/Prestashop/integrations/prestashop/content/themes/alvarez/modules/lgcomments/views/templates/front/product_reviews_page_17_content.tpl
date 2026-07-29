{*
 *  Please read the terms of the CLUF license attached to this module(cf "licences" folder)
 *
 *  @author    Línea Gráfica E.C.E. S.L.
 *  @copyright Lineagrafica.es - Línea Gráfica E.C.E. S.L. all rights reserved.
 *  @license   https://www.lineagrafica.es/licenses/license_en.pdf
 *             https://www.lineagrafica.es/licenses/license_es.pdf
 *             https://www.lineagrafica.es/licenses/license_fr.pdf
 *}
<section id="main" class="product-reviews">
    <div class="row">
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-4 col-lg-3 col-xl-3 product-reviews-product-column">
            <div class="product-reviews-product-image">
                <a href="{$product.url}" class="thumbnail product-thumbnail">
                    <img
                        class="img-fluid"
                        src = "{$product.cover.bySize.home_default.url}"
                        alt = "{$product.cover.legend}"
                        data-full-size-image-url = "{$product.cover.large.url}"
                    />
                </a>
            </div>

            <div class="product-reviews-product-btn product-reviews-product-view-product">
                <a href="{$product.link}" class="btn btn-primary btn-view-product">{l s='View product' d='Shop.Theme.Catalog'}</a>
            </div>

            <div class="content-button product-reviews-product-btn product-reviews-product-view-product">
                <a href="{$product.link}" class="lgcomment_button btn btn-primary btn-write-review">{l s='Click here to leave a review' mod='lgcomments'}</a>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-8 col-lg-9 col-xl-9 product-reviews-comments-column">
            <h1 class="product-reviews-title">{l s='Reviews about' d='Shop.Theme.Catalog'} {$product.name}</h1>

            <div class="product-reviews-block">
                {if $numerocomentarios}
                    <section id="lgcomments_products" class="lgcomments_store_reviews">
                        {if $reviews|count}
                            <div class="lgcomment_reviews">
                                <div class="row">
                                    {include 'module:lgcomments/views/templates/front/product_reviews_comments_17.tpl' reviews=$reviews product=$product}
                                </div>
                            </div>

                            <div class="lgcomment_pagination">
                                {block name='pagination'}
                                    {include file='_partials/pagination_reviews.tpl' pagination=$pagination}
                                {/block}
                            </div>
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
                {/if}

                <div style="display: none;">
                    {include file="module:lgcomments/views/templates/front/form_review_popup.tpl"}
                </div>
            </div>
        </div>
    </div>
</section>
